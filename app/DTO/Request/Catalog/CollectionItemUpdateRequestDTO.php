<?php

declare(strict_types=1);

namespace App\DTO\Request\Catalog;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CollectionItemUpdateRequest')]
readonly class CollectionItemUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'category_id', type: 'integer', nullable: true)]
    public ?int $category_id;
    #[OA\Property(description: 'inventory_code', type: 'string', nullable: true)]
    public ?string $inventory_code;
    #[OA\Property(description: 'status', type: 'string', nullable: true)]
    public ?string $status;
    #[OA\Property(description: 'summary', type: 'string', nullable: true)]
    public ?string $summary;
    #[OA\Property(description: 'curiosidad', type: 'string', nullable: true)]
    public ?string $curiosidad;
    #[OA\Property(description: 'contenido', type: 'string', nullable: true)]
    public ?string $contenido;
    #[OA\Property(description: 'origin', type: 'string', nullable: true)]
    public ?string $origin;
    #[OA\Property(description: 'period', type: 'string', nullable: true)]
    public ?string $period;
    #[OA\Property(description: 'creator', type: 'string', nullable: true)]
    public ?string $creator;
    #[OA\Property(description: 'ubicacion', type: 'string', nullable: true)]
    public ?string $ubicacion;
    #[OA\Property(description: 'materials', type: 'string', nullable: true)]
    public ?string $materials;
    #[OA\Property(description: 'cover_file_id', type: 'integer', nullable: true)]
    public ?int $cover_file_id;
    #[OA\Property(description: 'gallery_file_ids', type: 'string', nullable: true)]
    public ?string $gallery_file_ids;
    #[OA\Property(description: 'show_in_totem', type: 'integer', nullable: true)]
    public ?int $show_in_totem;
    #[OA\Property(description: 'internal_notes', type: 'string', nullable: true)]
    public ?string $internal_notes;
    #[OA\Property(description: 'collection_number', type: 'string', nullable: true)]
    public ?string $collection_number;
    #[OA\Property(description: 'collection_group', type: 'string', nullable: true)]
    public ?string $collection_group;
    #[OA\Property(description: 'physical_description', type: 'string', nullable: true)]
    public ?string $physical_description;
    #[OA\Property(description: 'dimensions', type: 'string', nullable: true)]
    public ?string $dimensions;
    #[OA\Property(description: 'ingress_type', type: 'string', nullable: true)]
    public ?string $ingress_type;
    #[OA\Property(description: 'donated_by', type: 'string', nullable: true)]
    public ?string $donated_by;
    #[OA\Property(description: 'tags', type: 'string', nullable: true)]
    public ?string $tags;
    #[OA\Property(description: 'links', type: 'string', nullable: true)]
    public ?string $links;
    #[OA\Property(description: 'company_history', type: 'string', nullable: true)]
    public ?string $company_history;
    #[OA\Property(description: 'is_active', type: 'integer', nullable: true)]
    public ?int $is_active;

    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', nullable: true, items: new OA\Items(type: 'object'))]
    public ?array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'permit_empty|string|max_length[255]',
            'category_id' => 'permit_empty|integer',
            'inventory_code' => 'permit_empty|string|max_length[255]',
            'status' => 'permit_empty|string|max_length[255]',
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
            'show_in_totem' => 'permit_empty|integer',
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
            'is_active' => 'permit_empty|integer',
            'translations' => 'permit_empty',
        ];
    }

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * NOT NULL columns (name, category_id, status, show_in_totem,
     * is_active) never accept an explicit null — treated the same as
     * omitting the field, matching the DB constraint. Every other field
     * (all the optional descriptive metadata, cover_file_id,
     * gallery_file_ids) is a nullable column: an explicit null preserves
     * through to toArray() and actually clears it — the bug this fixes is
     * array_filter() silently dropping every null, which made it
     * impossible to ever clear any of these fields via update.
     *
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = array_key_exists('name', $data) && $data['name'] !== null ? (string) $data['name'] : null;
        $this->category_id = array_key_exists('category_id', $data) && $data['category_id'] !== null && $data['category_id'] !== '' ? (int) $data['category_id'] : null;
        $this->inventory_code = array_key_exists('inventory_code', $data) && $data['inventory_code'] !== null && $data['inventory_code'] !== '' ? (string) $data['inventory_code'] : null;
        $this->status = array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : null;
        $this->summary = array_key_exists('summary', $data) && $data['summary'] !== null && $data['summary'] !== '' ? (string) $data['summary'] : null;
        $this->curiosidad = array_key_exists('curiosidad', $data) && $data['curiosidad'] !== null && $data['curiosidad'] !== '' ? (string) $data['curiosidad'] : null;
        $this->contenido = array_key_exists('contenido', $data) && $data['contenido'] !== null && $data['contenido'] !== '' ? (string) $data['contenido'] : null;
        $this->origin = array_key_exists('origin', $data) && $data['origin'] !== null && $data['origin'] !== '' ? (string) $data['origin'] : null;
        $this->period = array_key_exists('period', $data) && $data['period'] !== null && $data['period'] !== '' ? (string) $data['period'] : null;
        $this->creator = array_key_exists('creator', $data) && $data['creator'] !== null && $data['creator'] !== '' ? (string) $data['creator'] : null;
        $this->ubicacion = array_key_exists('ubicacion', $data) && $data['ubicacion'] !== null && $data['ubicacion'] !== '' ? (string) $data['ubicacion'] : null;
        $this->materials = array_key_exists('materials', $data) && $data['materials'] !== null && $data['materials'] !== '' ? (string) $data['materials'] : null;
        $this->cover_file_id = array_key_exists('cover_file_id', $data) && $data['cover_file_id'] !== null && $data['cover_file_id'] !== '' ? (int) $data['cover_file_id'] : null;
        $this->gallery_file_ids = array_key_exists('gallery_file_ids', $data) && $data['gallery_file_ids'] !== null ? (string) $data['gallery_file_ids'] : null;
        $this->show_in_totem = array_key_exists('show_in_totem', $data) && $data['show_in_totem'] !== null ? (int) $data['show_in_totem'] : null;
        $this->internal_notes = array_key_exists('internal_notes', $data) && $data['internal_notes'] !== null && $data['internal_notes'] !== '' ? (string) $data['internal_notes'] : null;
        $this->collection_number = array_key_exists('collection_number', $data) && $data['collection_number'] !== null && $data['collection_number'] !== '' ? (string) $data['collection_number'] : null;
        $this->collection_group = array_key_exists('collection_group', $data) && $data['collection_group'] !== null && $data['collection_group'] !== '' ? (string) $data['collection_group'] : null;
        $this->physical_description = array_key_exists('physical_description', $data) && $data['physical_description'] !== null && $data['physical_description'] !== '' ? (string) $data['physical_description'] : null;
        $this->dimensions = array_key_exists('dimensions', $data) && $data['dimensions'] !== null && $data['dimensions'] !== '' ? (string) $data['dimensions'] : null;
        $this->ingress_type = array_key_exists('ingress_type', $data) && $data['ingress_type'] !== null && $data['ingress_type'] !== '' ? (string) $data['ingress_type'] : null;
        $this->donated_by = array_key_exists('donated_by', $data) && $data['donated_by'] !== null && $data['donated_by'] !== '' ? (string) $data['donated_by'] : null;
        $this->tags = array_key_exists('tags', $data) && $data['tags'] !== null && $data['tags'] !== '' ? (string) $data['tags'] : null;
        $this->links = array_key_exists('links', $data) && $data['links'] !== null && $data['links'] !== '' ? (string) $data['links'] : null;
        $this->company_history = array_key_exists('company_history', $data) && $data['company_history'] !== null && $data['company_history'] !== '' ? (string) $data['company_history'] : null;
        $this->is_active = array_key_exists('is_active', $data) && $data['is_active'] !== null ? (int) $data['is_active'] : null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations']) ? array_values($data['translations']) : null;

        $mappedFields = [];
        if ($this->name !== null) {
            $mappedFields['name'] = $this->name;
        }
        if ($this->category_id !== null) {
            $mappedFields['category_id'] = $this->category_id;
        }
        if (array_key_exists('inventory_code', $data)) {
            $mappedFields['inventory_code'] = $this->inventory_code;
        }
        if ($this->status !== null) {
            $mappedFields['status'] = $this->status;
        }
        if (array_key_exists('summary', $data)) {
            $mappedFields['summary'] = $this->summary;
        }
        if (array_key_exists('curiosidad', $data)) {
            $mappedFields['curiosidad'] = $this->curiosidad;
        }
        if (array_key_exists('contenido', $data)) {
            $mappedFields['contenido'] = $this->contenido;
        }
        if (array_key_exists('origin', $data)) {
            $mappedFields['origin'] = $this->origin;
        }
        if (array_key_exists('period', $data)) {
            $mappedFields['period'] = $this->period;
        }
        if (array_key_exists('creator', $data)) {
            $mappedFields['creator'] = $this->creator;
        }
        if (array_key_exists('ubicacion', $data)) {
            $mappedFields['ubicacion'] = $this->ubicacion;
        }
        if (array_key_exists('materials', $data)) {
            $mappedFields['materials'] = $this->materials;
        }
        if (array_key_exists('cover_file_id', $data)) {
            $mappedFields['cover_file_id'] = $this->cover_file_id;
        }
        if (array_key_exists('gallery_file_ids', $data)) {
            $mappedFields['gallery_file_ids'] = $this->gallery_file_ids;
        }
        if ($this->show_in_totem !== null) {
            $mappedFields['show_in_totem'] = $this->show_in_totem;
        }
        if (array_key_exists('internal_notes', $data)) {
            $mappedFields['internal_notes'] = $this->internal_notes;
        }
        if (array_key_exists('collection_number', $data)) {
            $mappedFields['collection_number'] = $this->collection_number;
        }
        if (array_key_exists('collection_group', $data)) {
            $mappedFields['collection_group'] = $this->collection_group;
        }
        if (array_key_exists('physical_description', $data)) {
            $mappedFields['physical_description'] = $this->physical_description;
        }
        if (array_key_exists('dimensions', $data)) {
            $mappedFields['dimensions'] = $this->dimensions;
        }
        if (array_key_exists('ingress_type', $data)) {
            $mappedFields['ingress_type'] = $this->ingress_type;
        }
        if (array_key_exists('donated_by', $data)) {
            $mappedFields['donated_by'] = $this->donated_by;
        }
        if (array_key_exists('tags', $data)) {
            $mappedFields['tags'] = $this->tags;
        }
        if (array_key_exists('links', $data)) {
            $mappedFields['links'] = $this->links;
        }
        if (array_key_exists('company_history', $data)) {
            $mappedFields['company_history'] = $this->company_history;
        }
        if ($this->is_active !== null) {
            $mappedFields['is_active'] = $this->is_active;
        }
        if ($this->translations !== null) {
            $mappedFields['translations'] = $this->translations;
        }

        $this->mappedFields = $mappedFields;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
