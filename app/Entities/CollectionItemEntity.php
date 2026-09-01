<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CollectionItemEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'category_id' => 'int',
        'inventory_code' => 'string',
        'status' => 'string',
        'summary' => 'string',
        'curiosidad' => 'string',
        'contenido' => 'string',
        'origin' => 'string',
        'period' => 'string',
        'creator' => 'string',
        'ubicacion' => 'string',
        'materials' => 'string',
        'cover_file_id' => 'int',
        'gallery_file_ids' => 'string',
        'show_in_totem' => 'int',
        'internal_notes' => 'string',
        'collection_number' => 'string',
        'collection_group' => 'string',
        'physical_description' => 'string',
        'dimensions' => 'string',
        'ingress_type' => 'string',
        'donated_by' => 'string',
        'tags' => 'string',
        'links' => 'string',
        'company_history' => 'string',
        'is_active' => 'int',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
