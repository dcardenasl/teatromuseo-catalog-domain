<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Libraries\Localization\LocalizedTranslationStore;
use App\Libraries\Localization\PublicSlugStore;
use App\Libraries\Localization\RequestLocaleResolver;
use App\Libraries\Localization\SlugGenerator;
use App\Models\CatalogPublicSlugModel;
use App\Models\CatalogTranslationModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Seeder;

/**
 * Seeds representative museum catalog data (techniques, categories, collection items)
 * aligned across all 4 system languages (es, en, fr, pt).
 */
final class TeatroMuseoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $translationStore = new LocalizedTranslationStore(new CatalogTranslationModel());

        // 1. Techniques
        foreach ($this->techniqueDefinitions() as $def) {
            $this->upsertRecord('techniques', [
                'slug' => $def['slug'],
            ], [
                'name' => $def['translations']['es']['name'],
                'summary' => $def['translations']['es']['summary'],
            ]);

            $techniqueId = (int) $this->db->table('techniques')->where('slug', $def['slug'])->get()->getRow()->id;
            $translationRows = [];
            foreach ($def['translations'] as $locale => $fields) {
                $translationRows[] = ['locale' => $locale, ...$fields];
            }
            $translationStore->sync('technique', $techniqueId, $translationRows);
        }

        // 2. Categories
        foreach ($this->categoryDefinitions() as $def) {
            $this->upsertRecord('categories', [
                'slug' => $def['slug'],
            ], [
                'name' => $def['translations']['es']['name'],
                'icon' => $def['icon'],
                'short_description' => $def['translations']['es']['short_description'],
                'sort_order' => $def['sort_order'],
            ]);

            $categoryId = (int) $this->db->table('categories')->where('slug', $def['slug'])->get()->getRow()->id;
            $translationRows = [];
            foreach ($def['translations'] as $locale => $fields) {
                $translationRows[] = ['locale' => $locale, ...$fields];
            }
            $translationStore->sync('category', $categoryId, $translationRows);
        }

        // 3. Collection Items
        $categoryIds = $this->categoryIds();
        $techniqueIds = $this->techniqueIds();

        foreach ($this->itemDefinitions() as $def) {
            $categoryId = $categoryIds[$def['category_slug']] ?? null;
            if ($categoryId === null) {
                continue;
            }

            $es = $def['translations']['es'];
            $this->upsertRecord('collection_items', [
                'inventory_code' => $def['inventory_code'],
            ], [
                'name' => $es['name'],
                'category_id' => $categoryId,
                'status' => 'published',
                'summary' => $es['summary'],
                'curiosidad' => $es['curiosidad'],
                'contenido' => $es['contenido'],
                'origin' => $def['origin'],
                'period' => $def['period'],
                'creator' => $def['creator'],
                'ubicacion' => $es['ubicacion'],
                'materials' => $def['materials'],
                'cover_file_id' => null,
                'gallery_file_ids' => null,
                'show_in_totem' => $def['show_in_totem'],
                'internal_notes' => $def['internal_notes'],
                'collection_number' => $def['collection_number'],
                'collection_group' => $def['collection_group'],
                'physical_description' => $es['physical_description'],
                'dimensions' => $def['dimensions'],
                'ingress_type' => $def['ingress_type'],
                'donated_by' => $def['donated_by'],
                'tags' => $def['tags'],
                'links' => $def['links'],
                'company_history' => null,
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

            $namesByLocale = [];
            $trans = $this->db->table('catalog_translations')
                ->where('translatable_type', 'collection_item')
                ->where('translatable_id', $itemId)
                ->where('field', 'name')
                ->get()
                ->getResultArray();

            foreach ($trans as $tRow) {
                $loc = (string) ($tRow['locale'] ?? '');
                $val = trim((string) ($tRow['value'] ?? ''));
                if ($loc !== '' && $val !== '') {
                    $namesByLocale[$loc] = $val;
                }
            }

            if ($namesByLocale !== []) {
                $slugStore->syncForResource('collection_item', $itemId, $namesByLocale);
            }
        }
    }

    /**
     * @return list<array{slug: string, translations: array<string, array{name: string, summary: string}>}>
     */
    private function techniqueDefinitions(): array
    {
        return [
            [
                'slug' => 'titeres-guiol',
                'translations' => [
                    'es' => ['name' => 'Títeres de Guiñol', 'summary' => 'Títeres de guante articulados desde la base del muñeco.'],
                    'en' => ['name' => 'Guignol Puppets', 'summary' => 'Glove puppets operated directly by the performer from beneath.'],
                    'fr' => ['name' => 'Marionnettes à gaine', 'summary' => 'Marionnettes manipulées directement par le bas avec la main.'],
                    'pt' => ['name' => 'Fantoches de Luva', 'summary' => 'Manipulação direta com a mão a partir da base do boneco.'],
                ],
            ],
            [
                'slug' => 'marionetas-hilos',
                'translations' => [
                    'es' => ['name' => 'Marionetas de Hilos', 'summary' => 'Muñecos suspendidos y movidos por finos hilos desde una cruceta.'],
                    'en' => ['name' => 'String Marionettes', 'summary' => 'Figures suspended and controlled from above by strings and a control cross.'],
                    'fr' => ['name' => 'Marionnettes à fils', 'summary' => 'Figures suspendues manipulées depuis le haut au moyen de fils.'],
                    'pt' => ['name' => 'Marionetes de Fios', 'summary' => 'Figuras suspensas e movimentadas por fios a partir de uma cruzeta.'],
                ],
            ],
            [
                'slug' => 'titeres-sombra',
                'translations' => [
                    'es' => ['name' => 'Teatro de Sombras', 'summary' => 'Figuras recortadas proyectadas sobre una pantalla retroiluminada.'],
                    'en' => ['name' => 'Shadow Theatre', 'summary' => 'Flat cut-out figures projected onto a translucent backlit screen.'],
                    'fr' => ['name' => 'Théâtre d\'ombres', 'summary' => 'Figures découpées projetées sur un écran rétroéclairé.'],
                    'pt' => ['name' => 'Teatro de Sombras', 'summary' => 'Figuras cortadas projetadas sobre uma tela retroiluminada.'],
                ],
            ],
            [
                'slug' => 'caracterizacion-mascaras',
                'translations' => [
                    'es' => ['name' => 'Máscaras y Caracterización', 'summary' => 'Piezas faciales expresivas para interpretación dramática.'],
                    'en' => ['name' => 'Masks & Characterization', 'summary' => 'Expressive facial pieces for physical and dramatic performance.'],
                    'fr' => ['name' => 'Masques et Caractérisation', 'summary' => 'Pièces faciales expressives pour le jeu dramatique et physique.'],
                    'pt' => ['name' => 'Máscaras e Caracterização', 'summary' => 'Peças faciais expressivas para interpretação dramática.'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{slug: string, icon: string, sort_order: int, translations: array<string, array{name: string, short_description: string}>}>
     */
    private function categoryDefinitions(): array
    {
        return [
            [
                'slug' => 'escenografia',
                'icon' => 'layout-grid',
                'sort_order' => 10,
                'translations' => [
                    'es' => ['name' => 'Escenografía', 'short_description' => 'Elementos escénicos, utilería y estructuras de escena.'],
                    'en' => ['name' => 'Scenography', 'short_description' => 'Stage elements, props, and scene structures.'],
                    'fr' => ['name' => 'Scénographie', 'short_description' => 'Éléments scéniques, accessoires et structures de scène.'],
                    'pt' => ['name' => 'Cenografia', 'short_description' => 'Elementos cênicos, adereços e estruturas de cena.'],
                ],
            ],
            [
                'slug' => 'vestuario',
                'icon' => 'shirt',
                'sort_order' => 20,
                'translations' => [
                    'es' => ['name' => 'Vestuario', 'short_description' => 'Prendas, accesorios y piezas de caracterización.'],
                    'en' => ['name' => 'Costumes', 'short_description' => 'Garments, accessories, and characterization pieces.'],
                    'fr' => ['name' => 'Costumes', 'short_description' => 'Vêtements, accessoires et pièces de caractérisation.'],
                    'pt' => ['name' => 'Figurinos', 'short_description' => 'Peças de vestuário, acessórios e caracterização.'],
                ],
            ],
            [
                'slug' => 'memoria',
                'icon' => 'archive',
                'sort_order' => 30,
                'translations' => [
                    'es' => ['name' => 'Memoria', 'short_description' => 'Documentos, registros y material de archivo.'],
                    'en' => ['name' => 'Archive & Memory', 'short_description' => 'Documents, historical records, and archival material.'],
                    'fr' => ['name' => 'Mémoire et Archives', 'short_description' => 'Documents, registres et matériel d\'archives.'],
                    'pt' => ['name' => 'Memória e Arquivo', 'short_description' => 'Documentos, registros e material de arquivo.'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itemDefinitions(): array
    {
        return [
            $this->item(
                'pieza-001',
                'escenografia',
                'marionetas-hilos',
                'TEL-001',
                '3,20 x 6,00 m',
                'Montaje interno',
                'Compañía TeatroMuseo',
                'teatro, telon',
                'https://example.test/pieza-001',
                '2024-2025',
                'Equipo técnico',
                'Archivo de producción',
                'Lona, pintura textil y bastidor',
                [
                    'es' => ['name' => 'Telón Azul', 'summary' => 'Telón principal de la sala experimental.', 'curiosidad' => 'Se pintó a mano con pigmentos lavables.', 'contenido' => '<p>Telón principal de la sala experimental.</p><p>Se pintó a mano con pigmentos lavables.</p>', 'physical_description' => 'Escenografía del estreno inaugural.', 'ubicacion' => 'Sala Experimental'],
                    'en' => ['name' => 'Blue Backdrop', 'summary' => 'Main backdrop of the experimental hall.', 'curiosidad' => 'Hand-painted with washable pigments.', 'contenido' => '<p>Main backdrop of the experimental hall.</p><p>Hand-painted with washable pigments.</p>', 'physical_description' => 'Inaugural premiere scenography.', 'ubicacion' => 'Experimental Hall'],
                    'fr' => ['name' => 'Rideau Bleu', 'summary' => 'Rideau principal de la salle expérimentale.', 'curiosidad' => 'Peint à la main avec des pigments lavables.', 'contenido' => '<p>Rideau principal de la salle expérimentale.</p><p>Peint à la main avec des pigments lavables.</p>', 'physical_description' => 'Scénographie de la première inauguration.', 'ubicacion' => 'Salle Expérimentale'],
                    'pt' => ['name' => 'Cortina Azul', 'summary' => 'Cortina principal da sala experimental.', 'curiosidad' => 'Pintada à mão com pigmentos laváveis.', 'contenido' => '<p>Cortina principal da sala experimental.</p><p>Pintada à mão com pigmentos laváveis.</p>', 'physical_description' => 'Cenografia da estreia inaugural.', 'ubicacion' => 'Sala Experimental'],
                ]
            ),
            $this->item(
                'pieza-006',
                'vestuario',
                'caracterizacion-mascaras',
                'VES-001',
                'Talla M',
                'Confección propia',
                'Colectivo TeatroMuseo',
                'vestuario, gala',
                'https://example.test/pieza-006',
                '2024',
                'Diseño de vestuario',
                'Sastrería teatral',
                'Seda, hilo metálico y forro de algodón',
                [
                    'es' => ['name' => 'Traje de Gala', 'summary' => 'Prenda de ceremonia usada en funciones especiales.', 'curiosidad' => 'Conserva el bordado original de la primera temporada.', 'contenido' => '<p>Prenda de ceremonia usada en funciones especiales.</p>', 'physical_description' => 'Vestuario de apertura de temporada.', 'ubicacion' => 'Camerino histórico'],
                    'en' => ['name' => 'Gala Outfit', 'summary' => 'Ceremonial costume used in special performances.', 'curiosidad' => 'Retains the original first-season embroidery.', 'contenido' => '<p>Ceremonial costume used in special performances.</p>', 'physical_description' => 'Season opening costume.', 'ubicacion' => 'Historical Dressing Room'],
                    'fr' => ['name' => 'Costume de Gala', 'summary' => 'Vêtement de cérémonie utilisé lors des représentations spéciales.', 'curiosidad' => 'Conserve la broderie originale de la première saison.', 'contenido' => '<p>Vêtement de cérémonie utilisé lors des représentations spéciales.</p>', 'physical_description' => 'Costume d\'ouverture de saison.', 'ubicacion' => 'Loge historique'],
                    'pt' => ['name' => 'Traje de Gala', 'summary' => 'Veste cerimonial usada em apresentações especiais.', 'curiosidad' => 'Conserva o bordado original da primeira temporada.', 'contenido' => '<p>Veste cerimonial usada em apresentações especiais.</p>', 'physical_description' => 'Figurino de abertura de temporada.', 'ubicacion' => 'Camarim histórico'],
                ]
            ),
            $this->item(
                'pieza-008',
                'vestuario',
                'caracterizacion-mascaras',
                'VES-003',
                'Única',
                'Donación de montaje',
                null,
                'mascara, rostro',
                'https://example.test/pieza-008',
                '2022',
                'Diseño escénico',
                'Archivo de caracterización',
                'Lino, resina y pintura acrílica',
                [
                    'es' => ['name' => 'Máscara de Lino', 'summary' => 'Máscara de rostro para montaje físico.', 'curiosidad' => 'Su textura cambia con la luz frontal.', 'contenido' => '<p>Máscara de rostro para montaje físico.</p>', 'physical_description' => 'Uso en escenas de máscara.', 'ubicacion' => 'Bodega de vestuario'],
                    'en' => ['name' => 'Linen Mask', 'summary' => 'Face mask designed for physical theatre.', 'curiosidad' => 'Its texture reacts to front lighting.', 'contenido' => '<p>Face mask designed for physical theatre.</p>', 'physical_description' => 'Used in mask scenes.', 'ubicacion' => 'Costume Storage'],
                    'fr' => ['name' => 'Masque de Lin', 'summary' => 'Masque facial conçu pour le théâtre physique.', 'curiosidad' => 'Sa texture réagit à la lumière frontale.', 'contenido' => '<p>Masque facial conçu pour le théâtre physique.</p>', 'physical_description' => 'Utilisé dans les scènes de masque.', 'ubicacion' => 'Réserve des costumes'],
                    'pt' => ['name' => 'Máscara de Linho', 'summary' => 'Máscara facial projetada para teatro físico.', 'curiosidad' => 'Sua textura reage à iluminação frontal.', 'contenido' => '<p>Máscara facial projetada para teatro físico.</p>', 'physical_description' => 'Usada em cenas de máscara.', 'ubicacion' => 'Depósito de figurinos'],
                ]
            ),
            $this->item(
                'pieza-010',
                'memoria',
                'titeres-sombra',
                'MEM-001',
                '24 páginas',
                'Donación privada',
                null,
                'programa, archivo',
                'https://example.test/pieza-010',
                '2019',
                'Equipo editorial',
                'Archivo documental',
                'Papel couché y tinta offset',
                [
                    'es' => ['name' => 'Programa de Temporada', 'summary' => 'Programa impreso de una temporada histórica.', 'curiosidad' => 'Incluye anotaciones manuscritas de circulación interna.', 'contenido' => '<p>Programa impreso de una temporada histórica.</p>', 'physical_description' => 'Registro impreso de programación.', 'ubicacion' => 'Archivo histórico'],
                    'en' => ['name' => 'Season Program', 'summary' => 'Printed souvenir program of a landmark season.', 'curiosidad' => 'Includes handwritten internal circulation notes.', 'contenido' => '<p>Printed souvenir program of a landmark season.</p>', 'physical_description' => 'Printed programming record.', 'ubicacion' => 'Historical Archive'],
                    'fr' => ['name' => 'Programme de Saison', 'summary' => 'Programme imprimé d\'une saison historique.', 'curiosidad' => 'Comprend des notes manuscrites de circulation interne.', 'contenido' => '<p>Programme imprimé d\'une saison historique.</p>', 'physical_description' => 'Registre imprimé de programmation.', 'ubicacion' => 'Archives historiques'],
                    'pt' => ['name' => 'Programa de Temporada', 'summary' => 'Programa impresso de uma temporada histórica.', 'curiosidad' => 'Inclui anotações manuscritas de circulação interna.', 'contenido' => '<p>Programa impresso de uma temporada histórica.</p>', 'physical_description' => 'Registro impresso de programação.', 'ubicacion' => 'Arquivo histórico'],
                ]
            ),
        ];
    }

    /**
     * @param array<string, array<string, string>> $translations
     * @return array<string, mixed>
     */
    private function item(
        string $inventoryCode,
        string $categorySlug,
        ?string $techniqueSlug,
        string $collectionNumber,
        string $dimensions,
        string $ingressType,
        ?string $donatedBy,
        string $tags,
        string $links,
        string $period,
        string $creator,
        string $origin,
        string $materials,
        array $translations
    ): array {
        return [
            'inventory_code' => $inventoryCode,
            'category_slug' => $categorySlug,
            'technique_slug' => $techniqueSlug,
            'collection_number' => $collectionNumber,
            'collection_group' => 'TeatroMuseo',
            'dimensions' => $dimensions,
            'ingress_type' => $ingressType,
            'donated_by' => $donatedBy,
            'tags' => $tags,
            'links' => $links,
            'period' => $period,
            'creator' => $creator,
            'origin' => $origin,
            'materials' => $materials,
            'show_in_totem' => 1,
            'internal_notes' => null,
            'translations' => $translations,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function categoryIds(): array
    {
        $rows = $this->db->table('categories')->get()->getResultArray();
        $ids = [];
        foreach ($rows as $row) {
            $ids[(string) $row['slug']] = (int) $row['id'];
        }
        return $ids;
    }

    /**
     * @return array<string, int>
     */
    private function techniqueIds(): array
    {
        $rows = $this->db->table('techniques')->get()->getResultArray();
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

        $existing = $this->db->table($table)->where($lookup)->get()->getRowArray();
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
                $fallback = $this->db->table($table)->where($lookup)->get()->getRowArray();
                if ($fallback !== null && $supportsId && isset($fallback['id'])) {
                    $this->db->table($table)->where('id', (int) $fallback['id'])->update($payload);
                    return;
                }
            }
            return;
        }

        $updatePayload = $payload;
        unset($updatePayload['created_at']);

        if ($supportsId && isset($existing['id'])) {
            $this->db->table($table)->where('id', (int) $existing['id'])->update($updatePayload);
            return;
        }

        $this->db->table($table)->where($lookup)->update($updatePayload);
    }
}
