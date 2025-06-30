<?php

declare(strict_types=1);

namespace App\Core\Application\Listener;

use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class KernelListener
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        if (!$user->isActive()) {
            throw new AccessDeniedException('Account is not active.');
        }
    }
}