<?php

declare(strict_types=1);

namespace App\Core\Application\Listener;

use App\Core\Application\Exception\NamedException;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class KernelListener
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(param: 'app.exclude_path')]
        private readonly array $excludePaths,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->isExcludePath($event)) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        if (!$user->isActive()) {
            throw new AccessDeniedException('Account is not active');
        }
    }

    private function isExcludePath(KernelEvent $event): bool
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        return array_filter($this->excludePaths, fn (string $excludePath) => str_starts_with($path, $excludePath)) !== [];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious();
        }

        if ($exception instanceof NamedException) {
            $event->setResponse(
                new JsonResponse(
                    [
                        'message' => $exception->getErrorMessage(),
                        'code' => $exception->getErrorCode(),
                    ],
                    JsonResponse::HTTP_NOT_FOUND
                )
            );
        }

        if ($exception instanceof ValidationFail) {
            $event->setResponse(
                new JsonResponse(
                    [
                        'message' => $exception->getMessage(),
                    ],
                    JsonResponse::HTTP_BAD_REQUEST
                )
            );
        }
    }
}