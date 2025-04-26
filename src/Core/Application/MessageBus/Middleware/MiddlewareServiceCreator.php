<?php

declare(strict_types=1);

namespace App\Core\Application\MessageBus\Middleware;

readonly class MiddlewareServiceCreator
{
    private const array ALLOWED_SUFFIXES = ['Query', 'Command'];

    final public function createServiceName(string $commandClassName, string $suffix): string
    {
        $suffixes = array_values(
            array_filter(
                self::ALLOWED_SUFFIXES,
                static fn (string $name) => str_contains($commandClassName, $name) ? $name : ''
            )
        );

        $commandSuffix = $suffixes[0];
        $commandSuffixLength = mb_strlen($commandSuffix);

        if ($commandSuffix !== mb_substr($commandClassName, -$commandSuffixLength)) {
            throw new \RuntimeException(sprintf('Class %s is no %s class', $commandClassName, $commandSuffix));
        }

        return mb_substr($commandClassName, 0, -$commandSuffixLength).$suffix;
    }
}