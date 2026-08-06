<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\CatalogPublicSlugModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Localization\RequestLocaleResolver;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;
use dcardenasl\Ci4ApiCore\Services\HasPublicSlugs;

final class HasPublicSlugsIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->table('catalog_public_slugs')->truncate();
    }

    public function testBatchAttachmentPreservesTheEntitySlugWithoutAPublicSlug(): void
    {
        $entity = (object) ['id' => 10, 'slug' => 'legacy-item'];

        $result = (new HasPublicSlugsHarness($this->store()))->attachMany([$entity]);

        $this->assertSame([], $result[0]->slugs);
        $this->assertSame('legacy-item', $result[0]->slug);
    }

    public function testSingleAttachmentPreservesTheEntitySlugWithoutAPublicSlug(): void
    {
        $entity = (object) ['id' => 11, 'slug' => 'legacy-single'];

        (new HasPublicSlugsHarness($this->store()))->attachOne($entity);

        $this->assertSame([], $entity->slugs);
        $this->assertSame('legacy-single', $entity->slug);
    }

    public function testLocalizedPublicSlugStillTakesPrecedenceOverTheEntitySlug(): void
    {
        $this->db->table('catalog_public_slugs')->insert([
            'resource_type' => 'collection_item',
            'resource_id' => 12,
            'locale' => 'es',
            'slug' => 'public-item',
        ]);
        $entity = (object) ['id' => 12, 'slug' => 'legacy-item'];

        (new HasPublicSlugsHarness($this->store()))->attachOne($entity);

        $this->assertSame(['es' => 'public-item'], $entity->slugs);
        $this->assertSame('public-item', $entity->slug);
    }

    private function store(): PublicSlugStore
    {
        return new PublicSlugStore(
            new CatalogPublicSlugModel(),
            new SlugGenerator(),
            new RequestLocaleResolver(),
        );
    }
}

final class HasPublicSlugsHarness
{
    use HasPublicSlugs;

    public function __construct(PublicSlugStore $slugStore)
    {
        $this->slugStore = $slugStore;
        $this->slugResourceType = 'collection_item';
        $this->slugSourceField = 'name';
    }

    /**
     * @param array<int, object> $entities
     * @return array<int, object>
     */
    public function attachMany(array $entities): array
    {
        return $this->attachSlugs($entities);
    }

    public function attachOne(object $entity): void
    {
        $this->attachSlugsToEntity($entity);
    }
}
