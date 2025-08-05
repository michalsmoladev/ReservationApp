<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Employee;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserMetadata;
use App\User\Domain\Entity\JobRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Employee extends User
{
    private const array NON_EDITABLE_PROPERTIES = ['uuid'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['email', 'password', 'roles', 'isActive'];

    #[ORM\JoinTable(name: 'employee_jobrole')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'uuid', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'jobrole_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToMany(targetEntity: JobRole::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $jobRoles;

    public function __construct(
        protected string $email,
        protected string $password,
        protected UserMetadata $metadata,
        protected bool $isActive = false,
    ) {
        $this->jobRoles = new ArrayCollection();
    }

    public function addJobRole(JobRole $workplace): void
    {
        if (!$this->jobRoles->contains($workplace)) {
            $this->jobRoles->add($workplace);
        }
    }

    public function removeJobRole(JobRole $workplace): void
    {
        $this->jobRoles->removeElement($workplace);
    }

    public function getJobRoles(): Collection
    {
        return $this->jobRoles;
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