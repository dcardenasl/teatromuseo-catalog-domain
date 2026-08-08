<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\CollectionItemIndexRequestDTO;
use App\Interfaces\Catalog\CollectionItemServiceInterface;
use App\Services\Catalog\CollectionItemMediaResolutionService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;
use dcardenasl\Ci4ApiCore\Traits\SparseFieldsetTrait;

class PublicCollectionItemController extends ApiController
{
    use SparseFieldsetTrait;

    private const LISTING_FIELDS = [
        'id', 'uuid', 'name', 'slug', 'slugs', 'category_id', 'cover_file_id',
        'cover_url', 'localized', 'summary', 'period', 'creator', 'origin',
    ];

    private const DETAIL_FIELDS = [
        'id', 'uuid', 'name', 'slug', 'slugs', 'category_id', 'cover_file_id',
        'cover_url', 'gallery_file_ids', 'localized', 'summary', 'description',
        'content', 'period', 'creator', 'origin', 'techniques', 'materials',
        'translations', 'created_at', 'updated_at',
    ];

    protected CollectionItemServiceInterface $collectionItemService;

    protected CollectionItemMediaResolutionService $mediaResolutionService;

    protected function resolveDefaultService(): CollectionItemServiceInterface
    {
        $this->collectionItemService = Services::collectionItemService();
        $this->mediaResolutionService = Services::collectionItemMediaResolutionService();

        return $this->collectionItemService;
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionItemIndexRequestDTO $dto, SecurityContext $context): mixed {
                $fields = $this->parseFieldsParam(self::LISTING_FIELDS);
                $publicDto = Services::requestDtoFactory()->make(
                    CollectionItemIndexRequestDTO::class,
                    array_merge($dto->toArray(), [
                        'filter' => array_merge($dto->filter, ['is_active' => '1']),
                    ])
                );
                $result = $this->collectionItemService->index($publicDto, $context)->toArray();
                foreach ($result['data'] as $key => $item) {
                    $itemArray = $item instanceof DataTransferObjectInterface ? $item->toArray() : (array) $item;
                    $resolved = $this->mediaResolutionService->resolveMediaFields($itemArray);
                    $result['data'][$key] = $this->sparseFilter($resolved, $fields);
                }
                return $result;
            },
            CollectionItemIndexRequestDTO::class
        );
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
