<?php

declare(strict_types=1);

namespace App\Core\Application\MessageBus\Middleware;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

readonly class ValidationMiddleware extends MiddlewareServiceCreator implements MiddlewareInterface
{
    private const string VALIDATOR_SUFFIX = 'Validator';

    public function __construct(
        #[AutowireLocator(services: 'message.validator', indexAttribute: 'key')]
        private ServiceLocator $locator,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $validatorName = $this->createServiceName($message::class, self::VALIDATOR_SUFFIX);

        if ($this->locator->has($validatorName)) {
            $validatorService = $this->locator->get($validatorName);

            $this->logger->debug('[ValidatorMiddleware] Run validator', [
                'validator' => $validatorService::class,
                'command' => $message::class,
            ]);

            $this->runService($validatorService, $message);
        } else {
            $this->logger->error(\sprintf('[ValidatorMiddleware] %s not found', $validatorName));

            throw new \RuntimeException(\sprintf('[ValidatorMiddleware] %s not found', $validatorName));
        }

        return $stack->next()->handle($envelope, $stack);
    }

    private function runService(object $validator, object $command): void
    {
        $validatorReflection = new \ReflectionClass($validator);
        $validatorAttributeReflections = $validatorReflection->getAttributes(AsMessageValidator::class);

        if (empty($validatorAttributeReflections)) {
            throw new \RuntimeException(
                \sprintf(
                    'Class %1$s has no attribute %2$s maybe you forgot add #[%2$s] before class keyword',
                    $validator::class,
                    AsMessageValidator::class,
                )
            );
        }

        if (!\is_callable($validator)) {
            throw new \RuntimeException(\sprintf('Class %s must be callable', $validator::class));
        }

        $this->logger->debug('[ValidatorMiddleware] Run validator as callable', ['validator' => $validator::class]);
        $validator($command);
    }
}