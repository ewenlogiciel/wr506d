<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class MeController
{
    #[Route("/api/me", name:"get_current_user", methods:["GET"])]
    public function getCurrentUser(#[CurrentUser] User $user): JsonResponse
    {
        $userData = [
            'email' => $user->getEmail(),
        ];

        return new JsonResponse($userData);
    }
}
