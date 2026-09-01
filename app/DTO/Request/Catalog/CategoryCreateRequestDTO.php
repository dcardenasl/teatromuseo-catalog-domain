<?php

declare(strict_types=1);

namespace App\DTO\Request\Catalog;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CategoryCreateRequest')]
readonly class CategoryCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'slug', type: 'string')]
    public string $slug;
    #[OA\Property(description: 'icon', type: 'string', nullable: true)]
    public ?string $icon;
    #[OA\Property(description: 'short_description', type: 'string', nullable: true)]
    public ?string $short_description;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;

    /** @var list<array<string, mixed>> */
    #[OA\Property(description: 'Localized content rows keyed by locale code', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max_length[255]',
            'slug' => 'required|string|max_length[255]|is_unique[categories.slug]',
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
        $this->name = (string) ($data['name'] ?? '');
        $this->slug = (string) ($data['slug'] ?? '');
        $this->icon = $data['icon'] ?? null;
        $this->short_description = $data['short_description'] ?? null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
        $this->translations = is_array($data['translations'] ?? null) ? array_values($data['translations']) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'short_description' => $this->short_description,
            'sort_order' => $this->sort_order,
            'translations' => $this->translations,
        ];
    }
}
