<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ServiceController extends AbstractController
{
    public function __construct(

    ) {
    }

    #[Route(path: '/api/service', name: 'app_api_service_create', methods: ['POST'])]
    public function createServiceAction(): JsonResponse
    {

    }
}