<?php

namespace App\User\Application\Command\CreateTenant\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTenantDTO
{
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Type('string')]
        public string $password,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $firstname,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $lastname,
    ) {
    }
}