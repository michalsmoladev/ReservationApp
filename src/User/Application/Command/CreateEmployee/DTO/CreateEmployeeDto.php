<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateEmployeeDto
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

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public string $companyId,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public string $companyAddressId,
    ) {
    }
}
