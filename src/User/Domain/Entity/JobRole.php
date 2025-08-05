<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class JobRole
{
    private const array NON_EDITABLE_PROPERTIES = ['id'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['name', 'description'];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO' )]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 100)]
        private string $name,

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private ?string $description,
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

    public function getDescription(): ?string
    {
        return $this->description;
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
}