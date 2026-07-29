<?php

declare(strict_types=1);

namespace App\DTO\Request\Catalog;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TechniqueUpdateRequest')]
readonly class TechniqueUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'slug', type: 'string', nullable: true)]
    public ?string $slug;
    #[OA\Property(description: 'summary', type: 'string', nullable: true)]
    public ?string $summary;
    #[OA\Property(description: 'video_url', type: 'string', nullable: true)]
    public ?string $video_url;
    #[OA\Property(description: 'pdf_file_id', type: 'integer', nullable: true)]
    public ?int $pdf_file_id;
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
            'summary' => 'permit_empty|string',
            'video_url' => 'permit_empty|string|max_length[255]',
            'pdf_file_id' => 'permit_empty|integer',
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
        $this->summary = $data['summary'] ?? null;
        $this->video_url = $data['video_url'] ?? null;
        $this->pdf_file_id = isset($data['pdf_file_id']) ? (int) $data['pdf_file_id'] : null;
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
            'summary' => $this->summary,
            'video_url' => $this->video_url,
            'pdf_file_id' => $this->pdf_file_id,
            'sort_order' => $this->sort_order,
        ], static fn (mixed $value): bool => $value !== null) + ($this->translations !== null ? ['translations' => $this->translations] : []);
    }
}
