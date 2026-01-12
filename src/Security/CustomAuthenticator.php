<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class CustomAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private TwoFactorService $twoFactorService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/auth' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new CustomUserMessageAuthenticationException('Invalid JSON');
        }

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $totpCode = $data['totp_code'] ?? null;

        if (!is_string($email) || !is_string($password)) {
            throw new CustomUserMessageAuthenticationException('Email and password must be strings');
        }

        if (empty($email) || empty($password)) {
            throw new CustomUserMessageAuthenticationException('Email and password are required');
        }

        // On stocke les credentials dans la requête pour usage dans onAuthenticationSuccess
        $request->attributes->set('_auth_password', $password);
        $request->attributes->set('_auth_totp_code', $totpCode);

        return new SelfValidatingPassport(new UserBadge($email, function (string $userIdentifier) {
            $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
            if (!$user instanceof User) {
                throw new CustomUserMessageAuthenticationException('Invalid credentials');
            }
            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Invalid user'], Response::HTTP_UNAUTHORIZED);
        }

        // 1. Vérification du mot de passe
        $password = $request->attributes->get('_auth_password');
        if (!is_string($password)) {
            return new JsonResponse(['error' => 'Invalid password format'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Vérification du 2FA (si activé)
        if ($user->isTwoFactorEnabled() && $user->getTwoFactorSecret() !== null) {
            $totpCode = $request->attributes->get('_auth_totp_code');

            // Si le code n'est pas fourni, on retourne une erreur spécifique
            if ($totpCode === null || $totpCode === '') {
                return new JsonResponse([
                    'status' => 'totp_required',
                    'message' => '2FA code required.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (!is_string($totpCode)) {
                return new JsonResponse(['error' => 'Invalid TOTP code format'], Response::HTTP_UNAUTHORIZED);
            }

            // Vérification de la validité du code
            $isValid = $this->twoFactorService->verifyCode($user, $totpCode);

            if (!$isValid) {
                // Optionnel: Vérifier les codes de secours ici si verifyCode échoue
                // $isValid = $this->twoFactorService->verifyBackupCode($user, $totpCode);

                if (!$isValid) {
                    return new JsonResponse(['error' => 'Invalid 2FA code'], Response::HTTP_UNAUTHORIZED);
                }
            }
        }

        // 3. Génération du token JWT
        $jwt = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $jwt,
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
