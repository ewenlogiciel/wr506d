<?php

namespace App\EntityListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserListener
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
    }

    // Se déclenche à la création (Register)
    public function prePersist(User $user, PrePersistEventArgs $event): void
    {
        $this->hashPassword($user);
    }

    // Se déclenche à la modification (Update)
    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        // On vérifie si le mot de passe a été modifié pour éviter de re-hasher un hash !
        if ($event->hasChangedField('password')) {
            $this->hashPassword($user);
        }
    }

    private function hashPassword(User $user): void
    {
        // Si pas de mot de passe, on ne fait rien
        if (!$user->getPassword()) {
            return;
        }

        $hashed = $this->hasher->hashPassword(
            $user,
            $user->getPassword()
        );

        $user->setPassword($hashed);
    }
}
