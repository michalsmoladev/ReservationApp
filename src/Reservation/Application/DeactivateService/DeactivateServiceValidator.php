<?php

declare(strict_types=1);

namespace App\Reservation\Application\DeactivateService;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\ServiceAccessChecker;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class DeactivateServiceValidator
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ServiceAccessChecker $serviceAccessChecker,
        private readonly Security $security,
    ) {
    }

    public function __invoke(DeactivateServiceCommand $command): void
    {
        $service = $this->serviceRepository->findById($command->serviceId);

        if (!$service) {
            throw new ValidationFail('[DeactivateService] Service not found');
        }

        if (!$service->isActive()) {
            throw new ValidationFail('[DeactivateService] Service is already deactivated');
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[DeactivateService] Authenticated user is required');
        }

        if (!$this->serviceAccessChecker->canManageService($user, $service)) {
            throw new ValidationFail('[DeactivateService] User cannot deactivate this service');
        }
    }
}
