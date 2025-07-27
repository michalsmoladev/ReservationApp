<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        private string $street,

        #[ORM\Column(type: 'string', length: 100)]
        private string $city,

        #[ORM\Column(type: 'integer')]
        private int    $apartmentNo,

        #[ORM\Column(type: 'integer')]
        private int    $buildingNo,

        #[ORM\Column(type: 'string', length: 6
        )]
        private string $postCode,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getApartmentNo(): int
    {
        return $this->apartmentNo;
    }

    public function getBuildingNo(): int
    {
        return $this->buildingNo;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}