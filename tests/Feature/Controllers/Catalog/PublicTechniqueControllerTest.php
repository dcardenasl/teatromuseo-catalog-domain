<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Catalog;

use App\DTO\Request\Catalog\TechniqueCreateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Feature coverage for PublicTechniqueController, gated by 'webappkey' (not
 * JWT/domainauth). Previously had zero references anywhere in tests/
 * (LAYER-07) — pattern mirrors PublicCollectionItemControllerTest.
 *
 * @internal
 */
final class PublicTechniqueControllerTest extends CIUnitTestCase
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
        $this->db->table('collection_item_technique')->where('technique_id >', 0)->delete();
        $this->db->table('techniques')->where('id >', 0)->delete();
    }

    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
        parent::tearDown();
    }

    public function testIndexRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public/catalog/techniques');

        $result->assertStatus(401);
    }

    public function testIndexRejectsInvalidAppKey(): void
    {
        $result = $this->withHeaders(['X-App-Key' => 'not-the-configured-key'])
            ->get('/api/v1/public/catalog/techniques');

        $result->assertStatus(401);
    }

    public function testIndexReturnsCreatedTechniques(): void
    {
        $this->createTechnique('Grabado', 'grabado');
        $this->createTechnique('Serigrafía', 'serigrafia');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/techniques');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $names = array_column($body['data'], 'name');
        $this->assertContains('Grabado', $names);
        $this->assertContains('Serigrafía', $names);
    }

    public function testIndexRejectsPerPageOverTheAllowedLimit(): void
    {
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/techniques?per_page=999');

        $result->assertStatus(422);
    }

    public function testShowRejectsMissingAppKey(): void
    {
        $result = $this->get('/api/v1/public/catalog/techniques/anything');

        $result->assertStatus(401);
    }

    public function testShowResolvesBySlug(): void
    {
        $created = $this->createTechnique('Grabado', 'grabado');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/techniques/grabado');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        // Unlike PublicCollectionItemController's show(), techniques have no
        // domain column named "status", so ApiResponse::handleArray() takes
        // its normal branch and wraps the payload under 'data' (the
        // collection-item response only looks unwrapped at the top level
        // because its own 'status' column — draft/published — happens to
        // collide with the envelope's 'status' key).
        $this->assertSame('success', $body['status']);
        $this->assertSame((int) $created['id'], $body['data']['id']);
        $this->assertSame('grabado', $body['data']['slug']);
    }

    public function testShowResolvesById(): void
    {
        $created = $this->createTechnique('Grabado', 'grabado');

        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/techniques/' . $created['id']);

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame((int) $created['id'], $body['data']['id']);
    }

    public function testShowReturns404ForUnknownSlug(): void
    {
        $result = $this->withHeaders(['X-App-Key' => self::WEB_API_KEY])
            ->get('/api/v1/public/catalog/techniques/does-not-exist');

        $result->assertStatus(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function createTechnique(string $name, string $slug): array
    {
        return Services::techniqueService(false)->store(
            Services::requestDtoFactory()->make(TechniqueCreateRequestDTO::class, [
                'name' => $name,
                'slug' => $slug,
                'summary' => 'Resumen de ' . $name,
                'sort_order' => 1,
            ])
        )->toArray();
    }
}
