<?php

declare(strict_types=1);

namespace App\DTO\Response\Catalog;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoryResponse',
    title: 'Category Response',
    required: ["id","name","slug"]
)]
final readonly class CategoryResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'name', type: 'string')]
        public string $name,
        #[OA\Property(description: 'slug', type: 'string')]
        public string $slug,
        #[OA\Property(description: 'icon', type: 'string', nullable: true)]
        public ?string $icon,
        #[OA\Property(description: 'short_description', type: 'string', nullable: true)]
        public ?string $short_description,
        #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
        public ?int $sort_order,
        /** @var list<array<string, string>> */
        #[OA\Property(description: 'All stored localized content rows', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations = [],
        /** @var array<string, string> */
        #[OA\Property(description: 'Content resolved from Accept-Language with field-level fallback', type: 'object')]
        public array $localized = [],
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
            slug: (string) ($data['slug'] ?? ''),
            icon: $data['icon'] ?? null,
            short_description: $data['short_description'] ?? null,
            sort_order: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            translations: is_array($data['translations'] ?? null) ? $data['translations'] : [],
            localized: is_array($data['localized'] ?? null) ? $data['localized'] : [],
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
            'slug' => $this->slug,
            'icon' => $this->icon,
            'short_description' => $this->short_description,
            'sort_order' => $this->sort_order,
            'translations' => $this->translations,
            'localized' => $this->localized,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
