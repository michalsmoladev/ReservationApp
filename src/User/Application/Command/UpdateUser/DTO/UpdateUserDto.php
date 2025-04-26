<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserDto
{
    public string $uuid;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(min: 3, max: 255)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public string $password,

        #[Assert\NotBlank]
        public array $roles,
    ) {
    }
}