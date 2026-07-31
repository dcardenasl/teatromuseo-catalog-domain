<?php

declare(strict_types=1);

namespace App\DTO\Request\Example;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ItemUpdateRequest')]
readonly class ItemUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;

    public function rules(): array
    {
        return [
            'name' => 'permit_empty|string|max_length[255]',
            'description' => 'permit_empty|string',
        ];
    }

    /** @var array<string, mixed> */
    private array $mappedFields;

    protected function map(array $data): void
    {
        $this->name = array_key_exists('name', $data) && $data['name'] !== null ? (string) $data['name'] : null;
        $this->description = array_key_exists('description', $data) && $data['description'] !== null ? (string) $data['description'] : null;

        $mappedFields = [];
        if (array_key_exists('name', $data)) {
            $mappedFields['name'] = $this->name;
        }
        if (array_key_exists('description', $data)) {
            $mappedFields['description'] = $this->description;
        }

        $this->mappedFields = $mappedFields;
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
