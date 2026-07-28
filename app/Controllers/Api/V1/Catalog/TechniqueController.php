<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\TechniqueCreateRequestDTO;
use App\DTO\Request\Catalog\TechniqueIndexRequestDTO;
use App\DTO\Request\Catalog\TechniqueUpdateRequestDTO;
use App\Interfaces\Catalog\TechniqueServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class TechniqueController extends ApiController
{
    protected TechniqueServiceInterface $techniqueService;

    protected function resolveDefaultService(): TechniqueServiceInterface
    {
        $this->techniqueService = Services::techniqueService();

        return $this->techniqueService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (TechniqueIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.technique.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->techniqueService->index($dto, $context);
            },
            TechniqueIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (TechniqueCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.technique.create')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->techniqueService->store($dto, $context);
            },
            TechniqueCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (TechniqueUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.technique.update')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->techniqueService->update($id, $dto, $context);
            },
            TechniqueUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.technique.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->techniqueService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.technique.delete')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->techniqueService->destroy($id, $context);
            }
        );
    }
}
