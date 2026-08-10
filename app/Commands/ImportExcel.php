<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

class ImportExcel extends BaseCommand
{
    protected $group = 'Catalog';
    protected $name = 'catalog:import-excel';
    protected $description = 'Import museum collection items from excel template.';

    protected $usage = 'catalog:import-excel [--json=/path/to/records.json] [--dry-run]';

    protected $options = [
        '--json'    => 'Use a pre-generated JSON export instead of invoking Python.',
        '--dry-run' => 'Validate and count records without opening a write transaction.',
    ];

    public function run(array $params): void
    {
        $jsonFile = (string) (CLI::getOption('json') ?? $this->option($params, 'json'));
        if ($jsonFile === '') {
            $pythonScript = ROOTPATH . 'scripts/excel_to_json.py';
            CLI::write("Running Excel extraction helper: {$pythonScript}", 'cyan');

            $output = [];
            $resultCode = 0;
            exec("python3 " . escapeshellarg($pythonScript), $output, $resultCode);

            if ($resultCode !== 0) {
                CLI::error("Excel extraction failed: " . implode("\n", $output));
                return;
            }

            CLI::write(implode("\n", $output), 'green');
            $jsonFile = WRITEPATH . 'temp_excel_data.json';
        }

        $jsonFile = realpath($jsonFile) ?: $jsonFile;
        if (! file_exists($jsonFile)) {
            CLI::error("Extracted JSON data not found at: {$jsonFile}");
            return;
        }

        $records = json_decode(file_get_contents($jsonFile), true);
        if (! is_array($records)) {
            CLI::error("Invalid JSON data parsed from extracted file.");
            return;
        }

        $records = array_values(array_filter(
            $records,
            static fn (mixed $record): bool => is_array($record),
        ));
        if ((bool) (CLI::getOption('dry-run') ?? false) || $this->hasFlag($params, 'dry-run')) {
            $validRecords = count(array_filter(
                $records,
                static fn (array $record): bool => trim((string) ($record['nombre'] ?? '')) !== '',
            ));
            CLI::write("Dry run complete. Valid collection records: {$validRecords}.", 'green');
            return;
        }

        $categoryModel = model(\App\Models\CategoryModel::class);
        $techniqueModel = model(\App\Models\TechniqueModel::class);
        $itemModel = model(\App\Models\CollectionItemModel::class);
        $db = \Config\Database::connect();
        $slugGenerator = new SlugGenerator();

        $imported = 0;
        $updated = 0;
        $db->transStart();

        try {
            foreach ($records as $row) {
                $name = trim($row['nombre'] ?? '');
                if ($name === '') {
                    continue;
                }

                // Resolve Category
                $categoryName = trim($row['categoria'] ?? '');
                if ($categoryName === '') {
                    $categoryName = 'Otro';
                }
                $category = $categoryModel->where('name', $categoryName)->first();
                if (! $category) {
                    $catId = $categoryModel->insert([
                        'name' => $categoryName,
                        'slug' => $slugGenerator->slugify($categoryName),
                        'icon' => 'tag',
                        'short_description' => 'Colección de ' . $categoryName,
                    ]);
                } else {
                    $catId = (int) $category->id;
                }

                // Resolve Techniques
                $techniquesAssoc = [];
                $techniquesRaw = trim($row['tecnicas_asociadas'] ?? '');
                if ($techniquesRaw !== '') {
                    $techList = array_filter(array_map('trim', explode(',', $techniquesRaw)));
                    foreach ($techList as $techName) {
                        if ($techName === '') {
                            continue;
                        }
                        $tech = $techniqueModel->where('name', $techName)->first();
                        if (! $tech) {
                            $techId = $techniqueModel->insert([
                                'name' => $techName,
                                'slug' => $slugGenerator->slugify($techName),
                                'summary' => 'Técnica de ' . $techName,
                            ]);
                        } else {
                            $techId = (int) $tech->id;
                        }
                        $techniquesAssoc[] = $techId;
                    }
                }

                // Ingest Collection Item
                $inventoryCode = trim($row['codigo_vitrina_bodega'] ?? '');
                if ($inventoryCode === '') {
                    $inventoryCode = 'INV-' . uniqid();
                }

                $showInTotem = in_array(strtolower(trim($row['mostrar_en_totem'] ?? '')), ['sí', 'si', '1', 'true', 'yes'], true) ? 1 : 0;
                $isActive = in_array(strtolower(trim($row['publicado'] ?? '')), ['no', '0', 'false'], true) ? 0 : 1;
                // `status` is the public publication state consumed by
                // PublicRead. The spreadsheet's `estado` column describes the
                // object's condition and must never make a published item
                // disappear from the public listing.
                $status = $isActive === 1 ? 'published' : 'draft';
                $condition = trim((string) ($row['estado'] ?? ''));
                $internalNotes = trim((string) ($row['notas_internas'] ?? ''));
                if ($condition !== '') {
                    $internalNotes = trim($internalNotes . "\nEstado de conservación: " . $condition);
                }

                $itemData = [
                    'name' => $name,
                    'category_id' => $catId,
                    'inventory_code' => $inventoryCode,
                    'status' => $status,
                    'summary' => $row['descripcion_corta'] ?? '',
                    'curiosidad' => '', // Default to empty
                    'contenido' => $row['historia_completa'] ?? '',
                    'origin' => $row['origen'] ?? '',
                    'period' => $row['periodo'] ?? '',
                    'creator' => $row['creador_artista'] ?? '',
                    'ubicacion' => $row['ubicacion_fisica'] ?? '',
                    'materials' => $row['materiales'] ?? '',
                    'cover_file_id' => $this->numericFileId($row['imagen_portada'] ?? null),
                    'gallery_file_ids' => $this->numericFileIds($row['galeria'] ?? null),
                    'show_in_totem' => $showInTotem,
                    'internal_notes' => $internalNotes,
                    'collection_number' => $row['numero_coleccion'] ?? '',
                    'collection_group' => $row['coleccion'] ?? '',
                    'physical_description' => $row['descripcion_fisica'] ?? '',
                    'dimensions' => $row['tamanio'] ?? '',
                    'ingress_type' => $row['forma_ingreso'] ?? '',
                    'donated_by' => $row['donado_facilitado_por'] ?? '',
                    'tags' => $row['etiquetas'] ?? '',
                    'links' => $row['mas_informacion'] ?? '',
                    'company_history' => $row['historia_compania'] ?? '',
                    'is_active' => $isActive,
                ];

                $existing = $itemModel->where('inventory_code', $inventoryCode)->first();
                if ($existing) {
                    $itemModel->update($existing->id, $itemData);
                    $itemId = (int) $existing->id;
                    $updated++;
                } else {
                    $itemId = (int) $itemModel->insert($itemData);
                    $imported++;
                }

                // Sync Pivot Relations
                $pivotModel = model(\App\Models\CollectionItemTechniqueModel::class);
                $pivotModel->where('collection_item_id', $itemId)->delete();
                foreach ($techniquesAssoc as $techId) {
                    $pivotModel->insert([
                        'collection_item_id' => $itemId,
                        'technique_id'       => $techId,
                        'created_at'         => date('Y-m-d H:i:s'),
                    ]);
                }

                $this->syncPublicLocalization($db, $itemId, $name, $row, $slugGenerator);
            }

            // Keep the public Web cache consistent with the catalog writes.
            // The outbox insert participates in this transaction, so a failed
            // invalidation cannot leave a successful import serving stale data.
            \Config\Services::publicCacheInvalidationNotifier()->invalidate([
                'collection_items',
                'categories',
                'techniques',
            ]);
        } catch (\Throwable $exception) {
            $db->transRollback();
            CLI::error('Import rolled back: ' . $exception->getMessage());
            return;
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            CLI::error('Import rolled back because the database transaction failed.');
            return;
        }

        CLI::write("Import complete. Imported: {$imported}, Updated: {$updated}", 'green');
    }

