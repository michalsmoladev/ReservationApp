<?php

declare(strict_types=1);

namespace App\Core\Application\MessageBus\Middleware;

use App\Core\Application\MessageBus\Attribute\AsMessageLocker;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class LockingMiddleware extends MiddlewareServiceCreator implements MiddlewareInterface
{
    private const VALIDATOR_SUFFIX = 'Locker';

    public function __construct(
        #[TaggedLocator(tag: 'message.locker', indexAttribute: 'key')]
        private readonly ServiceLocator $locator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $lockerName = $this->createServiceName($message::class, self::VALIDATOR_SUFFIX);

        if ($this->locator->has($lockerName)) {
            $lockingStrategyService = $this->locator->get($lockerName);

            $this->logger->debug('[LockingMiddleware] Run locker', [
                'locker' => $lockingStrategyService::class,
                'command' => $message::class,
            ]);

            $this->runService($lockingStrategyService, $message);
        } else {
            $this->logger->debug('[LockingMiddleware] Locker not found', ['service' => $lockerName]);
        }

        return $stack->next()->handle($envelope, $stack);
    }

    public function runService(object $locker, object $command): void
    {
        $lockerReflection = new \ReflectionClass($locker);
        $lockerAttributeReflections = $lockerReflection->getAttributes(AsMessageLocker::class);

        if (empty($lockerAttributeReflections)) {
            throw new \RuntimeException(
                sprintf(
                    'Class %1$s has no attribute %2$s maybe you forgot add #[%2$s] before class keyword',
                    $locker::class,
                    AsMessageLocker::class,
                )
            );
        }

        if (!\is_callable($locker)) {
            throw new \RuntimeException(sprintf('Class %s must be callable', $locker::class));
        }

        $this->logger->debug('[LockingMiddleware] Run locker as callable', ['locker' => $locker::class]);
        $locker($command);
    }
}