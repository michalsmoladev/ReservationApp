<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetServices;

use Symfony\Component\Uid\Uuid;

final readonly class GetServicesQuery
{
    public function __construct(
        public ?Uuid $companyId = null,
        public ?Uuid $companyAddressId = null,
    ) {
    }
}
