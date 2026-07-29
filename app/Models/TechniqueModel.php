<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\TechniqueEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class TechniqueModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'techniques';
    protected $primaryKey = 'id';
    protected $returnType = TechniqueEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'slug', 'summary', 'video_url', 'pdf_file_id', 'sort_order'];

    /** @var array<int, string> */
    protected array $searchableFields = ['name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'name'];

    protected $validationRules = [
        'id' => 'permit_empty|integer',
        'name' => 'required|string|max_length[255]',
        'slug' => 'required|string|max_length[255]|is_unique[techniques.slug,id,{id}]',
        'summary' => 'permit_empty|string',
        'video_url' => 'permit_empty|string|max_length[255]',
        'pdf_file_id' => 'permit_empty|integer',
        'sort_order' => 'permit_empty|integer',
    ];
}
