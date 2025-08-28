<?php

declare(strict_types=1);

namespace App\Company\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Company
{

    private Uuid $id;
}