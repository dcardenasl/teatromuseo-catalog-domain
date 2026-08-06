<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\CollectionItemIndexRequestDTO;
use App\Interfaces\Catalog\CollectionItemServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicCollectionItemController extends ApiController
{
    protected CollectionItemServiceInterface $collectionItemService;

    protected function resolveDefaultService(): CollectionItemServiceInterface
    {
        $this->collectionItemService = Services::collectionItemService();
        return $this->collectionItemService;
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionItemIndexRequestDTO $dto, SecurityContext $context): mixed {
                $publicDto = Services::requestDtoFactory()->make(
                    CollectionItemIndexRequestDTO::class,
                    array_merge($dto->toArray(), [
                        'filter' => array_merge($dto->filter, ['is_active' => '1']),
                    ])
                );
                $result = $this->collectionItemService->index($publicDto, $context)->toArray();
                foreach ($result['data'] as $key => $item) {
                    $itemArray = $item instanceof DataTransferObjectInterface ? $item->toArray() : (array) $item;
                    $result['data'][$key] = $this->resolveMediaFields($itemArray);
                }
                return $result;
            },
            CollectionItemIndexRequestDTO::class
        );
    }

    public function show(string $idOrCode): ResponseInterface
    {
        return $this->handleRequest(
            function (mixed $_, SecurityContext $context) use ($idOrCode): mixed {
                $data = $this->collectionItemService->getPublicActive($idOrCode);
                return $this->resolveMediaFields($data);
            }
        );
    }

    /**
     * Helper to resolve cover image and gallery file IDs to Hub file metadata.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function resolveMediaFields(array $item): array
    {
        $hub = Services::hubClient();

        $fileIds = [];
        if (isset($item['cover_file_id']) && (int) $item['cover_file_id'] > 0) {
            $fileIds[] = (int) $item['cover_file_id'];
        }

        $galleryIds = [];
        if (isset($item['gallery_file_ids']) && is_string($item['gallery_file_ids']) && trim($item['gallery_file_ids']) !== '') {
            $rawIds = explode(',', $item['gallery_file_ids']);
            foreach ($rawIds as $rawId) {
                $id = (int) trim($rawId);
                if ($id > 0) {
                    $fileIds[] = $id;
                    $galleryIds[] = $id;
                }
            }
        }

        $metaMap = [];
        if (!empty($fileIds)) {
            $metaMap = $hub->resolvePublicFileMeta($fileIds);
        }

        $item['cover_image'] = null;
        if (isset($item['cover_file_id']) && (int) $item['cover_file_id'] > 0) {
            $fileId = (int) $item['cover_file_id'];
            $meta = $metaMap[$fileId] ?? null;
            if ($meta) {
                $item['cover_image'] = [
                    'source_kind' => 'hub_file',
                    'file_id'     => $fileId,
                    'url'         => $meta['url'] ?? null,
                    'variants'    => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
                ];
            }
        }

        $gallery = [];
        foreach ($galleryIds as $fileId) {
            $meta = $metaMap[$fileId] ?? null;
            if ($meta) {
                $gallery[] = [
                    'source_kind' => 'hub_file',
                    'file_id'     => $fileId,
                    'url'         => $meta['url'] ?? null,
                    'variants'    => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
                ];
            }
        }
        $item['gallery_images'] = $gallery;

        return $item;
    }
}
