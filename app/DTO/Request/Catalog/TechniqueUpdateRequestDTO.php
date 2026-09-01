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

    /** @var array<string, mixed> */
    private array $mappedFields;

    /**
     * NOT NULL columns (name, slug) never accept an explicit null —
     * treated the same as omitting the field. Nullable columns (summary,
     * video_url, pdf_file_id, sort_order) preserve an explicit null so it
     * reaches toArray() and actually clears the column — the bug this
     * fixes is array_filter() silently dropping every null, which made it
     * impossible to ever clear a nullable field via update.
     *
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->name = array_key_exists('name', $data) && $data['name'] !== null ? (string) $data['name'] : null;
        $this->slug = array_key_exists('slug', $data) && $data['slug'] !== null ? (string) $data['slug'] : null;
        $this->summary = array_key_exists('summary', $data) && $data['summary'] !== null && $data['summary'] !== '' ? (string) $data['summary'] : null;
        $this->video_url = array_key_exists('video_url', $data) && $data['video_url'] !== null && $data['video_url'] !== '' ? (string) $data['video_url'] : null;
        $this->pdf_file_id = array_key_exists('pdf_file_id', $data) && $data['pdf_file_id'] !== null && $data['pdf_file_id'] !== '' ? (int) $data['pdf_file_id'] : null;
        $this->sort_order = array_key_exists('sort_order', $data) && $data['sort_order'] !== null && $data['sort_order'] !== '' ? (int) $data['sort_order'] : null;
        $this->translations = array_key_exists('translations', $data) && is_array($data['translations']) ? array_values($data['translations']) : null;

        $mappedFields = [];
        if ($this->name !== null) {
            $mappedFields['name'] = $this->name;
        }
        if ($this->slug !== null) {
            $mappedFields['slug'] = $this->slug;
        }
        if (array_key_exists('summary', $data)) {
            $mappedFields['summary'] = $this->summary;
        }
        if (array_key_exists('video_url', $data)) {
            $mappedFields['video_url'] = $this->video_url;
        }
        if (array_key_exists('pdf_file_id', $data)) {
            $mappedFields['pdf_file_id'] = $this->pdf_file_id;
        }
        if (array_key_exists('sort_order', $data)) {
            $mappedFields['sort_order'] = $this->sort_order;
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
