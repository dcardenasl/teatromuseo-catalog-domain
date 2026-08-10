<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('public-read', ['namespace' => '\App\Controllers\Api\V1\Catalog', 'filter' => ['webappkey', 'throttle', 'correlationid', 'publicTelemetry']], static function ($routes): void {
    $routes->get('(:segment)/collection-items', 'PublicReadController::index/$1');
    $routes->get('(:segment)/collection-items/(:any)', 'PublicReadController::show/$1/$2');
});
