<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CompanyOpeningHour
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

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Company::class)]
        #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
        private Company $company,

        #[ORM\ManyToOne(targetEntity: CompanyAddress::class)]
        #[ORM\JoinColumn(name: 'company_address_id', referencedColumnName: 'id', nullable: true)]
        private ?CompanyAddress $companyAddress,

        #[ORM\Column(type: 'smallint')]
        private int $dayOfWeek,

        #[ORM\Column(type: 'time_immutable', nullable: true)]
        private ?\DateTimeImmutable $opensAt,

        #[ORM\Column(type: 'time_immutable', nullable: true)]
        private ?\DateTimeImmutable $closesAt,

        #[ORM\Column(type: 'boolean')]
        private bool $isClosed,
    ) {
        $this->assertValidDayOfWeek($dayOfWeek);
        $this->assertCompanyAddressBelongsToCompany();
        $this->assertValidOpeningHours();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getCompanyAddress(): ?CompanyAddress
    {
        return $this->companyAddress;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getOpensAt(): ?\DateTimeImmutable
    {
        return $this->opensAt;
    }

    public function getClosesAt(): ?\DateTimeImmutable
    {
        return $this->closesAt;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
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

    private function assertValidDayOfWeek(int $dayOfWeek): void
    {
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            throw new \InvalidArgumentException('dayOfWeek must be between 1 and 7');
        }
    }

    private function assertCompanyAddressBelongsToCompany(): void
    {
        if (
            null !== $this->companyAddress
            && !$this->companyAddress->getCompany()?->getId()->equals($this->company->getId())
        ) {
            throw new \InvalidArgumentException('companyAddress must belong to company');
        }
    }

    private function assertValidOpeningHours(): void
    {
        if ($this->isClosed) {
            if (null !== $this->opensAt || null !== $this->closesAt) {
                throw new \InvalidArgumentException('closed opening hour cannot define opensAt or closesAt');
            }

            return;
        }

        if (null === $this->opensAt || null === $this->closesAt) {
            throw new \InvalidArgumentException('open opening hour must define opensAt and closesAt');
        }

        if ($this->timeToSeconds($this->opensAt) >= $this->timeToSeconds($this->closesAt)) {
            throw new \InvalidArgumentException('opensAt must be earlier than closesAt');
        }
    }

    private function timeToSeconds(\DateTimeImmutable $time): int
    {
        return ((int) $time->format('H') * 3600) + ((int) $time->format('i') * 60) + (int) $time->format('s');
    }
}
