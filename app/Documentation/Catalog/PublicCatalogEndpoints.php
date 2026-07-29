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
        path: '/api/v1/public/catalog/collection-items',
        tags: ['Public Catalog'],
        summary: 'List active collection items for the public site',
        description: 'Requires the X-App-Key header bound to the web application. Localized content follows Accept-Language.',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CollectionItemResponse')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/public/catalog/collection-items/{idOrSlug}',
        tags: ['Public Catalog'],
        summary: 'Get an active collection item by id, inventory code, or per-locale routing slug',
        description: 'Slug resolution prefers the Accept-Language locale and falls back to any locale, so shared URLs keep working across languages.',
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
