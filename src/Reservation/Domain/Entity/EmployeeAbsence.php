<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\User\Domain\Entity\Employee\Employee;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class EmployeeAbsence
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
        #[ORM\ManyToOne(targetEntity: Employee::class)]
        #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'uuid', nullable: false)]
        private Employee $employee,

        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $startsAt,

        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $endsAt,

        #[ORM\Column(type: 'string', length: 255)]
        private string $reason,
    ) {
        $this->assertValidAbsenceRange();
        $this->assertValidReason();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getReason(): string
    {
        return $this->reason;
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

    private function assertValidAbsenceRange(): void
    {
        if ($this->startsAt >= $this->endsAt) {
            throw new \InvalidArgumentException('startsAt must be earlier than endsAt');
        }
    }

    private function assertValidReason(): void
    {
        if ('' === trim($this->reason)) {
            throw new \InvalidArgumentException('reason cannot be blank');
        }
    }
}
