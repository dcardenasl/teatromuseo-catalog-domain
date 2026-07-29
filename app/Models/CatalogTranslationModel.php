<?php

declare(strict_types=1);

namespace App\Models;

use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class CatalogTranslationModel extends BaseAuditableModel
{
    protected $table = 'catalog_translations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];
}
