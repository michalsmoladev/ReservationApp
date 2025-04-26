<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserDto
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
    ) {
    }
}