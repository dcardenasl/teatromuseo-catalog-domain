<?php

declare(strict_types=1);

namespace Config;

trait CatalogDomainServices
{
    public static function localizedTranslationStore(bool $getShared = true): \App\Libraries\Localization\LocalizedTranslationStore
    {
        if ($getShared) {
            return static::getSharedInstance('localizedTranslationStore');
        }

        $request = \Config\Services::request();

        return new \App\Libraries\Localization\LocalizedTranslationStore(
            new \App\Models\CatalogTranslationModel(),
            $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request : null
        );
    }

    public static function publicSlugStore(bool $getShared = true): \App\Libraries\Localization\PublicSlugStore
    {
        if ($getShared) {
            return static::getSharedInstance('publicSlugStore');
        }

        $request = \Config\Services::request();

        return new \App\Libraries\Localization\PublicSlugStore(
            new \App\Models\CatalogPublicSlugModel(),
            new \App\Libraries\Localization\SlugGenerator(),
            new \App\Libraries\Localization\RequestLocaleResolver(
                $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request : null
            ),
        );
    }

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
            static::categoryResponseMapper(),
            static::localizedTranslationStore(),
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
            static::techniqueResponseMapper(),
            static::localizedTranslationStore(),
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
            static::collectionItemResponseMapper(),
            static::localizedTranslationStore(),
            static::publicSlugStore(),
        );
    }

    public static function fileUsageService(bool $getShared = true): \App\Services\Catalog\FileUsageService
    {
        if ($getShared) {
            return static::getSharedInstance('fileUsageService');
        }

        return new \App\Services\Catalog\FileUsageService(\Config\Database::connect());
    }
}
