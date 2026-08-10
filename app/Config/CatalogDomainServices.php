<?php

declare(strict_types=1);

namespace Config;

trait CatalogDomainServices
{
    public static function publicReadCollectionItemReader(bool $getShared = true): \App\Interfaces\Catalog\PublicReadCollectionItemReaderInterface
    {
        if ($getShared) {
            return static::getSharedInstance('publicReadCollectionItemReader');
        }

        return new \App\Services\Catalog\PublicReadCollectionItemReader(
            \Config\Database::connect(),
            static::hubClient(),
            (string) config('Localization')->legacyFallbackLocale,
        );
    }

    /**
     * Shared per-request locale resolver.
     *
     * Both localization stores take the same instance so the Accept-Language /
     * locale header is parsed once per service graph.
     */
    public static function requestLocaleResolver(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver
    {
        if ($getShared) {
            return static::getSharedInstance('requestLocaleResolver');
        }

        // Feature tests and HTTP requests may already have an IncomingRequest
        // registered under the shared key. RequestLocaleResolver only needs
        // the request shape, so construct the ApiRequest explicitly here and
        // avoid the typed shared-service collision.
        $request = \Config\Services::request(false);

        return new \dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver(
            $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request : null
        );
    }

    public static function localizedTranslationStore(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore
    {
        if ($getShared) {
            return static::getSharedInstance('localizedTranslationStore');
        }

        return new \dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore(
            new \App\Models\CatalogTranslationModel(),
            static::requestLocaleResolver(),
            config('Localization'),
        );
    }

    public static function publicSlugStore(bool $getShared = true): \dcardenasl\Ci4ApiCore\Localization\PublicSlugStore
    {
        if ($getShared) {
            return static::getSharedInstance('publicSlugStore');
        }

        return new \dcardenasl\Ci4ApiCore\Localization\PublicSlugStore(
            new \App\Models\CatalogPublicSlugModel(),
            new \dcardenasl\Ci4ApiCore\Localization\SlugGenerator(),
            static::requestLocaleResolver(),
            config('Localization'),
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
            static::publicCacheInvalidationNotifier(),
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
            static::publicCacheInvalidationNotifier(),
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
            static::publicCacheInvalidationNotifier(),
        );
    }

    public static function fileUsageService(bool $getShared = true): \App\Services\Catalog\FileUsageService
    {
        if ($getShared) {
            return static::getSharedInstance('fileUsageService');
        }

        return new \App\Services\Catalog\FileUsageService(model(\App\Models\CollectionItemModel::class));
    }

    public static function collectionItemMediaResolutionService(bool $getShared = true): \App\Services\Catalog\CollectionItemMediaResolutionService
    {
        if ($getShared) {
            return static::getSharedInstance('collectionItemMediaResolutionService');
        }

        return new \App\Services\Catalog\CollectionItemMediaResolutionService(static::hubClient());
    }

    public static function cacheInvalidationOutbox(bool $getShared = true): \App\Libraries\PublicCache\CacheInvalidationOutbox
    {
        if ($getShared) {
            return static::getSharedInstance('cacheInvalidationOutbox');
        }

        return new \App\Libraries\PublicCache\CacheInvalidationOutbox(\Config\Database::connect());
    }

    public static function publicCacheInvalidationNotifier(bool $getShared = true): \App\Interfaces\PublicCacheInvalidationNotifierInterface
    {
        if ($getShared) {
            return static::getSharedInstance('publicCacheInvalidationNotifier');
        }

        return new \App\Libraries\PublicCache\PublicCacheInvalidationNotifier(static::cacheInvalidationOutbox());
    }

    public static function cacheInvalidationOutboxDispatcher(bool $getShared = true): \App\Libraries\PublicCache\CacheInvalidationOutboxDispatcher
    {
        if ($getShared) {
            return static::getSharedInstance('cacheInvalidationOutboxDispatcher');
        }

        $timeout = max(1, min(10, (int) env('WEB_CACHE_INVALIDATE_TIMEOUT', 5)));

        return new \App\Libraries\PublicCache\CacheInvalidationOutboxDispatcher(
            static::cacheInvalidationOutbox(),
            new \App\Libraries\PublicCache\CacheInvalidationHttpClient(
                (string) env('WEB_CACHE_INVALIDATE_URL', ''),
                (string) env('WEB_CACHE_INVALIDATE_KEY', ''),
                $timeout,
            ),
        );
    }
}
