<?php

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        Uuid $id,

        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $reservationDate,

        #[ORM\Column(type: 'string', length: 255)]
        private string $status,

        #[ORM\Column(type: 'uuid', length: 36)]
        private Uuid $serviceId,

        #[ORM\Column(type: 'uuid', length: 36)]
        private Uuid $customerId,

        #[ORM\Column(type: 'uuid', length: 36, nullable: true)]
        private ?Uuid $employeeId,

        #[ORM\Column(type: 'float', nullable: false)]
        private float $servicePrice,

        #[ORM\Column(type: 'float', nullable: false)]
        private float $serviceDuration,

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private ?string $note = null,
    ) {
        $this->id = $id;
    }

    public static function create(
        Uuid $id,
        \DateTimeImmutable $reservationDate,
        Uuid $serviceId,
        Uuid $customerId,
        ?Uuid $employeeId,
        float $servicePrice,
        float $serviceDuration,
        ?string $note = null,
    ): self {
        return new self(
            id: $id,
            reservationDate: $reservationDate,
            status: ReservationStatusEnum::WAITING_FOR_APPROVAL->value,
            serviceId: $serviceId,
            customerId: $customerId,
            employeeId: $employeeId,
            servicePrice: $servicePrice,
            serviceDuration: $serviceDuration,
            note: $note,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getServiceId(): Uuid
    {
        return $this->serviceId;
    }

    public function getCustomerId(): Uuid
    {
        return $this->customerId;
    }

    public function getEmployeeId(): ?Uuid
    {
        return $this->employeeId;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReservationDate(): \DateTimeImmutable
    {
        return $this->reservationDate;
    }

    public function getServicePrice(): float
    {
        return $this->servicePrice;
    }

    public function getServiceDuration(): float
    {
        return $this->serviceDuration;
    }

    public function accept(): void
    {
        $this->status = ReservationStatusEnum::CONFIRMED->value;
    }

    public function cancel(): void
    {
        $this->status = ReservationStatusEnum::CANCELED->value;
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
