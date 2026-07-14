<?php

namespace App\User\Domain\Entity\Tenant;

use App\Company\Domain\Entity\Company;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class Tenant extends User
{
    private const array NON_EDITABLE_PROPERTIES = ['uuid'];
    private const array AVAILABLE_PROPERTIES_WITH_NULL_VALUE = ['email', 'password', 'roles', 'isActive'];

    #[JoinTable(name: 'tenant_company')]
    #[JoinColumn(name: 'tenant_id', referencedColumnName: 'uuid')]
    #[InverseJoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: Company::class)]
    private Collection $companies;

    public function __construct(
        protected string $email,
        protected string $password,
        protected UserMetadata $metadata,
        protected bool $isActive,
        protected string $firstname,
        protected string $lastname,
    ) {
        $this->companies = new ArrayCollection();
    }

    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): void
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
        }
    }

    public function removeCompany(Company $company): void
    {
        $this->companies->removeElement($company);
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
