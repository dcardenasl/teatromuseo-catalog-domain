<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('public/catalog', ['namespace' => '\App\Controllers\Api\V1\Catalog', 'filter' => ['webappkey', 'throttle']], function ($routes): void {
    // Techniques
    $routes->get('techniques', 'PublicTechniqueController::index');
    $routes->get('techniques/(:any)', 'PublicTechniqueController::show/$1');

    // Collection Items
    // `GET public/catalog/collection-items` (bare listing) removed
    // 2026-08-13 — its controller method (index()) resolved media per item
    // in a foreach (one HTTP call to the Hub per item, no batching).
    // teatromuseo-web migrated to the BFF public-read/{lang}/collection-items;
    // confirmed zero remaining callers across teatromuseo-web/bff/admin/totem.
    // See docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md §2.5/§2.F.
    $routes->get('collection-items/(:any)', 'PublicCollectionItemController::show/$1');
});
