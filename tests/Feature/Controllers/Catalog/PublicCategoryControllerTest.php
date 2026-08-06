<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Catalog;

use App\DTO\Request\Catalog\CategoryCreateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Feature coverage for PublicCategoryController, gated by 'webappkey' (not
 * JWT/domainauth). Previously had zero references anywhere in tests/
 * (LAYER-07) — pattern mirrors PublicCollectionItemControllerTest.
 *
 * @internal
 */
final class PublicCategoryControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const WEB_API_KEY = 'test-web-api-key';

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        putenv('WEB_API_KEY=' . self::WEB_API_KEY);
        $_ENV['WEB_API_KEY'] = self::WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::WEB_API_KEY;

        $this->db->table('catalog_translations')->truncate();
        $this->db->table('categories')->where('id >', 0)->delete();
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testIndexRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public/catalog/categories');

        $result->assertStatus(401);
    }

    public function testIndexRejectsInvalidAppKey(): void
    {
        $result = $this->withHeaders(['X-App-Key' => 'not-the-configured-key'])
            ->get('/api/v1/public/catalog/categories');

        $result->assertStatus(401);
    }

    public function testIndexReturnsCreatedCategories(): void
    {
        $this->createCategory('Marionetas', 'marionetas');
        $this->createCategory('Escenografía', 'escenografia');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/categories');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $names = array_column($body['data'], 'name');
        $this->assertContains('Marionetas', $names);
        $this->assertContains('Escenografía', $names);
        $this->assertArrayHasKey('total', $body['meta']);
    }

    public function testIndexRejectsPerPageOverTheAllowedLimit(): void
    {
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/categories?per_page=999');

        $result->assertStatus(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function createCategory(string $name, string $slug): array
    {
        return Services::categoryService(false)->store(
            Services::requestDtoFactory()->make(CategoryCreateRequestDTO::class, [
                'name' => $name,
                'slug' => $slug,
                'short_description' => 'Descripción de ' . $name,
                'sort_order' => 1,
            ])
        )->toArray();
    }
}
