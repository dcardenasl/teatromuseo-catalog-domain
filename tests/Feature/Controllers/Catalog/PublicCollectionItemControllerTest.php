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
}
