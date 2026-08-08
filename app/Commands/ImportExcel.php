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

    protected $usage = 'catalog:import-excel';

    public function run(array $params): void
    {
        $pythonScript = ROOTPATH . 'scripts/excel_to_json.py';
        CLI::write("Running Excel extraction helper: {$pythonScript}", 'cyan');

        $output = [];
        $resultCode = 0;
        exec("python3 " . escapeshellarg($pythonScript), $output, $resultCode);

        if ($resultCode !== 0) {
            CLI::error("Python extraction failed: " . implode("\n", $output));
            return;
        }

        CLI::write(implode("\n", $output), 'green');

        $jsonFile = WRITEPATH . 'temp_excel_data.json';
        if (! file_exists($jsonFile)) {
            CLI::error("Extracted JSON data not found at: {$jsonFile}");
            return;
        }

        $records = json_decode(file_get_contents($jsonFile), true);
        if (! is_array($records)) {
            CLI::error("Invalid JSON data parsed from extracted file.");
            return;
        }

        $categoryModel = model(\App\Models\CategoryModel::class);
        $techniqueModel = model(\App\Models\TechniqueModel::class);
        $itemModel = model(\App\Models\CollectionItemModel::class);
        $db = \Config\Database::connect();
        $slugGenerator = new SlugGenerator();

        $imported = 0;
        $updated = 0;

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

            $status = trim($row['estado'] ?? '');
            if ($status === '') {
                $status = 'bueno';
            }

            $showInTotem = in_array(strtolower(trim($row['mostrar_en_totem'] ?? '')), ['sí', 'si', '1', 'true', 'yes'], true) ? 1 : 0;
            $isActive = in_array(strtolower(trim($row['publicado'] ?? '')), ['no', '0', 'false'], true) ? 0 : 1;

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
                'cover_file_id' => null,
                'gallery_file_ids' => $row['galeria'] ?: null,
                'show_in_totem' => $showInTotem,
                'internal_notes' => $row['notas_internas'] ?? '',
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
        }

        CLI::write("Import complete. Imported: {$imported}, Updated: {$updated}", 'green');
    }
}
