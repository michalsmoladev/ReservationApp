<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\User\Domain\Entity\Employee\Employee;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class EmployeeWorkingHour
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

        #[ORM\Column(type: 'smallint')]
        private int $dayOfWeek,

        #[ORM\Column(type: 'time_immutable')]
        private \DateTimeImmutable $startsAt,

        #[ORM\Column(type: 'time_immutable')]
        private \DateTimeImmutable $endsAt,
    ) {
        $this->assertValidDayOfWeek($dayOfWeek);
        $this->assertValidWorkingHours();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
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

    private function assertValidWorkingHours(): void
    {
        if ($this->timeToSeconds($this->startsAt) >= $this->timeToSeconds($this->endsAt)) {
            throw new \InvalidArgumentException('startsAt must be earlier than endsAt');
        }
    }

    private function timeToSeconds(\DateTimeImmutable $time): int
    {
        return ((int) $time->format('H') * 3600) + ((int) $time->format('i') * 60) + (int) $time->format('s');
    }
}
