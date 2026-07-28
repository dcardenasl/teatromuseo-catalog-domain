<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\CollectionItemCreateRequestDTO;
use App\DTO\Request\Catalog\CollectionItemIndexRequestDTO;
use App\DTO\Request\Catalog\CollectionItemUpdateRequestDTO;
use App\Interfaces\Catalog\CollectionItemServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class CollectionItemController extends ApiController
{
    protected CollectionItemServiceInterface $collectionItemService;

    protected function resolveDefaultService(): CollectionItemServiceInterface
    {
        $this->collectionItemService = Services::collectionItemService();

        return $this->collectionItemService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionItemIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.collectionItem.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionItemService->index($dto, $context);
            },
            CollectionItemIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionItemCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.collectionItem.create')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionItemService->store($dto, $context);
            },
            CollectionItemCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionItemUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.collectionItem.update')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionItemService->update($id, $dto, $context);
            },
            CollectionItemUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.collectionItem.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionItemService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.collectionItem.delete')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionItemService->destroy($id, $context);
            }
        );
    }
}
