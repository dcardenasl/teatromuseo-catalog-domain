<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\Catalog\FileUsageService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * FileUsageService had zero test coverage before LAYER-07. Runs against a
 * real DB (via DatabaseTestTrait) rather than mocks, since the behaviour
 * under test IS the SQL prefilter + PHP-side exact-membership check on the
 * gallery_file_ids CSV column — see the class docblock for why a naive
 * substring match would false-positive (file 1 matching "21" or "12,1").
 *
 * @internal
 */
final class FileUsageServiceTest extends CIUnitTestCase
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

    public function testReportsNoUsagesWhenNoItemReferencesTheFile(): void
    {
        $this->insertItem('Sin Archivo', null, null);

        $usages = $this->service()->getUsagesByHubFileId(999);

        $this->assertSame([], $usages);
    }

    public function testReportsCoverUsage(): void
    {
        $id = $this->insertItem('Con Portada', 5, null);

        $usages = $this->service()->getUsagesByHubFileId(5);

        $this->assertSame([
            ['source' => 'domain', 'resource' => 'collection_items', 'resource_id' => $id, 'role' => 'cover', 'label' => 'Con Portada'],
        ], $usages);
    }

    public function testReportsGalleryUsage(): void
    {
        $id = $this->insertItem('Con Galería', null, '10,11,12');

        $usages = $this->service()->getUsagesByHubFileId(11);

        $this->assertSame([
            ['source' => 'domain', 'resource' => 'collection_items', 'resource_id' => $id, 'role' => 'gallery', 'label' => 'Con Galería'],
        ], $usages);
    }

    public function testReportsBothCoverAndGalleryUsageForTheSameFile(): void
    {
        $id = $this->insertItem('Doble Uso', 7, '7,8,9');

        $usages = $this->service()->getUsagesByHubFileId(7);

        $this->assertSame([
            ['source' => 'domain', 'resource' => 'collection_items', 'resource_id' => $id, 'role' => 'cover', 'label' => 'Doble Uso'],
            ['source' => 'domain', 'resource' => 'collection_items', 'resource_id' => $id, 'role' => 'gallery', 'label' => 'Doble Uso'],
        ], $usages);
    }

    /**
     * Regression guard for the substring-false-positive the CSV column is
     * prone to: file id 1 must NOT match a gallery of "21,12" (which
     * contains "1" as a substring of "21" and "12", not as an exact CSV
     * member), and must NOT match cover_file_id 21.
     */
    public function testDoesNotFalsePositiveOnCsvSubstringMatches(): void
    {
        $this->insertItem('Falso Positivo Portada', 21, null);
        $this->insertItem('Falso Positivo Galería', null, '21,12');

        $usages = $this->service()->getUsagesByHubFileId(1);

        $this->assertSame([], $usages);
    }

    public function testExcludesSoftDeletedItems(): void
    {
        $id = $this->insertItem('Retirado', 42, null);
        $this->db->table('collection_items')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        $usages = $this->service()->getUsagesByHubFileId(42);

        $this->assertSame([], $usages);
    }

    private function service(): FileUsageService
    {
        return Services::fileUsageService(false);
    }

    private function insertItem(string $name, ?int $coverFileId, ?string $galleryFileIds): int
    {
        $this->db->table('collection_items')->insert([
            'name' => $name,
            'category_id' => $this->categoryId,
            'status' => 'published',
            'cover_file_id' => $coverFileId,
            'gallery_file_ids' => $galleryFileIds,
            'show_in_totem' => 0,
            'is_active' => 1,
        ]);

        return (int) $this->db->insertID();
    }
}
