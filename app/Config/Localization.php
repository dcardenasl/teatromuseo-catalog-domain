<?php

declare(strict_types=1);

namespace Config;

use dcardenasl\Ci4ApiCore\Config\Localization as BaseLocalization;

/**
 * Content localization registry for the Catalog Domain.
 *
 * `$translatableFields` is the explicit content contract for resources owned by
 * this domain, and doubles as the allow-list that stops arbitrary database
 * columns from becoming translatable — adding a resource stays a deliberate
 * schema decision. It replaces the former `App\Libraries\Localization\
 * TranslationFieldCatalog`, whose `fields()`/`hasField()` contract the core
 * config now provides verbatim.
 *
 * `$legacyFallbackLocale` is intentionally not a list of supported languages:
 * the CMS language catalog is dynamic, and this only provides a safe fallback
 * for legacy rows that predate `catalog_translations`. Override it per
 * environment with `LOCALIZATION_LEGACY_FALLBACK_LOCALE`.
 *
 * NOTE: `contenido`, `curiosidad` and `ubicacion` are Spanish while their
 * siblings are English. These are real `collection_items` column names consumed
 * by the admin, the public web and the totem, so renaming them is a cross-app
 * change requiring a data migration — tracked separately as CORE-01b, not a
 * config edit.
 */
class Localization extends BaseLocalization
{
    /** @var array<string, list<string>> */
    public array $translatableFields = [
        'collection_item' => ['name', 'summary', 'contenido', 'curiosidad', 'physical_description', 'ubicacion'],
        'category'        => ['name', 'short_description'],
        'technique'       => ['name', 'summary'],
    ];

    public string $legacyFallbackLocale = 'es';
}
