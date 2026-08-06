<?php

declare(strict_types=1);

namespace App\Models;

use dcardenasl\Ci4ApiCore\Models\BaseTranslationModel;

/**
 * Sidecar translation rows for Catalog Domain resources.
 *
 * Only the table name is app-specific — this domain predates the core's default
 * `translations` table name. Schema, casts and audit wiring come from the core
 * base model.
 */
class CatalogTranslationModel extends BaseTranslationModel
{
    protected $table = 'catalog_translations';
}
