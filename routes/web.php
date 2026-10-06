<?php

declare(strict_types=1);

use Adheri\Http\Controllers\PublicSite\SiteController;

/** @var Adheri\Router $router */

$router->get('/', [SiteController::class, 'home']);
$router->get('/about', [SiteController::class, 'about']);
$router->get('/programs', [SiteController::class, 'programs']);
$router->get('/gallery', [SiteController::class, 'gallery']);
$router->get('/gallery/{slug}', [SiteController::class, 'album']);
$router->get('/blog', [SiteController::class, 'blog']);
$router->get('/blog/{slug}', [SiteController::class, 'post']);
$router->get('/contact', [SiteController::class, 'contact']);
$router->post('/contact', [SiteController::class, 'contactSubmit']);
$router->get('/volunteer', [SiteController::class, 'volunteer']);
$router->post('/volunteer', [SiteController::class, 'volunteerSubmit']);
$router->get('/donate', [SiteController::class, 'donate']);
$router->post('/donate', [SiteController::class, 'donateInitiate']);
$router->get('/donate/checkout', [SiteController::class, 'donateCheckout']);
$router->get('/donate/success', [SiteController::class, 'donateSuccess']);
$router->get('/donate/failed', [SiteController::class, 'donateFailed']);
$router->post('/donate/webhook', [SiteController::class, 'donateWebhook']);
