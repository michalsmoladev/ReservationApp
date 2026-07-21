<?php

declare(strict_types=1);

namespace App\Company\Domain\Entity;

use App\Company\Domain\Entity\Address\CompanyAddress;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Company
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    #[ORM\OneToMany(
        targetEntity: CompanyAddress::class,
        mappedBy: 'company',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $addresses;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        #[ORM\Column(type: 'string', length: 128)]
        private string $displayName,
        #[ORM\Column(type: 'string', length: 128)]
        private string $legalName,
        #[ORM\Column(type: 'string', length: 30)]
        private string $taxId,
        #[ORM\Column(type: 'string', length: 3)]
        private string $currency,
    ) {
        $this->addresses = new ArrayCollection();
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

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function getTaxId(): string
    {
        return $this->taxId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function update(string $displayName, string $legalName, string $taxId, string $currency): self
    {
        $this->displayName = $displayName;
        $this->legalName = $legalName;
        $this->taxId = $taxId;
        $this->currency = $currency;

        return $this;
    }

    public function addAddress(CompanyAddress $address): void
    {
        if (!$this->addresses->contains($address)) {
            $address->setCompany($this);
            $this->addresses->add($address);
        }
    }

    public function removeAddress(CompanyAddress $address): void
    {
        $address->removeCompany();
        $this->addresses->removeElement($address);
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
