<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetServiceById;

use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\Exception\ServiceNotFoundException;
use App\Reservation\Application\Query\DTO\ServiceDetailsDTO;
use App\Reservation\Application\Query\ServiceQueryDataProvider;
use App\Reservation\Application\ServiceAccessChecker;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetServiceByIdHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ServiceAccessChecker $serviceAccessChecker,
        private readonly ServiceQueryDataProvider $serviceQueryDataProvider,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetServiceByIdQuery $query): ServiceDetailsDTO
    {
        $service = $this->serviceRepository->findById($query->serviceId);

        if (!$service) {
            throw new ServiceNotFoundException();
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[GetServiceById] Authenticated user is required');
        }

        if (!$this->serviceAccessChecker->canManageService($user, $service)) {
            throw new ServiceNotFoundException();
        }

        return $this->serviceQueryDataProvider->mapServiceToDto($service);
    }
}
