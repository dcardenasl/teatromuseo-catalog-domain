<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

/**
 * Projects the legacy single-language content of catalog resources into
 * catalog_translations (fallback locale) and generates the initial public
 * routing slug per collection item from its localized name.
 *
 * Categories and techniques already carry their own single slug column and
 * are not routed through catalog_public_slugs.
 */
class BackfillCatalogTranslationsAndSlugs extends Migration
{
    public function up(): void
    {
        $this->backfillTranslations('collection_items', 'collection_item', ['name', 'summary', 'contenido', 'curiosidad', 'physical_description', 'ubicacion']);
        $this->backfillTranslations('categories', 'category', ['name', 'short_description']);
        $this->backfillTranslations('techniques', 'technique', ['name', 'summary']);
        $this->backfillCollectionItemSlugs();
    }

    public function down(): void
    {
        // Deliberately preserve migrated content on rollback. Translation and
        // slug rows may have been edited after this compatibility backfill;
        // the schema migrations' down() drop the tables entirely.
    }

    /**
     * @param list<string> $fields
     */
    private function backfillTranslations(string $table, string $resourceType, array $fields): void
    {
        $records = $this->db->table($table)->get()->getResultArray();
        $legacyLocale = config('Localization')->legacyFallbackLocale;
        $now = date('Y-m-d H:i:s');

        foreach ($records as $record) {
            $resourceId = (int) ($record['id'] ?? 0);
            if ($resourceId < 1) {
                continue;
            }

            $hasTranslations = $this->db->table('catalog_translations')
                ->where([
                    'translatable_type' => $resourceType,
                    'translatable_id' => $resourceId,
                ])
                ->countAllResults() > 0;
            if ($hasTranslations) {
                continue;
            }

            $rows = [];
            foreach ($fields as $field) {
                $value = trim((string) ($record[$field] ?? ''));
                if ($value === '') {
                    continue;
                }

                $rows[] = [
                    'translatable_type' => $resourceType,
                    'translatable_id' => $resourceId,
                    'locale' => $legacyLocale,
                    'field' => $field,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                $this->db->table('catalog_translations')->insertBatch($rows);
            }
        }
    }

    private function backfillCollectionItemSlugs(): void
    {
        $generator = new SlugGenerator();
        $legacyLocale = config('Localization')->legacyFallbackLocale;
        $now = date('Y-m-d H:i:s');

        /** @var array<string, true> $taken "locale|slug" of every slug already assigned */
        $taken = [];
        foreach ($this->db->table('catalog_public_slugs')->where('resource_type', 'collection_item')->get()->getResultArray() as $row) {
            $taken[$row['locale'] . '|' . $row['slug']] = true;
        }

        $items = $this->db->table('collection_items')->select('id, name')->get()->getResultArray();

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId < 1) {
                continue;
            }

            $namesByLocale = [];

            $translationRows = $this->db->table('catalog_translations')
                ->where(['translatable_type' => 'collection_item', 'translatable_id' => $itemId, 'field' => 'name'])
                ->get()
                ->getResultArray();
            foreach ($translationRows as $row) {
                $name = trim((string) ($row['value'] ?? ''));
                if ($name !== '') {
                    $namesByLocale[(string) $row['locale']] = $name;
                }
            }

            $legacyName = trim((string) ($item['name'] ?? ''));
            if (! isset($namesByLocale[$legacyLocale]) && $legacyName !== '') {
                $namesByLocale[$legacyLocale] = $legacyName;
            }

            foreach ($namesByLocale as $locale => $name) {
                $exists = $this->db->table('catalog_public_slugs')
                    ->where(['resource_type' => 'collection_item', 'resource_id' => $itemId, 'locale' => $locale])
                    ->countAllResults() > 0;
                if ($exists) {
                    continue;
                }

                $slug = $generator->slugify($name);
                if ($slug === '') {
                    continue;
                }

                $slug = $generator->uniquify(
                    $slug,
                    static fn (string $candidate): bool => ! isset($taken[$locale . '|' . $candidate])
                );
                $taken[$locale . '|' . $slug] = true;

                $this->db->table('catalog_public_slugs')->insert([
                    'resource_type' => 'collection_item',
                    'resource_id'   => $itemId,
                    'locale'        => $locale,
                    'slug'          => $slug,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }
}
