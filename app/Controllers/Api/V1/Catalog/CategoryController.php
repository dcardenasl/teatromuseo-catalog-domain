<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\CategoryCreateRequestDTO;
use App\DTO\Request\Catalog\CategoryIndexRequestDTO;
use App\DTO\Request\Catalog\CategoryUpdateRequestDTO;
use App\Interfaces\Catalog\CategoryServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class CategoryController extends ApiController
{
    protected CategoryServiceInterface $categoryService;

    protected function resolveDefaultService(): CategoryServiceInterface
    {
        $this->categoryService = Services::categoryService();

        return $this->categoryService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('catalog.category.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->index($dto, $context);
            },
            CategoryIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('catalog.category.create')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->store($dto, $context);
            },
            CategoryCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('catalog.category.update')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->update($id, $dto, $context);
            },
            CategoryUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('catalog.category.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('catalog.category.delete')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->destroy($id, $context);
            }
        );
    }
}
