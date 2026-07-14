<?php

declare(strict_types=1);

namespace App\Company\Presentation\Controller;

use App\Company\Application\Command\CreateCompany\CreateCompanyCommand;
use App\Company\Application\Command\CreateCompany\DTO\CreateCompanyDTO;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class CompanyController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route(path: '/api/company', name: 'app_api_company_create', methods: ['POST'])]
    public function createCompanyAction(#[MapRequestPayload] CreateCompanyDTO $companyDTO): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Tenant) {
            return new JsonResponse(data: 'Invalid user', status: Response::HTTP_FORBIDDEN);
        }

        $id = Uuid::v7();
        $command = new CreateCompanyCommand(companyDTO: $companyDTO);
        $command->id = $id;
        $command->tenantId = $user->getUuid();

        $this->commandBus->dispatch($command);

        return new JsonResponse(data: ['id' => $id->toString()], status: JsonResponse::HTTP_CREATED);
    }
}
