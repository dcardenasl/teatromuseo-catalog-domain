<?php

declare(strict_types=1);

namespace App\Documentation\Catalog;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definitions for the public (web app key) Catalog endpoints.
 */
class PublicCatalogEndpoints
{
    #[OA\Get(
        path: '/api/v1/public/catalog/collection-items/{idOrSlug}',
        tags: ['Public Catalog'],
        summary: 'Get an active collection item by id, inventory code, or per-locale routing slug',
        description: 'Slug resolution prefers the Accept-Language locale and falls back to any locale, so shared URLs keep working across languages.',
        security: [['appKeyAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'idOrSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Found',
                content: new OA\JsonContent(ref: '#/components/schemas/CollectionItemResponse')
            ),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
            new OA\Response(response: 404, description: 'Not found or inactive'),
        ]
    )]
    public function show(): void
    {
    }
}
