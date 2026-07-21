<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetCustomerById;

use App\User\Application\Exception\CustomerNotFoundException;
use App\User\Application\Query\DTO\CustomerDTO;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use App\User\Domain\Service\CustomerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetCustomerByIdHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerService $customerService,
    ) {
    }

    public function __invoke(GetCustomerByIdQuery $query): CustomerDTO
    {
        /** @var Customer $customer */
        $customer = $this->customerRepository->findById($query->customerId);

        if (!$customer) {
            throw new CustomerNotFoundException();
        }

        return $this->customerService->createCustomerDtoFromCustomer($customer);
    }
}
