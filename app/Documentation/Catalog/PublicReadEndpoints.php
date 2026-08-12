<?php

declare(strict_types=1);

namespace App\Documentation\Catalog;

use OpenApi\Attributes as OA;

/** OpenAPI contract for the versioned Catalog PublicRead surface. */
final class PublicReadEndpoints
{
    #[OA\Get(
        path: '/api/v1/public-read/{locale}/collection-items',
        tags: ['Public Read - Catalog'],
        summary: 'List active collection items',
        security: [['appKeyAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 10000)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 120)),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 120)),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'technique', in: 'query', description: 'Comma-separated non-deleted technique slugs; matches any slug', schema: new OA\Schema(type: 'string', maxLength: 500)),
            new OA\Parameter(name: 'technique_id', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Ascending order; ties are resolved by id ascending', schema: new OA\Schema(type: 'string', enum: ['name', 'created_at', 'id'])),
            new OA\Parameter(name: 'fields', in: 'query', description: 'Comma-separated sparse top-level fields. Omit for the complete projection.', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PublicRead envelope',
                content: new OA\JsonContent(ref: '#/components/schemas/PublicReadEnvelope'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
            new OA\Response(response: 422, description: 'Invalid query'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/public-read/{locale}/collection-items/{idOrSlug}',
        tags: ['Public Read - Catalog'],
        summary: 'Get one active collection item',
        security: [['appKeyAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'idOrSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'fields', in: 'query', description: 'Comma-separated sparse top-level fields, including category and techniques relations.', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PublicRead envelope',
                content: new OA\JsonContent(ref: '#/components/schemas/PublicReadEnvelope'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid X-App-Key'),
            new OA\Response(response: 404, description: 'Not found or inactive'),
        ]
    )]
    public function show(): void
    {
    }
}
