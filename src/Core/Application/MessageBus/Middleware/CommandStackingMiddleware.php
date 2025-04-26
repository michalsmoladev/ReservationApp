<?php

declare(strict_types=1);

namespace App\Core\Application\MessageBus\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

readonly class CommandStackingMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(DispatchAfterCurrentBusStamp::class)) {
            $envelope = $envelope->with(new DispatchAfterCurrentBusStamp());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}