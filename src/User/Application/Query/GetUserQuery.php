<?php

declare(strict_types=1);

namespace App\User\Application\Query;

readonly class GetUserQuery
{
    public function __construct(
        public string $uuid,
    ) {
    }
}