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
}
