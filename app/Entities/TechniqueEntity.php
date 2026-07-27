<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TechniqueEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'summary' => 'string',
        'video_url' => 'string',
        'pdf_file_id' => 'int',
        'sort_order' => 'int',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
