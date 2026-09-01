<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('catalog', ['namespace' => '\App\Controllers\Api\V1\Catalog'], function ($routes): void {
    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        $routes->get('dashboard/summary', 'DashboardSummaryController::index');
        $routes->post('sort-orders', 'SortOrderController::reorder');

        // Resource routes will be injected here
        $routes->group('', ['filter' => 'permission:catalog.category.read'], function ($routes): void {
            $routes->get('categories', 'CategoryController::index');
            $routes->get('categories/(:num)', 'CategoryController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.category.create'], function ($routes): void {
            $routes->post('categories', 'CategoryController::create');
        });
        $routes->group('', ['filter' => 'permission:catalog.category.update'], function ($routes): void {
            $routes->put('categories/(:num)', 'CategoryController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.category.delete'], function ($routes): void {
            $routes->delete('categories/(:num)', 'CategoryController::delete/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.technique.read'], function ($routes): void {
            $routes->get('techniques', 'TechniqueController::index');
            $routes->get('techniques/(:num)', 'TechniqueController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.technique.create'], function ($routes): void {
            $routes->post('techniques', 'TechniqueController::create');
        });
        $routes->group('', ['filter' => 'permission:catalog.technique.update'], function ($routes): void {
            $routes->put('techniques/(:num)', 'TechniqueController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.technique.delete'], function ($routes): void {
            $routes->delete('techniques/(:num)', 'TechniqueController::delete/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.collectionItem.read'], function ($routes): void {
            $routes->get('collection-items', 'CollectionItemController::index');
            $routes->get('collection-items/(:num)', 'CollectionItemController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.collectionItem.create'], function ($routes): void {
            $routes->post('collection-items', 'CollectionItemController::create');
        });
        $routes->group('', ['filter' => 'permission:catalog.collectionItem.update'], function ($routes): void {
            $routes->put('collection-items/(:num)', 'CollectionItemController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:catalog.collectionItem.delete'], function ($routes): void {
            $routes->delete('collection-items/(:num)', 'CollectionItemController::delete/$1');
        });
    });
});
