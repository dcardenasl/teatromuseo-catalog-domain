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

class PublicCollectionItemController extends ApiController
{
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
                $publicDto = Services::requestDtoFactory()->make(
                    CollectionItemIndexRequestDTO::class,
                    array_merge($dto->toArray(), [
                        'filter' => array_merge($dto->filter, ['is_active' => '1']),
                    ])
                );
                $result = $this->collectionItemService->index($publicDto, $context)->toArray();
                foreach ($result['data'] as $key => $item) {
                    $itemArray = $item instanceof DataTransferObjectInterface ? $item->toArray() : (array) $item;
                    $result['data'][$key] = $this->mediaResolutionService->resolveMediaFields($itemArray);
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
                $data = $this->collectionItemService->getPublicActive($idOrCode);
                return $this->mediaResolutionService->resolveMediaFields($data);
            }
        );
    }
}
