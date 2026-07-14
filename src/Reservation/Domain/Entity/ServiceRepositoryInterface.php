<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

interface ServiceRepositoryInterface
{
    public function save(Service $service): void;
}
