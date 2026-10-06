<?php

declare(strict_types=1);

use Adheri\Http\Controllers\Admin\AlbumController;
use Adheri\Http\Controllers\Admin\AnnouncementController;
use Adheri\Http\Controllers\Admin\AuthController;
use Adheri\Http\Controllers\Admin\AccountSetupController;
use Adheri\Http\Controllers\Admin\DashboardController;
use Adheri\Http\Controllers\Admin\PasswordRecoveryController;
use Adheri\Http\Controllers\Admin\InquiryController;
use Adheri\Http\Controllers\Admin\MediaController;
use Adheri\Http\Controllers\Admin\PageAdminController;
use Adheri\Http\Controllers\Admin\PersonAdminController;
use Adheri\Http\Controllers\Admin\PostAdminController;
use Adheri\Http\Controllers\Admin\ProgramAdminController;
use Adheri\Http\Controllers\Admin\ResourceController;
use Adheri\Http\Controllers\Admin\SettingsController;
use Adheri\Http\Controllers\Admin\TestimonialAdminController;

/** @var Adheri\Router $router */

$router->get('/admin/login', [AuthController::class, 'loginForm']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

$router->get('/admin/account-setup', [AccountSetupController::class, 'form']);
$router->post('/admin/account-setup', [AccountSetupController::class, 'save']);

$router->get('/admin/forgot-password', [PasswordRecoveryController::class, 'forgotForm']);
$router->post('/admin/forgot-password', [PasswordRecoveryController::class, 'forgotSubmit']);
$router->get('/admin/verify-otp', [PasswordRecoveryController::class, 'verifyForm']);
$router->post('/admin/verify-otp', [PasswordRecoveryController::class, 'verifySubmit']);
$router->get('/admin/reset-password', [PasswordRecoveryController::class, 'resetForm']);
$router->post('/admin/reset-password', [PasswordRecoveryController::class, 'resetSubmit']);

$router->get('/admin', [DashboardController::class, 'index']);

$router->get('/admin/pages', [PageAdminController::class, 'index']);
$router->get('/admin/pages/edit/{id}', [PageAdminController::class, 'editForm']);
$router->post('/admin/pages/edit/{id}', [PageAdminController::class, 'edit']);

$router->get('/admin/programs', [ProgramAdminController::class, 'index']);
$router->get('/admin/programs/create', [ProgramAdminController::class, 'createForm']);
$router->post('/admin/programs/create', [ProgramAdminController::class, 'create']);
$router->get('/admin/programs/edit/{id}', [ProgramAdminController::class, 'editForm']);
$router->post('/admin/programs/edit/{id}', [ProgramAdminController::class, 'edit']);
$router->post('/admin/programs/delete/{id}', [ProgramAdminController::class, 'delete']);

$router->get('/admin/announcements', [ResourceController::class, 'announcements']);
$router->get('/admin/announcements/new', [AnnouncementController::class, 'createForm']);
$router->get('/admin/announcements/{id}/edit', [AnnouncementController::class, 'editForm']);
$router->post('/admin/announcements', [AnnouncementController::class, 'store']);
$router->post('/admin/announcements/{id}', [AnnouncementController::class, 'update']);
$router->post('/admin/announcements/{id}/delete', [AnnouncementController::class, 'delete']);

$router->get('/admin/testimonials', [TestimonialAdminController::class, 'index']);
$router->get('/admin/testimonials/create', [TestimonialAdminController::class, 'createForm']);
$router->post('/admin/testimonials/create', [TestimonialAdminController::class, 'create']);
$router->get('/admin/testimonials/edit/{id}', [TestimonialAdminController::class, 'editForm']);
$router->post('/admin/testimonials/edit/{id}', [TestimonialAdminController::class, 'edit']);
$router->post('/admin/testimonials/delete/{id}', [TestimonialAdminController::class, 'delete']);

$router->get('/admin/people', [PersonAdminController::class, 'index']);
$router->get('/admin/people/create', [PersonAdminController::class, 'createForm']);
$router->post('/admin/people/create', [PersonAdminController::class, 'create']);
$router->get('/admin/people/edit/{id}', [PersonAdminController::class, 'editForm']);
$router->post('/admin/people/edit/{id}', [PersonAdminController::class, 'edit']);
$router->post('/admin/people/delete/{id}', [PersonAdminController::class, 'delete']);

$router->get('/admin/media', [ResourceController::class, 'media']);
$router->post('/admin/media/upload', [MediaController::class, 'upload']);
$router->post('/admin/media/{id}/delete', [MediaController::class, 'delete']);

$router->get('/admin/albums', [ResourceController::class, 'albums']);
$router->get('/admin/albums/new', [AlbumController::class, 'createForm']);
$router->get('/admin/albums/{id}/edit', [AlbumController::class, 'editForm']);
$router->post('/admin/albums', [AlbumController::class, 'store']);
$router->post('/admin/albums/{id}', [AlbumController::class, 'update']);
$router->post('/admin/albums/{id}/delete', [AlbumController::class, 'delete']);
$router->post('/admin/albums/{id}/media/{media_id}/detach', [AlbumController::class, 'detachMedia']);

$router->get('/admin/posts', [PostAdminController::class, 'index']);
$router->get('/admin/posts/create', [PostAdminController::class, 'createForm']);
$router->post('/admin/posts/create', [PostAdminController::class, 'create']);
$router->get('/admin/posts/edit/{id}', [PostAdminController::class, 'editForm']);
$router->post('/admin/posts/edit/{id}', [PostAdminController::class, 'edit']);
$router->post('/admin/posts/delete/{id}', [PostAdminController::class, 'delete']);
$router->post('/admin/posts/upload-image', [PostAdminController::class, 'uploadImage']);

$router->get('/admin/inquiries', [ResourceController::class, 'inquiries']);
$router->post('/admin/inquiries/{id}/status', [InquiryController::class, 'updateStatus']);

$router->get('/admin/donations', [ResourceController::class, 'donations']);

$router->get('/admin/settings', [ResourceController::class, 'settings']);
$router->post('/admin/settings', [SettingsController::class, 'save']);
