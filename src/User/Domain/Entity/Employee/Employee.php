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
    public function __construct(
        protected string $email,
        protected string $password,
        protected UserMetadata $metadata,
        protected bool $isActive = false,

        #[ORM\JoinTable(name: 'employee_workplaces')]
        #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'uuid', nullable: false)]
        #[ORM\InverseJoinColumn(name: 'workplace_id', referencedColumnName: 'id', nullable: false)]
        #[ORM\ManyToMany(targetEntity: Workplace::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
        private Collection $workplaces,
    ) {
        parent::__construct($email, $password, $metadata, $isActive);

        $this->workplaces = new ArrayCollection();
    }
}