<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

enum UserType: string
{
    case EMPLOYEE = 'employee';
    case CUSTOMER = 'customer';
}
