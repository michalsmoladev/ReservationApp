<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateEmployeeDTO
{
    public function __construct(
        #[Assert\Email]
        #[Assert\NotBlank]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $password,

        #[Assert\Type('array')]
        public array $roles,

        #[Assert\Type('boolean')]
        public bool $isActive,
    ) {
    }
}