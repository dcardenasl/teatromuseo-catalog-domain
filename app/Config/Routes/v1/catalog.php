<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('catalog', ['namespace' => '\App\Controllers\Api\V1\Catalog'], function ($routes): void {
    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        // Resource routes will be injected here
        $routes->group('', ['filter' => 'permission:cms.category.read'], function ($routes): void {
            $routes->get('categories', 'CategoryController::index');
            $routes->get('categories/(:num)', 'CategoryController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:cms.category.create'], function ($routes): void {
            $routes->post('categories', 'CategoryController::create');
        });
        $routes->group('', ['filter' => 'permission:cms.category.update'], function ($routes): void {
            $routes->put('categories/(:num)', 'CategoryController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:cms.category.delete'], function ($routes): void {
            $routes->delete('categories/(:num)', 'CategoryController::delete/$1');
        });
        $routes->group('', ['filter' => 'permission:cms.technique.read'], function ($routes): void {
            $routes->get('techniques', 'TechniqueController::index');
            $routes->get('techniques/(:num)', 'TechniqueController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:cms.technique.create'], function ($routes): void {
            $routes->post('techniques', 'TechniqueController::create');
        });
        $routes->group('', ['filter' => 'permission:cms.technique.update'], function ($routes): void {
            $routes->put('techniques/(:num)', 'TechniqueController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:cms.technique.delete'], function ($routes): void {
            $routes->delete('techniques/(:num)', 'TechniqueController::delete/$1');
        });
    });
});
