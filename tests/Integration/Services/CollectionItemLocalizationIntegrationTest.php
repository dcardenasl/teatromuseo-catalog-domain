<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\DTO\Request\Catalog\CollectionItemCreateRequestDTO;
use App\DTO\Request\Catalog\CollectionItemUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;

/**
 * @internal
 */
final class CollectionItemLocalizationIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    private int $categoryId = 0;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function testStorePersistsTranslationsAndGeneratesLocalizedSlugs(): void
    {
        $created = $this->createItem('Payaso Histórico', [
            ['locale' => 'es', 'name' => 'Payaso Histórico', 'summary' => 'Resumen es'],
            ['locale' => 'en', 'name' => 'Historic Clown', 'summary' => 'Summary en'],
        ]);

        $this->assertSame(['en' => 'historic-clown', 'es' => 'payaso-historico'], $created['slugs']);
        $this->assertSame('Payaso Histórico', $created['localized']['name']);
        $this->assertCount(2, $created['translations']);
    }

    public function testSlugsStayStableWhenTheNameChanges(): void
    {
        $created = $this->createItem('Payaso Histórico');

        $updated = Services::collectionItemService(false)->update(
            (int) $created['id'],
            Services::requestDtoFactory()->make(CollectionItemUpdateRequestDTO::class, [
                'name' => 'Payaso Histórico Restaurado',
            ])
        )->toArray();

        $this->assertSame($created['slugs'], $updated['slugs']);
    }

    public function testGetPublicActiveResolvesSlugInventoryCodeAndId(): void
    {
        $created = $this->createItem('Payaso Histórico', [], 'INV-001');
        $service = Services::collectionItemService(false);

        $bySlug = $service->getPublicActive('payaso-historico');
        $this->assertSame((int) $created['id'], $bySlug['id']);

        $byCode = $service->getPublicActive('INV-001');
        $this->assertSame((int) $created['id'], $byCode['id']);

        $byId = $service->getPublicActive((string) $created['id']);
        $this->assertSame((int) $created['id'], $byId['id']);
        $this->assertArrayHasKey('techniques', $byId);
    }

    public function testGetPublicActiveHidesInactiveItems(): void
    {
        $created = $this->createItem('Pieza Retirada');
        $this->db->table('collection_items')->where('id', (int) $created['id'])->update(['is_active' => 0]);

        $this->expectException(NotFoundException::class);
        Services::collectionItemService(false)->getPublicActive('pieza-retirada');
    }

    /**
     * @param list<array<string, mixed>> $translations
     * @return array<string, mixed>
     */
    private function createItem(string $name, array $translations = [], ?string $inventoryCode = null): array
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
                'translations' => $translations,
            ])
        )->toArray();
    }
}
