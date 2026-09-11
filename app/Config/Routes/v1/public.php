<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->get(
    'content/(.*)',
    '\\App\\Controllers\\Api\\V1\\Content\\ContentProxyController::forward/$1',
);

$routes->get(
    'public-proxy/(.*)',
    '\\App\\Controllers\\Api\\V1\\PublicProxyController::forward/$1',
);

// This example is server-to-server by design. The BFF validates X-App-Key,
// then forwards it to the domain's own public route gate.
$routes->get(
    'public-read/pages/(:segment)/(.+)',
    '\\App\\Controllers\\Api\\V1\\PublicRead\\PageBootstrapController::show/$1/$2',
    ['filter' => ['webappkey', 'throttle']],
);
