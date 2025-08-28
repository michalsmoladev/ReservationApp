<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Customer;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserMetadata;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Customer extends User
{
    private const array NON_EDITABLE_PROPERTIES = ['uuid'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = [
        'email',
        'password',
        'roles',
        'isActive',
        'firstname',
        'lastname',
        'phone',
        ];

    public function __construct(
        protected string $email,
        protected string $password,
        protected UserMetadata $metadata,
        protected bool $isActive = false,

        #[ORM\Column(type: 'string', length: 255, nullable: false)]
        private string $firstname,

        #[ORM\Column(type: 'string', length: 255, nullable: false)]
        private string $lastname,

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private ?string $phone = null,
    ) {
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
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