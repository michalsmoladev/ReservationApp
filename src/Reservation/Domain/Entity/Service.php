<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\User\Domain\Entity\Employee\Employee;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Service
{
    private const array NON_EDITABLE_PROPERTIES = ['id'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['name', 'description', 'duration', 'price'];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\JoinTable(name: 'service_employee')]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'employee_id', referencedColumnName: 'uuid', nullable: false)]
    #[ORM\ManyToMany(targetEntity: Employee::class, inversedBy: 'services')]
    private Collection $employees;

    public function __construct(
        #[ORM\Column(type: 'string', length: 120)]
        private string $name,

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private ?string $description,

        #[ORM\Column(type: 'float')]
        private float $duration,

        #[ORM\Column(type: 'float')]
        private float $price,

        #[ORM\ManyToOne(targetEntity: Company::class)]
        #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
        private Company $company,

        #[ORM\ManyToOne(targetEntity: CompanyAddress::class)]
        #[ORM\JoinColumn(name: 'company_address_id', referencedColumnName: 'id', nullable: false)]
        private CompanyAddress $companyAddress,
    ) {
        $this->employees = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getCompanyAddress(): CompanyAddress
    {
        return $this->companyAddress;
    }

    public function addEmployee(Employee $employee): void
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
        }
    }

    public function removeEmployee(Employee $employee): void
    {
        $this->employees->removeElement($employee);
    }

    public function getEmployees(): Collection
    {
        return $this->employees;
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
