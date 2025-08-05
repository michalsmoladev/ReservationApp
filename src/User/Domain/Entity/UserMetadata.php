<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Uid\Uuid;

#[Entity]
#[HasLifecycleCallbacks]
class UserMetadata
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        private string $activationToken,

        #[ORM\Column(type: 'datetime_immutable', length: 255)]
        private \DateTimeImmutable $activationExpiresAt,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getActivationToken(): string
    {
        return $this->activationToken;
    }

    public function setActivationToken(string $activationToken): self
    {
        $this->activationToken = $activationToken;

        return $this;
    }

    public function getActivationExpiresAt(): \DateTimeImmutable
    {
        return $this->activationExpiresAt;
    }

    public function setActivationExpiresAt(\DateTimeImmutable $activationExpiresAt): self
    {
        $this->activationExpiresAt = $activationExpiresAt;

        return $this;
    }
}