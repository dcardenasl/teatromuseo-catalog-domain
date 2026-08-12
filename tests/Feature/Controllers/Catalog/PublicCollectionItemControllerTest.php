<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Catalog;

use App\DTO\Request\Catalog\CollectionItemCreateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class PublicCollectionItemControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const WEB_API_KEY = 'test-web-api-key';

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    private int $categoryId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('WEB_API_KEY=' . self::WEB_API_KEY);
        $_ENV['WEB_API_KEY'] = self::WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::WEB_API_KEY;

        $this->db->table('catalog_public_slugs')->truncate();
        $this->db->table('catalog_translations')->truncate();
        $this->db->table('collection_item_technique')->where('collection_item_id >', 0)->delete();
        $this->db->table('collection_items')->where('id >', 0)->delete();
        $this->db->table('categories')->where('id >', 0)->delete();

        $this->db->table('categories')->insert([
            'name' => 'Marionetas',
            'slug' => 'marionetas',
            'short_description' => 'Categoría de prueba',
            'sort_order' => 1,
        ]);
        $this->categoryId = (int) $this->db->insertID();
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testShowRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public/catalog/collection-items/anything');

        $result->assertStatus(401);
    }

    public function testShowResolvesTheGeneratedSlug(): void
    {
        $created = $this->createItem('Payaso Histórico');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/collection-items/payaso-historico');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame((int) $created['id'], $body['id']);
        $this->assertSame('payaso-historico', $body['slugs']['es'] ?? null);
    }

    public function testShowStillResolvesTheLegacyInventoryCode(): void
    {
        $created = $this->createItem('Payaso Histórico', 'INV-001');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/collection-items/INV-001');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame((int) $created['id'], $body['id']);
    }

    public function testShowReturns404ForInactiveItems(): void
    {
        $created = $this->createItem('Pieza Retirada');
        $this->db->table('collection_items')->where('id', (int) $created['id'])->update(['is_active' => 0]);

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/collection-items/pieza-retirada');

        $result->assertStatus(404);
    }

    public function testShowSupportsStatusSparseField(): void
    {
        $this->createItem('Pieza Publicada');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/collection-items/pieza-publicada?fields=status');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('published', $body['status'] ?? null);
        $this->assertArrayNotHasKey('name', $body);
    }

    public function testPublicReadListingUsesVersionedEnvelope(): void
    {
        $this->createItem('Pieza PublicRead');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/collection-items?fields=id,name,slug');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertPublicReadEnvelope($body, 'catalog');
        $this->assertSame('es', $body['meta']['locale'] ?? null);
        $this->assertSame('Pieza PublicRead', $body['data'][0]['name'] ?? null);
    }

    public function testPublicReadListingFallsBackToTheDefaultLocale(): void
    {
        $this->createItem('Pieza fallback');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items?fields=id,name');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertPublicReadEnvelope($body, 'catalog');
        $this->assertSame('Pieza fallback', $body['data'][0]['name'] ?? null);
    }

    public function testPublicReadListingRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public-read/es/collection-items');

        $result->assertStatus(401);
    }

    public function testPublicReadDetailKeepsLegacySlugLookupInsideNewContract(): void
    {
        $this->createItem('Pieza PublicRead Detalle');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/collection-items/pieza-publicread-detalle');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertTrue($body['ok'] ?? false);
        $this->assertSame('pieza-publicread-detalle', $body['data']['slug'] ?? null);
        $this->assertArrayHasKey('techniques', $body['data'] ?? []);
    }

    public function testPublicReadSearchIncludesRequestedLocaleTranslations(): void
    {
        $created = $this->createItem('Pieza sin traducción');
        $this->db->table('catalog_translations')->insert([
            'translatable_type' => 'collection_item',
            'translatable_id' => $created['id'],
            'locale' => 'en',
            'field' => 'name',
            'value' => 'Translated Puppet',
        ]);

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items?search=Translated%20Puppet&fields=id,name');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame(1, $body['meta']['total'] ?? null);
        $this->assertSame((int) $created['id'], $body['data'][0]['id'] ?? null);
        $this->assertSame('Translated Puppet', $body['data'][0]['name'] ?? null);
    }

    public function testPublicReadRevisionChangesWhenASelectedTranslationChanges(): void
    {
        $created = $this->createItem('Pieza revisada');
        $this->db->table('catalog_translations')->insert([
            'translatable_type' => 'collection_item',
            'translatable_id' => $created['id'],
            'locale' => 'en',
            'field' => 'name',
            'value' => 'First publication',
        ]);

        $first = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items?fields=id,name');
        $firstBody = json_decode((string) $first->getJSON(), true);

        $this->db->table('catalog_translations')
            ->where('translatable_type', 'collection_item')
            ->where('translatable_id', $created['id'])
            ->where('locale', 'en')
            ->where('field', 'name')
            ->update(['value' => 'Second publication']);

        $second = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items?fields=id,name');
        $secondBody = json_decode((string) $second->getJSON(), true);

        $this->assertNotSame(
            $firstBody['meta']['source_revision'] ?? null,
            $secondBody['meta']['source_revision'] ?? null,
        );
        $this->assertSame('Second publication', $secondBody['data'][0]['name'] ?? null);
    }

    public function testPublicReadDetailPrefersRequestedLocaleSlugOverFallbackSlug(): void
    {
        $fallback = $this->createItem('Fallback slug target', 'INV-FALLBACK');
        $requested = $this->createItem('Requested slug target', 'INV-REQUESTED');

        $this->db->table('catalog_public_slugs')
            ->whereIn('resource_id', [(int) $fallback['id'], (int) $requested['id']])
            ->delete();
        $this->db->table('catalog_public_slugs')->insertBatch([
            [
                'resource_type' => 'collection_item',
                'resource_id' => (int) $fallback['id'],
                'locale' => 'es',
                'slug' => 'gala',
            ],
            [
                'resource_type' => 'collection_item',
                'resource_id' => (int) $requested['id'],
                'locale' => 'en',
                'slug' => 'gala',
            ],
        ]);

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items/gala?fields=id');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame((int) $requested['id'], $body['data']['id'] ?? null);
    }

    public function testPublicReadDetailPrefersInventoryCodeOverSlugCollision(): void
    {
        $inventoryWinner = $this->createItem('Inventory winner', 'CODE-AMBIGUOUS');
        $slugLoser = $this->createItem('Slug loser', 'INV-SLUG-LOSER');

        $this->db->table('catalog_public_slugs')
            ->where('resource_id', (int) $slugLoser['id'])
            ->delete();
        $this->db->table('catalog_public_slugs')->insert([
            'resource_type' => 'collection_item',
            'resource_id' => (int) $slugLoser['id'],
            'locale' => 'en',
            'slug' => 'CODE-AMBIGUOUS',
        ]);

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items/CODE-AMBIGUOUS?fields=id');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame((int) $inventoryWinner['id'], $body['data']['id'] ?? null);
    }

    public function testPublicReadLocalizesCategoryAndTechniqueInOneDetailProjection(): void
    {
        $this->db->table('catalog_translations')->insert([
            'translatable_type' => 'category',
            'translatable_id' => $this->categoryId,
            'locale' => 'en',
            'field' => 'name',
            'value' => 'Puppets',
        ]);
        $this->db->table('techniques')->insert([
            'name' => 'Madera',
            'slug' => 'madera',
            'summary' => 'Resumen madera',
            'sort_order' => 1,
        ]);
        $techniqueId = (int) $this->db->insertID();
        $this->db->table('catalog_translations')->insert([
            'translatable_type' => 'technique',
            'translatable_id' => $techniqueId,
            'locale' => 'en',
            'field' => 'name',
            'value' => 'Wood',
        ]);
        $created = $this->createItem('Pieza localizada', 'INV-LOCALIZED');
        $this->db->table('collection_item_technique')->insert([
            'collection_item_id' => (int) $created['id'],
            'technique_id' => $techniqueId,
        ]);

        $slug = (string) ($created['slugs']['es'] ?? '');
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/en/collection-items/' . $slug . '?fields=id,category,techniques');

        $result->assertStatus(200);
        $body = json_decode((string) $result->getJSON(), true);
        $this->assertSame('Puppets', $body['data']['category']['name'] ?? null);
        $this->assertSame('Wood', $body['data']['techniques'][0]['name'] ?? null);
    }

    public function testPublicReadFiltersDoNotExposeSoftDeletedRelations(): void
    {
        $this->db->table('categories')->insert([
            'name' => 'Deleted category',
            'slug' => 'deleted-category',
            'short_description' => 'Hidden',
            'deleted_at' => '2026-08-11 12:00:00',
        ]);
        $deletedCategoryId = (int) $this->db->insertID();
        $categoryItem = $this->createItem('Item in deleted category', 'INV-DELETED-CATEGORY');
        $this->db->table('collection_items')->where('id', (int) $categoryItem['id'])->update([
            'category_id' => $deletedCategoryId,
        ]);

        $this->db->table('techniques')->insert([
            'name' => 'Deleted technique',
            'slug' => 'deleted-technique',
            'deleted_at' => '2026-08-11 12:00:00',
        ]);
        $deletedTechniqueId = (int) $this->db->insertID();
        $techniqueItem = $this->createItem('Item in deleted technique', 'INV-DELETED-TECHNIQUE');
        $this->db->table('collection_item_technique')->insert([
            'collection_item_id' => (int) $techniqueItem['id'],
            'technique_id' => $deletedTechniqueId,
        ]);

        $categoryResult = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/collection-items?category_id=' . $deletedCategoryId . '&fields=id');
        $categoryResult->assertStatus(200);
        $categoryBody = json_decode((string) $categoryResult->getJSON(), true);
        $this->assertSame(0, $categoryBody['meta']['total'] ?? null);

        $techniqueResult = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/collection-items?technique_id=' . $deletedTechniqueId . '&fields=id');
        $techniqueResult->assertStatus(200);
        $techniqueBody = json_decode((string) $techniqueResult->getJSON(), true);
        $this->assertSame(0, $techniqueBody['meta']['total'] ?? null);
    }

    public function testPublicReadRejectsPagesBeyondTheConfiguredLimit(): void
    {
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public-read/es/collection-items?page=10001');

        $result->assertStatus(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function createItem(string $name, ?string $inventoryCode = null): array
    {
        return Services::collectionItemService(false)->store(
            Services::requestDtoFactory()->make(CollectionItemCreateRequestDTO::class, [
                'name' => $name,
                'category_id' => $this->categoryId,
                'inventory_code' => $inventoryCode,
                'status' => 'published',
                'summary' => 'Resumen de ' . $name,
                'show_in_totem' => 0,
                'is_active' => 1,
            ])
        )->toArray();
    }

    /** @param array<string, mixed> $body */
    private function assertPublicReadEnvelope(array $body, string $domain): void
    {
        $this->assertTrue($body['ok'] ?? false);
        $this->assertSame(1, $body['version'] ?? null);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['meta'] ?? null);
        $this->assertSame($domain, $body['source']['domain'] ?? null);
        $this->assertSame('fresh', $body['source']['state'] ?? null);
        $this->assertFalse($body['source']['stale'] ?? true);
        $this->assertIsArray($body['messages'] ?? null);
    }
}
