<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('public/catalog', ['namespace' => '\App\Controllers\Api\V1\Catalog', 'filter' => ['webappkey', 'throttle']], function ($routes): void {
    // Categories
    $routes->get('categories', 'PublicCategoryController::index');

    // Techniques
    $routes->get('techniques', 'PublicTechniqueController::index');
    $routes->get('techniques/(:any)', 'PublicTechniqueController::show/$1');

    // Collection Items
    $routes->get('collection-items', 'PublicCollectionItemController::index');
    $routes->get('collection-items/(:any)', 'PublicCollectionItemController::show/$1');
});
