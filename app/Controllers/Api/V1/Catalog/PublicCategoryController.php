<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\CategoryIndexRequestDTO;
use App\Interfaces\Catalog\CategoryServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicCategoryController extends ApiController
{
    protected CategoryServiceInterface $categoryService;

    protected function resolveDefaultService(): CategoryServiceInterface
    {
        $this->categoryService = Services::categoryService();
        return $this->categoryService;
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryIndexRequestDTO $dto, SecurityContext $context): mixed {
                return $this->categoryService->index($dto, $context);
            },
            CategoryIndexRequestDTO::class
        );
    }
}
