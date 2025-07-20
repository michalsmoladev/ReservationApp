<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Customer;

use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Customer extends User
{

}