<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\CollectionItemEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class CollectionItemModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'collection_items';
    protected $primaryKey = 'id';
    protected $returnType = CollectionItemEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'category_id', 'inventory_code', 'status', 'summary', 'curiosidad', 'contenido', 'origin', 'period', 'creator', 'ubicacion', 'materials', 'cover_file_id', 'gallery_file_ids', 'show_in_totem', 'internal_notes', 'collection_number', 'collection_group', 'physical_description', 'dimensions', 'ingress_type', 'donated_by', 'tags', 'links', 'company_history', 'is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = ['name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'category_id', 'is_active'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'name'];

    protected $validationRules = [
        'id' => 'permit_empty|integer',
        'name' => 'required|string|max_length[255]',
        'category_id' => 'required|integer',
        'inventory_code' => 'permit_empty|string|max_length[255]|is_unique[collection_items.inventory_code,id,{id}]',
        'status' => 'required|string|max_length[255]',
        'summary' => 'permit_empty|string',
        'curiosidad' => 'permit_empty|string',
        'contenido' => 'permit_empty|string',
        'origin' => 'permit_empty|string|max_length[255]',
        'period' => 'permit_empty|string|max_length[255]',
        'creator' => 'permit_empty|string|max_length[255]',
        'ubicacion' => 'permit_empty|string|max_length[255]',
        'materials' => 'permit_empty|string',
        'cover_file_id' => 'permit_empty|integer',
        'gallery_file_ids' => 'permit_empty|string',
        'show_in_totem' => 'required|integer',
        'internal_notes' => 'permit_empty|string',
        'collection_number' => 'permit_empty|string|max_length[255]',
        'collection_group' => 'permit_empty|string|max_length[255]',
        'physical_description' => 'permit_empty|string',
        'dimensions' => 'permit_empty|string|max_length[255]',
        'ingress_type' => 'permit_empty|string|max_length[255]',
        'donated_by' => 'permit_empty|string|max_length[255]',
        'tags' => 'permit_empty|string',
        'links' => 'permit_empty|string',
        'company_history' => 'permit_empty|string',
        'is_active' => 'required|integer',
    ];
}
