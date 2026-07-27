<?php

declare(strict_types=1);

namespace App\DTO\Request\Catalog;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CollectionItemCreateRequest')]
readonly class CollectionItemCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'category_id', type: 'integer')]
    public int $category_id;
    #[OA\Property(description: 'inventory_code', type: 'string', nullable: true)]
    public ?string $inventory_code;
    #[OA\Property(description: 'status', type: 'string')]
    public string $status;
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
    #[OA\Property(description: 'show_in_totem', type: 'integer')]
    public int $show_in_totem;
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
    #[OA\Property(description: 'is_active', type: 'integer')]
    public int $is_active;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max_length[255]',
            'category_id' => 'required|integer',
            'inventory_code' => 'permit_empty|string|max_length[255]|is_unique[collection_items.inventory_code]',
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

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = (string) ($data['name'] ?? '');
        $this->category_id = (int) ($data['category_id'] ?? 0);
        $this->inventory_code = $data['inventory_code'] ?? null;
        $this->status = (string) ($data['status'] ?? '');
        $this->summary = $data['summary'] ?? null;
        $this->curiosidad = $data['curiosidad'] ?? null;
        $this->contenido = $data['contenido'] ?? null;
        $this->origin = $data['origin'] ?? null;
        $this->period = $data['period'] ?? null;
        $this->creator = $data['creator'] ?? null;
        $this->ubicacion = $data['ubicacion'] ?? null;
        $this->materials = $data['materials'] ?? null;
        $this->cover_file_id = isset($data['cover_file_id']) ? (int) $data['cover_file_id'] : null;
        $this->gallery_file_ids = $data['gallery_file_ids'] ?? null;
        $this->show_in_totem = (int) ($data['show_in_totem'] ?? 0);
        $this->internal_notes = $data['internal_notes'] ?? null;
        $this->collection_number = $data['collection_number'] ?? null;
        $this->collection_group = $data['collection_group'] ?? null;
        $this->physical_description = $data['physical_description'] ?? null;
        $this->dimensions = $data['dimensions'] ?? null;
        $this->ingress_type = $data['ingress_type'] ?? null;
        $this->donated_by = $data['donated_by'] ?? null;
        $this->tags = $data['tags'] ?? null;
        $this->links = $data['links'] ?? null;
        $this->company_history = $data['company_history'] ?? null;
        $this->is_active = (int) ($data['is_active'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category_id' => $this->category_id,
            'inventory_code' => $this->inventory_code,
            'status' => $this->status,
            'summary' => $this->summary,
            'curiosidad' => $this->curiosidad,
            'contenido' => $this->contenido,
            'origin' => $this->origin,
            'period' => $this->period,
            'creator' => $this->creator,
            'ubicacion' => $this->ubicacion,
            'materials' => $this->materials,
            'cover_file_id' => $this->cover_file_id,
            'gallery_file_ids' => $this->gallery_file_ids,
            'show_in_totem' => $this->show_in_totem,
            'internal_notes' => $this->internal_notes,
            'collection_number' => $this->collection_number,
            'collection_group' => $this->collection_group,
            'physical_description' => $this->physical_description,
            'dimensions' => $this->dimensions,
            'ingress_type' => $this->ingress_type,
            'donated_by' => $this->donated_by,
            'tags' => $this->tags,
            'links' => $this->links,
            'company_history' => $this->company_history,
            'is_active' => $this->is_active,
        ];
    }
}
