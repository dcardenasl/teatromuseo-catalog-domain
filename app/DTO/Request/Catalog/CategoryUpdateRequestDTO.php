<?php

declare(strict_types=1);

namespace App\DTO\Request\Catalog;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CategoryUpdateRequest')]
readonly class CategoryUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'slug', type: 'string', nullable: true)]
    public ?string $slug;
    #[OA\Property(description: 'icon', type: 'string', nullable: true)]
    public ?string $icon;
    #[OA\Property(description: 'short_description', type: 'string', nullable: true)]
    public ?string $short_description;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;

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
            'slug' => 'permit_empty|string|max_length[255]',
            'icon' => 'permit_empty|string|max_length[255]',
            'short_description' => 'permit_empty|string',
            'sort_order' => 'permit_empty|integer',
            'translations' => 'permit_empty',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = $data['name'] ?? null;
        $this->slug = $data['slug'] ?? null;
        $this->icon = $data['icon'] ?? null;
        $this->short_description = $data['short_description'] ?? null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations'])
            ? array_values($data['translations'])
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'short_description' => $this->short_description,
            'sort_order' => $this->sort_order,
        ], static fn (mixed $value): bool => $value !== null) + ($this->translations !== null ? ['translations' => $this->translations] : []);
    }
}
