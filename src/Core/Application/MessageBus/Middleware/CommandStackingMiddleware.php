<?php

namespace App\Core\Application\MessageBus\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

readonly class CommandStackingMiddleware
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(DispatchAfterCurrentBusStamp::class)) {
            $envelope = $envelope->with(new DispatchAfterCurrentBusStamp());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}