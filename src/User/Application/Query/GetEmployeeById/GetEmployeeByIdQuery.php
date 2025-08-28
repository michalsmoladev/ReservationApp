<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetEmployeeById;

use Symfony\Component\Uid\Uuid;

final readonly class GetEmployeeByIdQuery
{
    public function __construct(
        public Uuid $employeeId,
    ) {
    }
}