<?php

declare(strict_types=1);

namespace App\Interfaces\Catalog;

use App\DTO\Request\Catalog\PublicReadCollectionItemRequestDTO;
use dcardenasl\Ci4ApiCore\Support\ApiResult;

interface PublicReadCollectionItemReaderInterface
{
    /** @param list<string> $fields */
    public function index(PublicReadCollectionItemRequestDTO $request, array $fields): ApiResult;

    /** @param list<string> $fields */
    public function show(string $locale, string $idOrSlug, array $fields): ApiResult;
}
