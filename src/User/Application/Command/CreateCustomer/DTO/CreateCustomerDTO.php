<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateCustomer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCustomerDTO
{
    public function __construct(
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
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Email]
        public string $email,

        #[Assert\Type('string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $password,

        #[Assert\Type('string')]
        #[Assert\NotBlank(allowNull: true)]
        public ?string $phone,
    ) {
    }
}