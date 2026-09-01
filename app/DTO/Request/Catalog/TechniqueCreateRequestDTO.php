<?php

declare(strict_types=1);

namespace App\DTO\Request\Catalog;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TechniqueCreateRequest')]
readonly class TechniqueCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'slug', type: 'string')]
    public string $slug;
    #[OA\Property(description: 'summary', type: 'string', nullable: true)]
    public ?string $summary;
    #[OA\Property(description: 'video_url', type: 'string', nullable: true)]
    public ?string $video_url;
    #[OA\Property(description: 'pdf_file_id', type: 'integer', nullable: true)]
    public ?int $pdf_file_id;
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
            'slug' => 'required|string|max_length[255]|is_unique[techniques.slug]',
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
        $this->name = (string) ($data['name'] ?? '');
        $this->slug = (string) ($data['slug'] ?? '');
        $this->summary = $data['summary'] ?? null;
        $this->video_url = $data['video_url'] ?? null;
        $this->pdf_file_id = isset($data['pdf_file_id']) ? (int) $data['pdf_file_id'] : null;
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
            'summary' => $this->summary,
            'video_url' => $this->video_url,
            'pdf_file_id' => $this->pdf_file_id,
            'sort_order' => $this->sort_order,
            'translations' => $this->translations,
        ];
    }
}
