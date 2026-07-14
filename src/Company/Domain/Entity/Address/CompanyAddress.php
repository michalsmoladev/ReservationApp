<?php

declare(strict_types=1);

namespace App\Company\Domain\Entity\Address;

use App\Company\Domain\Entity\Company;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CompanyAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    public function __construct(
        #[ORM\Column(type: 'string', length: 150)]
        private string $street,
        #[ORM\Column(type: 'string', length: 150)]
        private string $city,
        #[ORM\Column(type: 'string', length: 150)]
        private string $country,
        #[ORM\Column(type: 'string', length: 6)]
        private string $postalCode,
        #[ORM\Column(type: 'integer')]
        private int $apartmentNo,
        #[ORM\Column(type: 'integer')]
        private int $buildingNo,
        #[ORM\Column(type: 'string', length: 150, nullable: true)]
        private ?string $name,
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function removeCompany(): void
    {
        $this->company = null;
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
