<?php

namespace App\EventSubscriber;

use App\Entity\User;
use DateInterval;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\RateLimiter\Policy\SlidingWindowLimiter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @SuppressWarnings("PHPMD.StaticAccess")
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $anonymousApiLimiter,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }
        if (str_starts_with($request->getPathInfo(), '/api/docs') ||
            str_starts_with($request->getPathInfo(), '/api/graphql/graphiql')
        ) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        // 1. On récupère le bon limiter (User ou Anonyme) via la méthode privée
        // Cela supprime le "if/else" complexe de cette méthode principale
        $limiter = $this->getLimiter($user, $request);

        // 2. On consomme
        $limit = $limiter->consume();

        $request->attributes->set('_rate_limit', [
            'limit' => $limit->getLimit(),
            'remaining' => $limit->getRemainingTokens(),
            'reset' => $limit->getRetryAfter()->getTimestamp(),
        ]);

        if (false === $limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $response = new JsonResponse(
                [
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $retryAfter->getTimestamp(),
                ],
                429
            );

            $response->headers->set('Retry-After', (string) $retryAfter->getTimestamp());
            $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string) $retryAfter->getTimestamp());

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $rateLimitInfo = $request->attributes->get('_rate_limit');
        if (!$rateLimitInfo) {
            return;
        }

        $response->headers->set('X-RateLimit-Limit', (string) $rateLimitInfo['limit']);
        $response->headers->set('X-RateLimit-Remaining', (string) $rateLimitInfo['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $rateLimitInfo['reset']);
    }

    /**
     * Cette méthode permet de supprimer le ELSE en utilisant des return.
     */
    private function getLimiter(mixed $user, Request $request): LimiterInterface
    {
        if ($user instanceof User) {
            $identifier = 'user_' . $user->getUserIdentifier();
            $storage = new CacheStorage($this->cache);

            return new SlidingWindowLimiter(
                $identifier,
                $user->getApiRateLimit(),
                DateInterval::createFromDateString($user->getApiRateLimitInterval()),
                $storage
            );
        }

        $identifier = $request->getClientIp() ?? 'unknown';

        return $this->anonymousApiLimiter->create($identifier);
    }
}
