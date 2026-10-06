<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Database;
use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Modules\Donations\DonationRepository;
use Adheri\Modules\Inquiries\InquiryRepository;
use Adheri\Modules\Programs\ProgramRepository;
use Adheri\Modules\Blog\PostRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): never
    {
        $inquiryRepo = new InquiryRepository();
        $this->adminView('admin/dashboard', [
            'title' => 'Dashboard',
            'newInquiries' => $inquiryRepo->countNew(),
            'recentInquiries' => $inquiryRepo->latest(8),
            'recentDonations' => (new DonationRepository())->latest(8),
            'programCount' => count((new ProgramRepository())->all()),
            'postCount' => count(Database::fetchAll('SELECT id FROM posts')),
            'albumCount' => count(Database::fetchAll('SELECT id FROM albums')),
        ]);
    }
}
