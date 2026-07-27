<?php

declare(strict_types=1);

namespace Config;

trait CatalogDomainServices
{
    public static function categoryResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('categoryResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Catalog\CategoryResponseDTO::class
        );
    }

    public static function categoryService(bool $getShared = true): \App\Interfaces\Catalog\CategoryServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('categoryService');
        }

        return new \App\Services\Catalog\CategoryService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CategoryModel::class)),
            static::categoryResponseMapper()
        );
    }

    public static function techniqueResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('techniqueResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Catalog\TechniqueResponseDTO::class
        );
    }

    public static function techniqueService(bool $getShared = true): \App\Interfaces\Catalog\TechniqueServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('techniqueService');
        }

        return new \App\Services\Catalog\TechniqueService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\TechniqueModel::class)),
            static::techniqueResponseMapper()
        );
    }

    public static function collectionItemResponseMapper(bool $getShared = true): \dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface
    {
        if ($getShared) {
            return static::getSharedInstance('collectionItemResponseMapper');
        }

        return new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Catalog\CollectionItemResponseDTO::class
        );
    }

    public static function collectionItemService(bool $getShared = true): \App\Interfaces\Catalog\CollectionItemServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('collectionItemService');
        }

        return new \App\Services\Catalog\CollectionItemService(
            new \dcardenasl\Ci4ApiCore\Repositories\GenericRepository(model(\App\Models\CollectionItemModel::class)),
            static::collectionItemResponseMapper()
        );
    }
}
