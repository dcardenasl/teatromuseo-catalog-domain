<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\TechniqueIndexRequestDTO;
use App\Interfaces\Catalog\TechniqueServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicTechniqueController extends ApiController
{
    protected TechniqueServiceInterface $techniqueService;

    protected function resolveDefaultService(): TechniqueServiceInterface
    {
        $this->techniqueService = Services::techniqueService();
        return $this->techniqueService;
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (TechniqueIndexRequestDTO $dto, SecurityContext $context): mixed {
                return $this->techniqueService->index($dto, $context);
            },
            TechniqueIndexRequestDTO::class
        );
    }

    public function show(string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($idOrSlug): mixed {
                return $this->techniqueService->getPublic($idOrSlug);
            }
        );
    }
}
