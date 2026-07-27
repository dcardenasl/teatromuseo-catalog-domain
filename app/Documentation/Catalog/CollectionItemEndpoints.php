<?php

declare(strict_types=1);

namespace App\Documentation\Catalog;

use OpenApi\Attributes as OA;

/**
 * OpenAPI definitions for CollectionItem endpoints.
 *
 * @OA\Tag(name="Catalog", description="Catalog management")
 */
class CollectionItemEndpoints
{
    #[OA\Get(
        path: '/api/v1/catalog/collection-items',
        tags: ['Catalog'],
        summary: 'List CollectionItems',
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
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Post(
        path: '/api/v1/catalog/collection-items',
        tags: ['Catalog'],
        summary: 'Create new CollectionItem',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CollectionItemCreateRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/catalog/collection-items/{id}',
        tags: ['Catalog'],
        summary: 'Get CollectionItem by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Found',
                content: new OA\JsonContent(ref: '#/components/schemas/CollectionItemResponse')
            ),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Put(
        path: '/api/v1/catalog/collection-items/{id}',
        tags: ['Catalog'],
        summary: 'Update existing CollectionItem',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CollectionItemUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/CollectionItemResponse')
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/v1/catalog/collection-items/{id}',
        tags: ['Catalog'],
        summary: 'Delete CollectionItem by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted successfully'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function delete(): void
    {
    }
}
