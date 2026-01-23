<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/2fa')]
class TwoFactorController extends AbstractController
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/setup', name: 'app_2fa_setup', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function setup(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        // Génération d'un nouveau secret
        $secret = $this->twoFactorService->generateSecret();
        $user->setTwoFactorSecret($secret);
        // On s'assure que le 2FA est désactivé tant qu'il n'est pas confirmé
        $user->setTwoFactorEnabled(false);

        $this->entityManager->flush();

        // Génération du QR code et de l'URI
        $qrCodeDataUri = $this->twoFactorService->getQrCode($user);
        $provisioningUri = $this->twoFactorService->getProvisioningUri($user);

        return $this->json([
            'secret' => $secret,
            'qr_code' => $qrCodeDataUri,
            'provisioning_uri' => $provisioningUri,
            'message' => 'Scannez le QR code avec votre application d\'authentification
            (Google Authenticator, Authy, etc.) puis validez avec un code pour activer le 2FA.',
        ]);
    }

    #[Route('/enable', name: 'app_2fa_enable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enable(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (empty($code)) {
            return $this->json(['error' => 'Code is required'], 400);
        }

        // 1. Vérification du code TOTP
        if (!$this->twoFactorService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid code'], 400);
        }

        // 2. Génération des codes de secours
        $backupCodes = $this->twoFactorService->generateBackupCodes();
        $hashedBackupCodes = $this->twoFactorService->hashBackupCodes($backupCodes);

        // 3. Activation du 2FA et sauvegarde
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorBackupCodes($hashedBackupCodes);

        $this->entityManager->flush();

        return $this->json([
            'message' => '2FA enabled successfully',
            'backup_codes' => $backupCodes,
            'warning' => 'Save these backup codes in a safe place. You won\'t be able to see them again!',
        ]);
    }
}
