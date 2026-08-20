<?php

declare(strict_types=1);

namespace App\Documentation\Catalog;

use OpenApi\Attributes as OA;

/** OpenAPI definition for the atomic Catalog reorder operation. */
final class SortOrderEndpoints
{
    #[OA\Post(
        path: '/api/v1/catalog/sort-orders',
        tags: ['Catalog'],
        summary: 'Reorder a bounded catalog resource in one operation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CatalogSortOrderBatchRequest'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order saved successfully'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function reorder(): void
    {
    }
}
