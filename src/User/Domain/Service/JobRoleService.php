<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\User\Application\Query\DTO\JobRoleDTO;
use App\User\Domain\Entity\JobRole;

class JobRoleService
{
    public function createDtoFromEntity(JobRole $jobRole): JobRoleDTO
    {
        return new JobRoleDTO(
            name: $jobRole->getName(),
            description: $jobRole->getDescription(),
        );
    }
}