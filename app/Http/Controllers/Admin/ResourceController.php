<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Modules\Inquiries\InquiryRepository;
use Adheri\Modules\Donations\DonationRepository;
use Adheri\Modules\Blog\PostRepository;
use Adheri\Modules\Albums\AlbumRepository;
use Adheri\Modules\Announcements\AnnouncementRepository;
use Adheri\Modules\Settings\SettingsRepository;
use Adheri\Database;

final class ResourceController extends Controller
{
    public function inquiries(Request $request): never
    {
        $this->adminView('admin/inquiries', [
            'title' => 'Inquiries',
            'items' => (new InquiryRepository())->latest(100),
        ]);
    }

    public function donations(Request $request): never
    {
        $this->adminView('admin/donations', [
            'title' => 'Donations',
            'items' => (new DonationRepository())->latest(100),
        ]);
    }

    public function posts(Request $request): never
    {
        $this->adminView('admin/posts', [
            'title' => 'Blog posts',
            'items' => Database::fetchAll('SELECT * FROM posts ORDER BY updated_at DESC'),
        ]);
    }

    public function albums(Request $request): never
    {
        $this->adminView('admin/albums', [
            'title' => 'Albums',
            'items' => (new AlbumRepository())->allForAdmin(),
        ]);
    }

    public function settings(Request $request): never
    {
        $this->adminView('admin/settings', [
            'title' => 'Settings',
            'settings' => (new SettingsRepository())->all(),
        ]);
    }

    public function media(Request $request): never
    {
        $this->adminView('admin/media', [
            'title' => 'Media library',
            'items' => Database::fetchAll('SELECT * FROM media_assets ORDER BY created_at DESC LIMIT 200'),
        ]);
    }

    public function announcements(Request $request): never
    {
        $this->adminView('admin/announcements', [
            'title' => 'Announcements',
            'items' => (new AnnouncementRepository())->allForAdmin(),
        ]);
    }

    public function pages(Request $request): never
    {
        $this->adminView('admin/pages', [
            'title' => 'Pages',
            'items' => Database::fetchAll('SELECT * FROM pages ORDER BY title ASC'),
        ]);
    }
}
