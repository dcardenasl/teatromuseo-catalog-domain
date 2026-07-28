<?php

declare(strict_types=1);

namespace App\DTO\Response\Catalog;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectionItemResponse',
    title: 'CollectionItem Response',
    required: ["id","name","category_id","status","show_in_totem","is_active"]
)]
final readonly class CollectionItemResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'name', type: 'string')]
        public string $name,
        #[OA\Property(description: 'category_id', type: 'integer')]
        public int $category_id,
        #[OA\Property(description: 'inventory_code', type: 'string', nullable: true)]
        public ?string $inventory_code,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
        #[OA\Property(description: 'summary', type: 'string', nullable: true)]
        public ?string $summary,
        #[OA\Property(description: 'curiosidad', type: 'string', nullable: true)]
        public ?string $curiosidad,
        #[OA\Property(description: 'contenido', type: 'string', nullable: true)]
        public ?string $contenido,
        #[OA\Property(description: 'origin', type: 'string', nullable: true)]
        public ?string $origin,
        #[OA\Property(description: 'period', type: 'string', nullable: true)]
        public ?string $period,
        #[OA\Property(description: 'creator', type: 'string', nullable: true)]
        public ?string $creator,
        #[OA\Property(description: 'ubicacion', type: 'string', nullable: true)]
        public ?string $ubicacion,
        #[OA\Property(description: 'materials', type: 'string', nullable: true)]
        public ?string $materials,
        #[OA\Property(description: 'cover_file_id', type: 'integer', nullable: true)]
        public ?int $cover_file_id,
        #[OA\Property(description: 'gallery_file_ids', type: 'string', nullable: true)]
        public ?string $gallery_file_ids,
        #[OA\Property(description: 'show_in_totem', type: 'integer')]
        public int $show_in_totem,
        #[OA\Property(description: 'internal_notes', type: 'string', nullable: true)]
        public ?string $internal_notes,
        #[OA\Property(description: 'collection_number', type: 'string', nullable: true)]
        public ?string $collection_number,
        #[OA\Property(description: 'collection_group', type: 'string', nullable: true)]
        public ?string $collection_group,
        #[OA\Property(description: 'physical_description', type: 'string', nullable: true)]
        public ?string $physical_description,
        #[OA\Property(description: 'dimensions', type: 'string', nullable: true)]
        public ?string $dimensions,
        #[OA\Property(description: 'ingress_type', type: 'string', nullable: true)]
        public ?string $ingress_type,
        #[OA\Property(description: 'donated_by', type: 'string', nullable: true)]
        public ?string $donated_by,
        #[OA\Property(description: 'tags', type: 'string', nullable: true)]
        public ?string $tags,
        #[OA\Property(description: 'links', type: 'string', nullable: true)]
        public ?string $links,
        #[OA\Property(description: 'company_history', type: 'string', nullable: true)]
        public ?string $company_history,
        #[OA\Property(description: 'is_active', type: 'integer')]
        public int $is_active,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $createdAt = $data['created_at'] ?? null;
        if ($createdAt instanceof \DateTimeInterface) {
            $createdAt = $createdAt->format('Y-m-d H:i:s');
        }

        $updatedAt = $data['updated_at'] ?? null;
        if ($updatedAt instanceof \DateTimeInterface) {
            $updatedAt = $updatedAt->format('Y-m-d H:i:s');
        }

        return new static(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            category_id: (int) ($data['category_id'] ?? 0),
            inventory_code: $data['inventory_code'] ?? null,
            status: (string) ($data['status'] ?? ''),
            summary: $data['summary'] ?? null,
            curiosidad: $data['curiosidad'] ?? null,
            contenido: $data['contenido'] ?? null,
            origin: $data['origin'] ?? null,
            period: $data['period'] ?? null,
            creator: $data['creator'] ?? null,
            ubicacion: $data['ubicacion'] ?? null,
            materials: $data['materials'] ?? null,
            cover_file_id: isset($data['cover_file_id']) ? (int) $data['cover_file_id'] : null,
            gallery_file_ids: $data['gallery_file_ids'] ?? null,
            show_in_totem: (int) ($data['show_in_totem'] ?? 0),
            internal_notes: $data['internal_notes'] ?? null,
            collection_number: $data['collection_number'] ?? null,
            collection_group: $data['collection_group'] ?? null,
            physical_description: $data['physical_description'] ?? null,
            dimensions: $data['dimensions'] ?? null,
            ingress_type: $data['ingress_type'] ?? null,
            donated_by: $data['donated_by'] ?? null,
            tags: $data['tags'] ?? null,
            links: $data['links'] ?? null,
            company_history: $data['company_history'] ?? null,
            is_active: (int) ($data['is_active'] ?? 0),
            createdAt: is_string($createdAt) ? $createdAt : null,
            updatedAt: is_string($updatedAt) ? $updatedAt : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
