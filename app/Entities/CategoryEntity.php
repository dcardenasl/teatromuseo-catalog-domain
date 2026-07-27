<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CategoryEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'icon' => 'string',
        'short_description' => 'string',
        'sort_order' => 'int',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
