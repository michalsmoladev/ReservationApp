<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Employee;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserMetadata;
use App\User\Domain\Entity\Workplace;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Employee extends User
{
    private const array NON_EDITABLE_PROPERTIES = ['uuid'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['email', 'password', 'roles', 'isActive'];

    #[ORM\JoinTable(name: 'employee_workplaces')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'uuid', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'workplace_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToMany(targetEntity: Workplace::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $workplaces;

    public function __construct(
        protected string $email,
        protected string $password,
        protected UserMetadata $metadata,
        protected bool $isActive = false,
    ) {
        $this->workplaces = new ArrayCollection();
    }

    public function addWorkplace(Workplace $workplace): void
    {
        if (!$this->workplaces->contains($workplace)) {
            $this->workplaces->add($workplace);
        }
    }

    public function removeWorkplace(Workplace $workplace): void
    {
        $this->workplaces->removeElement($workplace);
    }

    public function getWorkplaces(): Collection
    {
        return $this->workplaces;
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