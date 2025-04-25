<?php

namespace App\User\Application\Listener;

use App\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener('prePersist')]
#[AsDoctrineListener('preUpdate')]
class UserPasswordHasListener
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $user = $event->getObject();

        if (!$user instanceof User || !$this->passwordHasher->needsRehash($user)) {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $user = $event->getObject();

        if (!$user instanceof User || !$this->passwordHasher->needsRehash($user)) {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
    }
}