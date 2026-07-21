<?php

declare(strict_types=1);

namespace App\Company\Presentation\Controller;

use App\Company\Application\Command\CreateCompany\CreateCompanyCommand;
use App\Company\Application\Command\CreateCompany\DTO\CreateCompanyDTO;
use App\Company\Application\Command\CreateCompanyAddress\CreateCompanyAddressCommand;
use App\Company\Application\Command\CreateCompanyAddress\DTO\CreateCompanyAddressDTO;
use App\Company\Application\Command\DeleteCompanyAddress\DeleteCompanyAddressCommand;
use App\Company\Application\Command\UpdateCompanyAddress\DTO\UpdateCompanyAddressDTO;
use App\Company\Application\Command\UpdateCompanyAddress\UpdateCompanyAddressCommand;
use App\Company\Application\Query\GetCompanyAddresses\GetCompanyAddressesQuery;
use App\Company\Application\Command\UpdateCompany\DTO\UpdateCompanyDTO;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyCommand;
use App\Company\Application\Query\GetCompanies\GetCompaniesQuery;
use App\Company\Application\Query\GetCompanyById\GetCompanyByIdQuery;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class CompanyController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
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

    #[Route(path: '/api/company/{id}', name: 'app_api_company_show', methods: ['GET'])]
    public function showCompanyAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetCompanyByIdQuery(companyId: Uuid::fromString($id))
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/companies', name: 'app_api_company_list', methods: ['GET'])]
    public function listCompaniesAction(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetCompaniesQuery());

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/company/{id}', name: 'app_api_company_update', methods: ['PATCH'])]
    public function updateCompanyAction(string $id, #[MapRequestPayload] UpdateCompanyDTO $updateCompanyDTO): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateCompanyCommand(
                companyId: Uuid::fromString($id),
                updateCompanyDTO: $updateCompanyDTO,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/company/{id}/addresses', name: 'app_api_company_address_list', methods: ['GET'])]
    public function listCompanyAddressesAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetCompanyAddressesQuery(companyId: Uuid::fromString($id))
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/company/{id}/address', name: 'app_api_company_address_create', methods: ['POST'])]
    public function createCompanyAddressAction(string $id, #[MapRequestPayload] CreateCompanyAddressDTO $createCompanyAddressDTO): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $addressId = Uuid::v7();

        $this->commandBus->dispatch(
            new CreateCompanyAddressCommand(
                companyId: Uuid::fromString($id),
                addressId: $addressId,
                createCompanyAddressDTO: $createCompanyAddressDTO,
            )
        );

        return new JsonResponse(data: ['id' => $addressId->toString()], status: Response::HTTP_CREATED);
    }

    #[Route(path: '/api/company/address/{id}', name: 'app_api_company_address_update', methods: ['PATCH'])]
    public function updateCompanyAddressAction(string $id, #[MapRequestPayload] UpdateCompanyAddressDTO $updateCompanyAddressDTO): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateCompanyAddressCommand(
                companyAddressId: Uuid::fromString($id),
                updateCompanyAddressDTO: $updateCompanyAddressDTO,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/company/address/{id}', name: 'app_api_company_address_delete', methods: ['DELETE'])]
    public function deleteCompanyAddressAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new DeleteCompanyAddressCommand(
                companyAddressId: Uuid::fromString($id),
            )
        );

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
