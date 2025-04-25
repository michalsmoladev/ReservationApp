<?php

declare(strict_types=1);

namespace App\Core\Application\MessageBus\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class ValidationFail extends UnrecoverableMessageHandlingException implements HttpExceptionInterface
{

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
