<?php

declare(strict_types=1);

namespace App\Company\Application\Query\GetCompanyAddresses;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class GetCompanyAddressesValidator
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetCompanyAddressesQuery $query): void
    {
        if (!$this->security->getUser() instanceof Tenant) {
            throw new ValidationFail('[GetCompanyAddresses] Only tenant can list company addresses');
        }
    }
}
