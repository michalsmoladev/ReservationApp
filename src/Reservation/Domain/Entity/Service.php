<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Service
{
    private const array NON_EDITABLE_PROPERTIES = ['id'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['name', 'description', 'duration', 'price'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\Column(type: 'string', length: 120)]
        private string $name,

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private string $description,

        #[ORM\Column(type: 'float')]
        private float $duration,

        #[ORM\Column(type: 'float')]
        private float $price,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function update(array $properties): self
    {
        foreach ($properties as $property => $propertyValue) {
            if (
                $propertyValue
                && !\in_array($property, self::NON_EDITABLE_PROPERTIES, true)
                || \in_array($property, self::AVAILABLE_PROPERTIES_WITH_NULL_VALUE, true)
            ) {
                $this->{$property} = $propertyValue;
            }
        }

        return $this;
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