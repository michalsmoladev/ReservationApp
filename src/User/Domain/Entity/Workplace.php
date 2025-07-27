<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Workplace
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO' )]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 100)]
        private string $name,

        #[ORM\Column(type: 'string', length: 255)]
        private string $description,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}