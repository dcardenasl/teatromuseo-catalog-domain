<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\CategoryEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class CategoryModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $returnType = CategoryEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'slug', 'icon', 'short_description', 'sort_order'];

    /** @var array<int, string> */
    protected array $searchableFields = ['name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'name'];

    protected $validationRules = [
        'name' => 'required|string|max_length[255]',
        'slug' => 'required|string|max_length[255]|is_unique[categories.slug]',
        'icon' => 'permit_empty|string|max_length[255]',
        'short_description' => 'permit_empty|string',
        'sort_order' => 'permit_empty|integer',
    ];
}
