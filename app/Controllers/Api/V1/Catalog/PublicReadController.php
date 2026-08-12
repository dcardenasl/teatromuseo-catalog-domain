<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Catalog;

use App\DTO\Request\Catalog\PublicReadCollectionItemRequestDTO;
use App\DTO\Request\Catalog\PublicReadLocaleRequestDTO;
use App\Interfaces\Catalog\PublicReadCollectionItemReaderInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;
use dcardenasl\Ci4ApiCore\Traits\SparseFieldsetTrait;

final class PublicReadController extends ApiController
{
    use SparseFieldsetTrait;

    /** @var list<string> */
    private const LISTING_FIELDS = [
        'id', 'name', 'category_id', 'inventory_code', 'status', 'summary',
        'cover_file_id', 'cover_image', 'slug', 'localized', 'category',
    ];

    /** @var list<string> */
    private const DETAIL_FIELDS = [
        'id', 'name', 'category_id', 'inventory_code', 'status', 'summary',
        'curiosidad', 'contenido', 'origin', 'period', 'creator', 'ubicacion',
        'materials', 'cover_file_id', 'cover_image', 'gallery_file_ids',
        'gallery_images', 'collection_number', 'collection_group',
        'physical_description', 'dimensions', 'ingress_type', 'donated_by',
        'tags', 'links', 'company_history', 'localized', 'translations',
        'slug', 'slugs', 'category', 'techniques', 'created_at', 'updated_at',
    ];

    private PublicReadCollectionItemReaderInterface $reader;

    protected function resolveDefaultService(): object
    {
        $this->reader = Services::publicReadCollectionItemReader();

        return $this->reader;
    }

    public function index(string $locale): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicReadCollectionItemRequestDTO $dto, SecurityContext $context): mixed {
                $fields = $this->parseFieldsParam(self::LISTING_FIELDS);
                return $this->reader->index($dto, $fields);
            },
            PublicReadCollectionItemRequestDTO::class,
            ['locale' => $locale],
        );
    }

    public function show(string $locale, string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicReadLocaleRequestDTO $dto) use ($idOrSlug): mixed {
                return $this->reader->show($dto->locale, $idOrSlug, $this->parseFieldsParam(self::DETAIL_FIELDS));
            },
            PublicReadLocaleRequestDTO::class,
            ['locale' => $locale],
        );
    }
}