    /** @param list<string> $params */
    private function option(array $params, string $name): string
    {
        $prefix = '--' . $name . '=';
        foreach ($params as $param) {
            if (is_string($param) && str_starts_with($param, $prefix)) {
                return trim(substr($param, strlen($prefix)), " '\"");
            }
        }

        return '';
    }

    /** @param list<string> $params */
    private function hasFlag(array $params, string $name): bool
    {
        return in_array('--' . $name, $params, true);
    }

    /** @param array<string, mixed> $row */
    private function syncPublicLocalization(
        \CodeIgniter\Database\BaseConnection $db,
        int $itemId,
        string $name,
        array $row,
        SlugGenerator $slugGenerator,
    ): void {
        $translationFields = [
            'name' => $name,
            'summary' => (string) ($row['descripcion_corta'] ?? ''),
            'contenido' => (string) ($row['historia_completa'] ?? ''),
            'physical_description' => (string) ($row['descripcion_fisica'] ?? ''),
            'ubicacion' => (string) ($row['ubicacion_fisica'] ?? ''),
        ];
        $now = date('Y-m-d H:i:s');

        foreach ($translationFields as $field => $value) {
            $translation = $db->table('catalog_translations')
                ->where('translatable_type', 'collection_item')
                ->where('translatable_id', $itemId)
                ->where('locale', 'es')
                ->where('field', $field)
                ->get(1)
                ->getRowArray();
            $values = ['value' => trim($value), 'updated_at' => $now];

            if ($translation === null) {
                $db->table('catalog_translations')->insert([
                    'translatable_type' => 'collection_item',
                    'translatable_id' => $itemId,
                    'locale' => 'es',
                    'field' => $field,
                    ...$values,
                    'created_at' => $now,
                ]);
            } else {
                $db->table('catalog_translations')->where('id', (int) $translation['id'])->update($values);
            }
        }

        $baseSlug = trim($slugGenerator->slugify($name), '-');
        $inventoryCode = trim((string) ($row['codigo_vitrina_bodega'] ?? ''));
        $candidateSlug = $baseSlug !== '' ? $baseSlug : $inventoryCode;
        $existing = $db->table('catalog_public_slugs')
            ->where('resource_type', 'collection_item')
            ->where('locale', 'es')
            ->where('slug', $candidateSlug)
            ->where('resource_id !=', $itemId)
            ->get(1)
            ->getRowArray();
        if ($existing !== null && $inventoryCode !== '') {
            $candidateSlug .= '-' . trim($slugGenerator->slugify($inventoryCode), '-');
        }

        $slugRow = $db->table('catalog_public_slugs')
            ->where('resource_type', 'collection_item')
            ->where('resource_id', $itemId)
            ->where('locale', 'es')
            ->get(1)
            ->getRowArray();
        if ($slugRow === null) {
            $db->table('catalog_public_slugs')->insert([
                'resource_type' => 'collection_item',
                'resource_id' => $itemId,
                'locale' => 'es',
                'slug' => $candidateSlug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $db->table('catalog_public_slugs')->where('id', (int) $slugRow['id'])->update([
                'slug' => $candidateSlug,
                'updated_at' => $now,
            ]);
        }
    }

    private function numericFileId(mixed $value): ?int
    {
        $value = trim((string) $value);

        return ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function numericFileIds(mixed $value): ?string
    {
        $ids = array_values(array_filter(array_map(
            fn (string $item): ?int => $this->numericFileId($item),
            explode(',', (string) $value),
        )));

        return $ids === [] ? null : implode(',', $ids);
    }
}
