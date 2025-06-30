<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    private const array NON_EDITABLE_PROPERTIES = ['uuid'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['email', 'password', 'roles', 'isActive'];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $uuid;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        private string $email,

        #[ORM\Column(type: 'string', length: 255)]
        private string $password,

        #[ORM\OneToOne(targetEntity: UserMetadata::class, cascade: ['persist', 'remove'])]
        #[ORM\JoinColumn(referencedColumnName: 'id')]
        private UserMetadata $metadata,

        #[ORM\Column(type: 'boolean', nullable: false, )]
        private bool $isActive = false,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function markAsActive(): self
    {
        $this->isActive = true;

        return $this;
    }

    public function markAsInactive(): self
    {
        $this->isActive = false;

        return $this;
    }

    public function getMetadata(): UserMetadata
    {
        return $this->metadata;
    }

    public function setMetadata(UserMetadata $metadata): void
    {
        $this->metadata = $metadata;
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