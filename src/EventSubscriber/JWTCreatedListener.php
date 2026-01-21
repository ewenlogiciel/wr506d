<?php

namespace App\EventSubscriber;

use App\Entity\User; // <--- 1. Import indispensable
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedListener implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_jwt_created' => 'onJWTCreated',
        ];
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        /** @var User $user */

        $payload = $event->getData();

        $payload['id'] = $user->getId();
        $payload['email'] = $user->getEmail();

        $event->setData($payload);
    }
}
