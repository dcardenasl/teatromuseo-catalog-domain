<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\SortOrderBatchRequestDTO;
use App\Services\Catalog\SortOrderBatchService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

final class SortOrderController extends ApiController
{
    protected SortOrderBatchService $sortOrderService;

    protected function resolveDefaultService(): SortOrderBatchService
    {
        $this->sortOrderService = Services::sortOrderBatchService();

        return $this->sortOrderService;
    }

    public function reorder(): ResponseInterface
    {
        return $this->handleRequest(
            function (SortOrderBatchRequestDTO $dto, SecurityContext $context): array {
                $permission = $dto->resource === 'categories'
                    ? 'catalog.category.update'
                    : 'catalog.technique.update';

                if (! $context->hasPermission($permission)) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }

                return $this->sortOrderService->reorder($dto);
            },
            SortOrderBatchRequestDTO::class,
        );
    }
}
