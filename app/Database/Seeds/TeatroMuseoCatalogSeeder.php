<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Seeder;

/**
 * Seeds representative museum catalog data for local development.
 */
final class TeatroMuseoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categoryDefinitions() as $definition) {
            $this->upsertRecord('categories', [
                'slug' => $definition['slug'],
            ], [
                'name' => $definition['name'],
                'icon' => $definition['icon'],
                'short_description' => $definition['short_description'],
                'sort_order' => $definition['sort_order'],
            ]);
        }

        $categoryIds = $this->categoryIds();
        foreach ($this->itemDefinitions() as $definition) {
            $categoryId = $categoryIds[$definition['category_slug']] ?? null;
            if ($categoryId === null) {
                continue;
            }

            $this->upsertRecord('collection_items', [
                'inventory_code' => $definition['inventory_code'],
            ], [
                'name' => $definition['name'],
                'category_id' => $categoryId,
                'status' => 'published',
                'summary' => $definition['summary'],
                'curiosidad' => $definition['curiosidad'],
                'contenido' => $definition['contenido'],
                'origin' => $definition['origin'],
                'period' => $definition['period'],
                'creator' => $definition['creator'],
                'ubicacion' => $definition['ubicacion'],
                'materials' => $definition['materials'],
                'cover_file_id' => null,
                'gallery_file_ids' => null,
                'show_in_totem' => $definition['show_in_totem'],
                'internal_notes' => $definition['internal_notes'],
                'collection_number' => $definition['collection_number'],
                'collection_group' => $definition['collection_group'],
                'physical_description' => $definition['physical_description'],
                'dimensions' => $definition['dimensions'],
                'ingress_type' => $definition['ingress_type'],
                'donated_by' => $definition['donated_by'],
                'tags' => $definition['tags'],
                'links' => $definition['links'],
                'company_history' => $definition['company_history'],
                'is_active' => 1,
            ]);
        }

        $this->syncPublicSlugs();
    }

    /**
     * Ensure every seeded item carries its per-locale routing slug. Existing
     * slugs are preserved (PublicSlugStore only fills the gaps), so re-running
     * the seeder never moves a published URL.
     */
    private function syncPublicSlugs(): void
    {
        $slugStore = new \App\Libraries\Localization\PublicSlugStore(
            new \App\Models\CatalogPublicSlugModel(),
            new \App\Libraries\Localization\SlugGenerator(),
            new \App\Libraries\Localization\RequestLocaleResolver(),
        );
        $legacyLocale = config('Localization')->legacyFallbackLocale;

        $rows = $this->db->table('collection_items')->select('id, name')->get()->getResultArray();
        foreach ($rows as $row) {
            $itemId = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));
            if ($itemId < 1 || $name === '') {
                continue;
            }

            $slugStore->syncForResource('collection_item', $itemId, [$legacyLocale => $name]);
        }
    }

    /**
     * @return list<array{slug: string, name: string, icon: ?string, short_description: string, sort_order: int}>
     */
    private function categoryDefinitions(): array
    {
        return [
            [
                'slug' => 'escenografia',
                'name' => 'Escenografía',
                'icon' => 'layout-grid',
                'short_description' => 'Elementos escénicos, utilería y estructuras de escena.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'vestuario',
                'name' => 'Vestuario',
                'icon' => 'shirt',
                'short_description' => 'Prendas, accesorios y piezas de caracterización.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'memoria',
                'name' => 'Memoria',
                'icon' => 'archive',
                'short_description' => 'Documentos, registros y material de archivo.',
                'sort_order' => 30,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itemDefinitions(): array
    {
        return [
            $this->item('pieza-001', 'Telón Azul', 'escenografia', 'Telón principal de la sala experimental.', 'Se pintó a mano con pigmentos lavables.', 'Archivo de producción', '2024-2025', 'Equipo técnico', 'Sala Experimental', 'Lona, pintura textil y bastidor', 'TEL-001', 'Escenografía del estreno inaugural.', '3,20 x 6,00 m', 'Montaje interno', 'Compañía TeatroMuseo', 'teatro, telon', 'https://example.test/pieza-001'),
            $this->item('pieza-002', 'Tramoya Móvil', 'escenografia', 'Sistema móvil de cambios rápidos de escena.', 'Se reutiliza desde varias temporadas.', 'Taller central', '2023-2024', 'Equipo de escenografía', 'Bodega técnica', 'Madera, rieles y herrajes', 'TEL-002', 'Parte del soporte escénico principal.', '2,40 x 1,20 m', 'Traslado institucional', null, 'tramoya, escena', 'https://example.test/pieza-002'),
            $this->item('pieza-003', 'Lienzo de Fondo', 'escenografia', 'Fondo pintado para atmósferas nocturnas.', 'El degradado se resolvió en una sola jornada.', 'Archivo de temporada', '2022-2023', 'Área de arte', 'Depósito 1', 'Lino y acrílico', 'TEL-003', 'Usado en funciones de verano.', '4,00 x 7,50 m', 'Producción propia', null, 'fondo, pintura', 'https://example.test/pieza-003'),
            $this->item('pieza-004', 'Atril de Dirección', 'escenografia', 'Atril plegable para lecturas y ensayos.', 'Su ángulo se ajusta con una sola mano.', 'Taller de utilería', '2025', 'Jefatura técnica', 'Sala de ensayo', 'Metal pintado y madera', 'TEL-004', 'Accesorio de apoyo para ensayo.', '1,10 x 0,45 m', 'Compra directa', null, 'utileria, ensayo', 'https://example.test/pieza-004'),
            $this->item('pieza-005', 'Bastidor Rojo', 'escenografia', 'Bastidor ligero para escenas de tránsito.', 'Se montó como pieza modular.', 'Archivo escénico', '2021-2022', 'Equipo técnico', 'Depósito 2', 'Aluminio y tela tensada', 'TEL-005', 'Sistema modular reutilizable.', '2,80 x 1,80 m', 'Donación interna', null, 'bastidor, modular', 'https://example.test/pieza-005'),
            $this->item('pieza-006', 'Traje de Gala', 'vestuario', 'Prenda de ceremonia usada en funciones especiales.', 'Conserva el bordado original de la primera temporada.', 'Sastrería teatral', '2024', 'Diseño de vestuario', 'Camerino histórico', 'Seda, hilo metálico y forro de algodón', 'VES-001', 'Vestuario de apertura de temporada.', 'Talla M', 'Confección propia', 'Colectivo TeatroMuseo', 'vestuario, gala', 'https://example.test/pieza-006'),
            $this->item('pieza-007', 'Vestuario de Ensayo', 'vestuario', 'Conjunto liviano para trabajo de sala.', 'Fue ajustado para múltiples intérpretes.', 'Taller de vestuario', '2023', 'Departamento de vestuario', 'Camerino 2', 'Algodón y elastano', 'VES-002', 'Pieza de uso continuo.', 'Talla L', 'Confección propia', null, 'ensayo, vestuario', 'https://example.test/pieza-007'),
            $this->item('pieza-008', 'Máscara de Lino', 'vestuario', 'Máscara de rostro para montaje físico.', 'Su textura cambia con la luz frontal.', 'Archivo de caracterización', '2022', 'Diseño escénico', 'Bodega de vestuario', 'Lino, resina y pintura acrílica', 'VES-003', 'Uso en escenas de máscara.', 'Única', 'Donación de montaje', null, 'mascara, rostro', 'https://example.test/pieza-008'),
            $this->item('pieza-009', 'Sombrero de Camerino', 'vestuario', 'Sombrero utilizado en escenas de época.', 'Se conserva con su caja original.', 'Colección de vestuario', '2021', 'Maestría de vestuario', 'Archivo central', 'Fieltro y cinta de raso', 'VES-004', 'Complemento de utilería vestible.', 'Talla única', 'Adquisición institucional', null, 'sombrero, epoca', 'https://example.test/pieza-009'),
            $this->item('pieza-010', 'Programa de Temporada', 'memoria', 'Programa impreso de una temporada histórica.', 'Incluye anotaciones manuscritas de circulación interna.', 'Archivo documental', '2019', 'Equipo editorial', 'Archivo histórico', 'Papel couché y tinta offset', 'MEM-001', 'Registro impreso de programación.', '24 páginas', 'Donación privada', null, 'programa, archivo', 'https://example.test/pieza-010'),
            $this->item('pieza-011', 'Afiche de Estreno', 'memoria', 'Afiche promocional del estreno de verano.', 'La tirada original fue de solo cien ejemplares.', 'Archivo de prensa', '2020', 'Diseño gráfico', 'Archivo de prensa', 'Papel, tinta serigráfica', 'MEM-002', 'Material promocional de exhibición.', '70 x 100 cm', 'Colección patrimonial', null, 'afiche, estreno', 'https://example.test/pieza-011'),
            $this->item('pieza-012', 'Cuaderno de Montaje', 'memoria', 'Bitácora técnica con notas de puesta en escena.', 'Contiene bocetos y marcas de ensayo.', 'Archivo técnico', '2024', 'Jefatura de montaje', 'Archivo técnico', 'Papel, lápiz y cinta', 'MEM-003', 'Registro operativo del proceso.', 'Cuaderno A5', 'Archivo propio', null, 'bitacora, montaje', 'https://example.test/pieza-012'),
            $this->item('pieza-013', 'Archivo Fotográfico', 'memoria', 'Conjunto de imágenes de funciones y ensayos.', 'La serie cubre tres temporadas consecutivas.', 'Archivo visual', '2018-2025', 'Fotografía institucional', 'Archivo fotográfico', 'Papel fotográfico y negativos digitalizados', 'MEM-004', 'Memoria visual del teatro.', 'Colección mixta', 'Archivo institucional', null, 'foto, memoria', 'https://example.test/pieza-013'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $inventoryCode,
        string $name,
        string $categorySlug,
        string $summary,
        string $curiosidad,
        string $origin,
        string $period,
        string $creator,
        string $ubicacion,
        string $materials,
        string $collectionNumber,
        string $physicalDescription,
        string $dimensions,
        string $ingressType,
        ?string $donatedBy,
        string $tags,
        string $links
    ): array {
        return [
            'inventory_code' => $inventoryCode,
            'name' => $name,
            'category_slug' => $categorySlug,
            'summary' => $summary,
            'curiosidad' => $curiosidad,
            'contenido' => '<p>' . $summary . '</p><p>' . $curiosidad . '</p>',
            'origin' => $origin,
            'period' => $period,
            'creator' => $creator,
            'ubicacion' => $ubicacion,
            'materials' => $materials,
            'show_in_totem' => 1,
            'internal_notes' => null,
            'collection_number' => $collectionNumber,
            'collection_group' => 'TeatroMuseo',
            'physical_description' => $physicalDescription,
            'dimensions' => $dimensions,
            'ingress_type' => $ingressType,
            'donated_by' => $donatedBy,
            'tags' => $tags,
            'links' => $links,
            'company_history' => null,
            'is_active' => 1,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function categoryIds(): array
    {
        $rows = $this->db->table('categories')
            ->whereIn('slug', array_map(static fn (array $definition): string => $definition['slug'], $this->categoryDefinitions()))
            ->get()
            ->getResultArray();

        $ids = [];
        foreach ($rows as $row) {
            $ids[(string) $row['slug']] = (int) $row['id'];
        }

        return $ids;
    }

    /**
     * @param array<string, scalar|null> $lookup
     * @param array<string, mixed> $data
     */
    private function upsertRecord(string $table, array $lookup, array $data): void
    {
        $supportsCreatedAt = $this->db->fieldExists('created_at', $table);
        $supportsUpdatedAt = $this->db->fieldExists('updated_at', $table);
        $supportsId = $this->db->fieldExists('id', $table);

        $existing = $this->db->table($table)
            ->where($lookup)
            ->get()
            ->getRowArray();

        $payload = array_merge($lookup, $data);
        if ($supportsUpdatedAt) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($existing === null) {
            if ($supportsCreatedAt) {
                $payload['created_at'] = date('Y-m-d H:i:s');
            }

            try {
                $this->db->table($table)->insert($payload);
                return;
            } catch (DatabaseException) {
                $fallback = $this->db->table($table)
                    ->where($lookup)
                    ->get()
                    ->getRowArray();

                if ($fallback !== null && $supportsId && isset($fallback['id'])) {
                    $this->db->table($table)
                        ->where('id', (int) $fallback['id'])
                        ->update($payload);
                    return;
                }
            }

            return;
        }

        $updatePayload = $payload;
        unset($updatePayload['created_at']);

        if ($supportsId && isset($existing['id'])) {
            $this->db->table($table)
                ->where('id', (int) $existing['id'])
                ->update($updatePayload);
            return;
        }

        $this->db->table($table)
            ->where($lookup)
            ->update($updatePayload);
    }
}
