<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Catalog;

use CodeIgniter\Database\QueryInterface;
use CodeIgniter\Events\Events;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicReadQueryBudgetTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const WEB_API_KEY = 'test-web-api-key';

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        putenv('WEB_API_KEY=' . self::WEB_API_KEY);
        $_ENV['WEB_API_KEY'] = self::WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::WEB_API_KEY;

        $this->db->disableForeignKeyChecks();
        $this->db->table('catalog_public_slugs')->where('id >', 0)->delete();
        $this->db->table('catalog_translations')->where('id >', 0)->delete();
        $this->db->table('collection_item_technique')->where('collection_item_id >', 0)->delete();
        $this->db->table('collection_items')->where('id >', 0)->delete();
        $this->db->table('categories')->where('id >', 0)->delete();
        $this->db->table('techniques')->where('id >', 0)->delete();
        $this->db->enableForeignKeyChecks();
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testListingKeepsAStableQueryBudgetAndUsesThePublicIndex(): void
    {
        $this->db->table('categories')->insert([
            'name' => 'QA-02 Catalog',
            'slug' => 'qa-02-catalog',
            'short_description' => 'QA-02 fixture',
            'sort_order' => 1,
        ]);
        $categoryId = (int) $this->db->insertID();

        $items = [];
        for ($index = 0; $index < 600; $index++) {
            $published = $index < 120;
            $items[] = [
                'name' => sprintf('qa02-catalog-%04d', $index),
                'category_id' => $categoryId,
                'inventory_code' => sprintf('QA02-%04d', $index),
                'status' => $published ? 'published' : 'draft',
                'summary' => 'QA-02 fixture',
                'show_in_totem' => 0,
                'is_active' => 1,
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
        }
        $this->db->table('collection_items')->insertBatch($items);

        $publishedItems = $this->db->table('collection_items')
            ->select('id, name')
            ->where('name >=', 'qa02-catalog-0000')
            ->where('name <', 'qa02-catalog-0120')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        $this->assertCount(120, $publishedItems);

        $translations = [];
        $slugs = [];
        foreach ($publishedItems as $item) {
            $id = (int) $item['id'];
            $translations[] = [
                'translatable_type' => 'collection_item',
                'translatable_id' => $id,
                'locale' => 'es',
                'field' => 'name',
                'value' => (string) $item['name'],
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
            $slugs[] = [
                'resource_type' => 'collection_item',
                'resource_id' => $id,
                'locale' => 'es',
                'slug' => (string) $item['name'],
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
        }
        $this->db->table('catalog_translations')->insertBatch($translations);
        $this->db->table('catalog_public_slugs')->insertBatch($slugs);

        $measurement = $this->measureGet('/api/v1/public-read/es/collection-items?fields=id,name&per_page=24');
        $measurement['response']->assertStatus(200);

        $body = json_decode((string) $measurement['response']->getJSON(), true);
        $this->assertSame(120, $body['meta']['total'] ?? null);
        $this->assertCount(24, $body['data'] ?? []);
        $this->assertLessThanOrEqual(6, $measurement['query_count'], $this->querySummary($measurement['queries']));
        $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $this->querySummary($measurement['queries']));

        $listingSql = $this->findQuery($measurement['queries'], 'FROM `collection_items` `ci`');
        $this->assertNotNull($listingSql, $this->querySummary($measurement['queries']));

        $plan = $this->db->query('EXPLAIN ' . $listingSql)->getResultArray();
        $listingPlan = $this->findPlanRow($plan, 'ci');
        $this->assertNotNull($listingPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->assertSame('idx_collection_items_public_listing', $listingPlan['key'] ?? null, json_encode($listingPlan));
        $this->assertNotSame('ALL', $listingPlan['type'] ?? null, json_encode($listingPlan));
    }

    /**
     * Regression for docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md
     * §2.D/§1.6: `testFiltersSearchAndOrdersStayWithinTheReadBudget` only
     * exercises a 1-item fixture, which gives no real EXPLAIN signal on
     * whether category_id/technique_id filtering needs its own composite —
     * with 1 row, any plan "works". This builds a volumetric, multi-category
     * fixture (mirroring testListingKeepsAStableQueryBudgetAndUsesThePublicIndex's
     * 600-row scale) and inspects the actual plan MySQL chooses for a
     * minority-selectivity category_id filter, deciding on the evidence
     * rather than adding an index "by symmetry" with event-domain.
     */
    public function testCategoryFilteredListingUsesAnIndexAtRealisticVolume(): void
    {
        $categoryIds = [];
        for ($c = 0; $c < 5; $c++) {
            $this->db->table('categories')->insert([
                'name' => 'QA-D category ' . $c,
                'slug' => 'qa-d-category-' . $c,
                'short_description' => 'QA-D fixture',
                'sort_order' => $c,
            ]);
            $categoryIds[] = (int) $this->db->insertID();
        }

        $items = [];
        for ($index = 0; $index < 600; $index++) {
            $items[] = [
                'name' => sprintf('qa-d-item-%04d', $index),
                'category_id' => $categoryIds[$index % 5],
                'inventory_code' => sprintf('QAD-%04d', $index),
                'status' => 'published',
                'summary' => 'QA-D fixture',
                'show_in_totem' => 0,
                'is_active' => 1,
                'created_at' => '2026-08-10 12:00:00',
                'updated_at' => '2026-08-10 12:00:00',
            ];
        }
        $this->db->table('collection_items')->insertBatch($items);

        // Filtering by one of 5 categories is ~20% selectivity — enough for
        // the optimizer to meaningfully prefer an index over a scan, unlike
        // the 1-row fixture in testFiltersSearchAndOrdersStayWithinTheReadBudget.
        $targetCategoryId = $categoryIds[2];
        $measurement = $this->measureGet(
            '/api/v1/public-read/es/collection-items?category_id=' . $targetCategoryId . '&fields=id,name&sort=name&per_page=24'
        );
        $measurement['response']->assertStatus(200);

        $body = json_decode((string) $measurement['response']->getJSON(), true);
        $this->assertSame(120, $body['meta']['total'] ?? null, json_encode($body));

        $listingSql = $this->findQuery($measurement['queries'], 'FROM `collection_items` `ci`');
        $this->assertNotNull($listingSql, $this->querySummary($measurement['queries']));
        $plan = $this->db->query('EXPLAIN ' . $listingSql)->getResultArray();
        $listingPlan = $this->findPlanRow($plan, 'ci');
        $this->assertNotNull($listingPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));

        // Evidence, not assumption: log the chosen plan so a future reader
        // can see what justified the (non-)decision below, the same way
        // QA-02's original EXPLAIN runs are documented in
        // docs/audits/2026-08-10-qa-02-explain-indexes.md.
        $this->assertNotSame('ALL', $listingPlan['type'] ?? null, json_encode($listingPlan));
    }

    /**
     * Regression for docs/audits/2026-08-13-auditoria-carga-fria-web-domains.md
     * hallazgo D: `created_at`/`updated_at` were missing from
     * `PublicReadController::LISTING_FIELDS` even though the base listing
     * query already selects them (`PUBLIC_COLUMNS`), causing a 500 for the
     * exact `fields=` combination `teatromuseo-web` sends for listing cards.
     * Confirms the fix is both functional (200, values present) and doesn't
     * change the QA-02 query budget/index plan — the two columns are already
     * scalar columns on `collection_items`, not a join.
     */
    public function testListingWithCreatedAtUpdatedAtFieldsStaysWithinBudgetAndKeepsTheIndex(): void
    {
        $this->createFixture();

        $measurement = $this->measureGet('/api/v1/public-read/en/collection-items?fields=id,name,created_at,updated_at&per_page=24');
        $measurement['response']->assertStatus(200);

        $body = json_decode((string) $measurement['response']->getJSON(), true);
        $this->assertArrayHasKey('created_at', $body['data'][0] ?? [], json_encode($body));
        $this->assertArrayHasKey('updated_at', $body['data'][0] ?? [], json_encode($body));
        $this->assertLessThanOrEqual(6, $measurement['query_count'], $this->querySummary($measurement['queries']));
        $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $this->querySummary($measurement['queries']));

        $listingSql = $this->findQuery($measurement['queries'], 'FROM `collection_items` `ci`');
        $this->assertNotNull($listingSql, $this->querySummary($measurement['queries']));
        $plan = $this->db->query('EXPLAIN ' . $listingSql)->getResultArray();
        $listingPlan = $this->findPlanRow($plan, 'ci');
        $this->assertNotNull($listingPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->assertSame('idx_collection_items_public_listing', $listingPlan['key'] ?? null, json_encode($listingPlan));
        $this->assertNotSame('ALL', $listingPlan['type'] ?? null, json_encode($listingPlan));
    }

    /**
     * Regression for docs/audits/2026-08-13-auditoria-carga-fria-web-domains.md
     * hallazgo C: an invalid `?fields=` value used to bubble up as a plain
     * \InvalidArgumentException, which ExceptionFormatter cannot map to a
     * status code and falls back to 500 — contradicting the OpenAPI contract
     * (`422 — Invalid query`, already documented for this route). It must
     * come back as 422 with a structured `errors` payload, not 500.
     */
    public function testInvalidFieldsParamReturns422WithStructuredErrorsNot500(): void
    {
        $this->createFixture();

        $measurement = $this->measureGet('/api/v1/public-read/en/collection-items?fields=id,not_a_real_field');
        $measurement['response']->assertStatus(422);

        $body = json_decode((string) $measurement['response']->getJSON(), true);
        $this->assertSame('error', $body['status'] ?? null, json_encode($body));
        $this->assertSame(['not_a_real_field'], $body['errors']['fields'] ?? null, json_encode($body));
    }

    public function testDetailFullProjectionStaysSetBasedAndExplainable(): void
    {
        $fixture = $this->createFixture();

        $measurement = $this->measureGet('/api/v1/public-read/en/collection-items/' . $fixture['item_id']);
        $measurement['response']->assertStatus(200);
        $body = json_decode((string) $measurement['response']->getJSON(), true);

        $this->assertSame($fixture['item_id'], $body['data']['id'] ?? null);
        $this->assertArrayHasKey('category', $body['data'] ?? []);
        $this->assertArrayHasKey('techniques', $body['data'] ?? []);
        $this->assertLessThanOrEqual(6, $measurement['query_count'], $this->querySummary($measurement['queries']));
        $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $this->querySummary($measurement['queries']));

        $detailSql = $this->findBaseCollectionQuery($measurement['queries']);
        $this->assertNotNull($detailSql, $this->querySummary($measurement['queries']));
        $plan = $this->db->query('EXPLAIN ' . $detailSql)->getResultArray();
        $detailPlan = $this->findPlanRow($plan, 'ci');
        $this->assertNotNull($detailPlan, json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->assertNotSame('ALL', $detailPlan['type'] ?? null, json_encode($detailPlan));
    }

    public function testFiltersSearchAndOrdersStayWithinTheReadBudget(): void
    {
        $fixture = $this->createFixture();
        $cases = [
            '/api/v1/public-read/en/collection-items?category_id=' . $fixture['category_id'] . '&fields=id,name&sort=name',
            '/api/v1/public-read/en/collection-items?technique_id=' . $fixture['technique_id'] . '&fields=id,name&sort=id',
            '/api/v1/public-read/en/collection-items?search=Budget%20translated&fields=id,name&sort=created_at',
            '/api/v1/public-read/en/collection-items?category=budget-category&fields=id,name&sort=name',
        ];

        foreach ($cases as $path) {
            $measurement = $this->measureGet($path);
            $measurement['response']->assertStatus(200);
            $this->assertLessThanOrEqual(6, $measurement['query_count'], $path . ' ' . $this->querySummary($measurement['queries']));
            $this->assertLessThanOrEqual(500.0, $this->totalDuration($measurement['queries']), $path . ' ' . $this->querySummary($measurement['queries']));

            $listingSql = $this->findQuery($measurement['queries'], 'FROM `collection_items` `ci`');
            $this->assertNotNull($listingSql, $path . ' ' . $this->querySummary($measurement['queries']));
            $plan = $this->db->query('EXPLAIN ' . $listingSql)->getResultArray();
            $listingPlan = $this->findPlanRow($plan, 'ci');
            $this->assertNotNull($listingPlan, $path . ' ' . json_encode($plan, JSON_UNESCAPED_SLASHES));
            $this->assertNotSame('ALL', $listingPlan['type'] ?? null, $path . ' ' . json_encode($listingPlan));
        }
    }

    public function testSparseNameFieldOnlyLoadsTheRequestedTranslation(): void
    {
        $this->createFixture();

        $measurement = $this->measureGet('/api/v1/public-read/en/collection-items?fields=name');
        $measurement['response']->assertStatus(200);
        $translationSql = $this->findQueryContaining($measurement['queries'], 'FROM `catalog_translations`');

        $this->assertNotNull($translationSql, $this->querySummary($measurement['queries']));
        $this->assertStringContainsString("'name'", (string) $translationSql);
        $this->assertStringNotContainsString("'summary'", (string) $translationSql);
        $this->assertStringNotContainsString("'contenido'", (string) $translationSql);
        $this->assertStringNotContainsString('FROM `categories`', $this->querySummary($measurement['queries']));
        $this->assertStringNotContainsString('FROM `collection_item_technique`', $this->querySummary($measurement['queries']));
    }

    /** @return array{category_id:int,technique_id:int,item_id:int,slug:string} */
    private function createFixture(): array
    {
        $this->db->table('categories')->insert([
            'name' => 'Budget category',
            'slug' => 'budget-category',
            'short_description' => 'Budget fixture',
            'sort_order' => 1,
        ]);
        $categoryId = (int) $this->db->insertID();

        $this->db->table('techniques')->insert([
            'name' => 'Budget technique',
            'slug' => 'budget-technique',
            'summary' => 'Budget fixture',
            'sort_order' => 1,
        ]);
        $techniqueId = (int) $this->db->insertID();

        $this->db->table('collection_items')->insert([
            'name' => 'Budget item',
            'category_id' => $categoryId,
            'inventory_code' => 'BUDGET-001',
            'status' => 'published',
            'summary' => 'Budget summary',
            'show_in_totem' => 0,
            'is_active' => 1,
            'created_at' => '2026-08-10 12:00:00',
            'updated_at' => '2026-08-10 12:00:00',
        ]);
        $itemId = (int) $this->db->insertID();

        $this->db->table('collection_item_technique')->insert([
            'collection_item_id' => $itemId,
            'technique_id' => $techniqueId,
        ]);
        $this->db->table('catalog_translations')->insertBatch([
            [
                'translatable_type' => 'collection_item',
                'translatable_id' => $itemId,
                'locale' => 'en',
                'field' => 'name',
                'value' => 'Budget translated',
            ],
            [
                'translatable_type' => 'category',
                'translatable_id' => $categoryId,
                'locale' => 'en',
                'field' => 'name',
                'value' => 'Budget category translated',
            ],
            [
                'translatable_type' => 'technique',
                'translatable_id' => $techniqueId,
                'locale' => 'en',
                'field' => 'name',
                'value' => 'Budget technique translated',
            ],
        ]);
        $this->db->table('catalog_public_slugs')->insert([
            'resource_type' => 'collection_item',
            'resource_id' => $itemId,
            'locale' => 'en',
            'slug' => 'budget-item',
        ]);

        return [
            'category_id' => $categoryId,
            'technique_id' => $techniqueId,
            'item_id' => $itemId,
            'slug' => 'budget-item',
        ];
    }

    /**
     * @return array{response: \CodeIgniter\Test\TestResponse, queries: list<array{sql:string,duration_ms:float}>, query_count:int}
     */
    private function measureGet(string $path): array
    {
        $queries = [];
        $listener = static function (mixed $query) use (&$queries): void {
            if (! $query instanceof QueryInterface) {
                return;
            }

            $sql = trim((string) $query);
            if (str_starts_with(strtoupper($sql), 'SELECT')) {
                $queries[] = [
                    'sql' => $sql,
                    'duration_ms' => (float) $query->getDuration(6) * 1000,
                ];
            }
        };

        Events::on('DBQuery', $listener, Events::PRIORITY_LOW);
        $startedAt = microtime(true);
        try {
            $response = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])->get($path);
        } finally {
            Events::removeListener('DBQuery', $listener);
        }

        $elapsedMs = (microtime(true) - $startedAt) * 1000;
        $this->assertLessThan(1000, $elapsedMs, $this->querySummary($queries));

        return ['response' => $response, 'queries' => $queries, 'query_count' => count($queries)];
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function findQuery(array $queries, string $fragment): ?string
    {
        foreach ($queries as $query) {
            if (str_contains($query['sql'], $fragment) && str_contains($query['sql'], 'ORDER BY')) {
                return $query['sql'];
            }
        }

        return null;
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function findQueryContaining(array $queries, string $fragment): ?string
    {
        foreach ($queries as $query) {
            if (str_contains($query['sql'], $fragment)) {
                return $query['sql'];
            }
        }

        return null;
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function findBaseCollectionQuery(array $queries): ?string
    {
        foreach ($queries as $query) {
            if (
                str_contains($query['sql'], 'FROM `collection_items` `ci`')
                && ! str_contains($query['sql'], 'catalog_public_slugs')
            ) {
                return $query['sql'];
            }
        }

        return null;
    }

    /** @param list<array<string,mixed>> $plan */
    private function findPlanRow(array $plan, string $table): ?array
    {
        foreach ($plan as $row) {
            if (($row['table'] ?? null) === $table) {
                return $row;
            }
        }

        return null;
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function querySummary(array $queries): string
    {
        return json_encode($queries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'no queries';
    }

    /** @param list<array{sql:string,duration_ms:float}> $queries */
    private function totalDuration(array $queries): float
    {
        $total = 0.0;
        foreach ($queries as $query) {
            $total += $query['duration_ms'];
        }

        return $total;
    }
}
