<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\Interfaces\Catalog\CollectionItemServiceInterface;
use App\Services\Catalog\CollectionItemMediaResolutionService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;
use dcardenasl\Ci4ApiCore\Traits\SparseFieldsetTrait;

class PublicCollectionItemController extends ApiController
{
    use SparseFieldsetTrait;

    private const DETAIL_FIELDS = [
        'id', 'uuid', 'name', 'slug', 'slugs', 'category_id', 'cover_file_id',
        'cover_url', 'gallery_file_ids', 'localized', 'summary', 'description',
        'content', 'period', 'creator', 'origin', 'techniques', 'materials',
        'translations', 'status', 'created_at', 'updated_at',
    ];

    protected CollectionItemServiceInterface $collectionItemService;

    protected CollectionItemMediaResolutionService $mediaResolutionService;

    protected function resolveDefaultService(): CollectionItemServiceInterface
    {
        $this->collectionItemService = Services::collectionItemService();
        $this->mediaResolutionService = Services::collectionItemMediaResolutionService();

        return $this->collectionItemService;
    }

    public function show(string $idOrCode): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($idOrCode): mixed {
                $fields = $this->parseFieldsParam(self::DETAIL_FIELDS);
                $data = $this->collectionItemService->getPublicActive($idOrCode);
                $resolved = $this->mediaResolutionService->resolveMediaFields($data);
                return $this->sparseFilter($resolved, $fields);
            }
        );
    }
}
