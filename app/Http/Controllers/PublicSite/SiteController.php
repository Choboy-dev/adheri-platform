<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\PublicSite;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Modules\Albums\AlbumRepository;
use Adheri\Modules\Announcements\AnnouncementRepository;
use Adheri\Modules\Blog\PostRepository;
use Adheri\Modules\Content\PageRepository;
use Adheri\Modules\Donations\DonationRepository;
use Adheri\Modules\Donations\FlutterwaveGateway;
use Adheri\Modules\Inquiries\InquiryRepository;
use Adheri\Modules\Notifications\ContactMailer;
use Adheri\Modules\People\PersonRepository;
use Adheri\Modules\Programs\ProgramRepository;
use Adheri\Modules\Sdgs\SdgRepository;
use Adheri\Modules\Settings\SettingsRepository;
use Adheri\Modules\Testimonials\TestimonialRepository;
use Adheri\Security\Csrf;
use Adheri\Support\Flash;
use Adheri\Support\Seo;
use Adheri\Support\Url;
use Adheri\Http\Response;
use Adheri\Config;

final class SiteController extends Controller
{
    public function home(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/home', [
            'title' => $settings['seo_default_title'] ?? 'ADHERI',
            'settings' => $settings,
            'meta' => $this->pageShareMeta($settings, 'home', (string) ($settings['seo_default_title'] ?? 'ADHERI')),
            'sections' => $this->sections('home'),
            'programs' => (new ProgramRepository())->featuredOnHome(4),
            'testimonials' => (new TestimonialRepository())->published(),
            'sdgs' => (new SdgRepository())->published(),
            'announcements' => (new AnnouncementRepository())->publishedActive(),
            'heroVideo' => null,
            'heroSlides' => [
                Url::asset('img/hero/01.jpg'),
                Url::asset('img/hero/02.jpg'),
                Url::asset('img/hero/03.jpg'),
                Url::asset('img/hero/04.jpg'),
                Url::asset('img/hero/05.jpg'),
            ],
            'aboutImage' => Url::asset('img/presence/about.jpg'),
            'presenceImages' => [
                Url::asset('img/presence/01.jpg'),
                Url::asset('img/presence/02.jpg'),
                Url::asset('img/presence/03.jpg'),
                Url::asset('img/presence/04.jpg'),
                Url::asset('img/presence/05.jpg'),
                Url::asset('img/presence/06.jpg'),
            ],
            'programImages' => [
                'advocacy' => Url::asset('img/programs/advocacy.jpg'),
                'counselling' => Url::asset('img/programs/counselling.jpg'),
                'empowerment' => Url::asset('img/programs/empowerment.jpg'),
                'climate-change' => Url::asset('img/programs/climate.jpg'),
                'media' => Url::asset('img/programs/media.jpg'),
                'rehab-support' => Url::asset('img/programs/rehab.jpg'),
                'artivism' => Url::asset('img/programs/artivism.jpg'),
            ],
            'fallbackAnnouncements' => [
                ['src' => Url::asset('img/announcements/call-artists.png'), 'alt' => 'Call for artists'],
                ['src' => Url::asset('img/announcements/policy.png'), 'alt' => 'Policy round table'],
                ['src' => Url::asset('img/announcements/nigeria.png'), 'alt' => 'The Nigeria We Want'],
                ['src' => Url::asset('img/announcements/training.png'), 'alt' => 'Training invitation'],
            ],
        ]);
    }

    public function about(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/about', [
            'title' => 'About — ADHERI',
            'settings' => $settings,
            'meta' => $this->pageShareMeta($settings, 'about', 'About — ADHERI', Url::asset('img/about/About-us-image.jpg')),
            'sections' => $this->sections('about'),
            'board' => (new PersonRepository())->publishedByGroup('board'),
            'youth' => (new PersonRepository())->publishedByGroup('youth_advisory'),
            'programs' => (new ProgramRepository())->published(),
            'aboutImage' => Url::asset('img/about/About-us-image.jpg'),
        ]);
    }

    public function programs(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/programs', [
            'title' => 'Programs — ADHERI',
            'settings' => $settings,
            'meta' => $this->pageShareMeta($settings, 'programs', 'Programs — ADHERI'),
            'sections' => $this->sections('programs'),
            'programs' => (new ProgramRepository())->published(),
            'programImages' => [
                'advocacy' => Url::asset('img/programs/advocacy.jpg'),
                'counselling' => Url::asset('img/programs/counselling.jpg'),
                'empowerment' => Url::asset('img/programs/empowerment.jpg'),
                'climate-change' => Url::asset('img/programs/climate.jpg'),
                'media' => Url::asset('img/programs/media.jpg'),
                'rehab-support' => Url::asset('img/programs/rehab.jpg'),
                'artivism' => Url::asset('img/programs/artivism.jpg'),
            ],
        ]);
    }

    public function gallery(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/gallery', [
            'title' => 'Gallery — ADHERI',
            'settings' => $settings,
            'meta' => [
                'description' => 'Photo albums from ADHERI programs, artivism, and community work.',
            ],
            'albums' => (new AlbumRepository())->published(),
        ]);
    }

    public function album(Request $request, string $slug): never
    {
        $repo = new AlbumRepository();
        $album = $repo->findPublishedBySlug($slug);
        if ($album === null) {
            Response::html('Album not found', 404);
        }
        $settings = (new SettingsRepository())->all();
        $cover = !empty($album['cover_url']) ? Url::media((string) $album['cover_url']) : null;
        $this->view('public/album', [
            'title' => $album['title'] . ' — Gallery',
            'settings' => $settings,
            'meta' => [
                'title' => (string) $album['title'] . ' — Gallery',
                'description' => Seo::plain((string) ($album['description'] ?? ''), 200) ?: null,
                'image' => $cover,
                'type' => 'website',
                'url' => Url::to('gallery/' . $slug),
            ],
            'album' => $album,
            'media' => $repo->mediaForAlbum((int) $album['id']),
        ]);
    }

    public function blog(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/blog', [
            'title' => 'Blog — ADHERI',
            'settings' => $settings,
            'meta' => [
                'description' => 'News, stories, and updates from ADHERI.',
            ],
            'posts' => (new PostRepository())->published(),
        ]);
    }

    public function post(Request $request, string $slug): never
    {
        $post = (new PostRepository())->findPublishedBySlug($slug);
        if ($post === null) {
            Response::html('Post not found', 404);
        }
        $settings = (new SettingsRepository())->all();
        $cover = !empty($post['cover_url']) ? Url::media((string) $post['cover_url']) : null;
        $description = (string) ($post['seo_description'] ?? '');
        if ($description === '') {
            $description = (string) ($post['excerpt'] ?? '');
        }
        if ($description === '' && !empty($post['body'])) {
            $description = Seo::plain((string) $post['body'], 200);
        }
        $shareTitle = (string) (($post['seo_title'] ?? '') !== '' ? $post['seo_title'] : $post['title']);

        $this->view('public/post', [
            'title' => $shareTitle,
            'settings' => $settings,
            'meta' => [
                'title' => $shareTitle,
                'description' => $description !== '' ? Seo::plain($description, 200) : null,
                'image' => $cover,
                'type' => 'article',
                'url' => Url::to('blog/' . $slug),
            ],
            'post' => $post,
        ]);
    }

    public function contact(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/contact', [
            'title' => 'Contact — ADHERI',
            'settings' => $settings,
            'meta' => [
                'description' => 'Contact ADHERI — Advocates for Health and Rights of Women and Youths Initiative.',
            ],
            'errors' => Flash::pullErrors(),
            'old' => Flash::get('old', []),
            'success' => Flash::get('success'),
        ]);
    }

    public function contactSubmit(Request $request): never
    {
        $this->requireCsrf($request);
        $first = $request->string('first_name');
        $last = $request->string('last_name');
        $email = $request->string('email');
        $phone = $request->string('phone');
        $message = $request->string('message');
        $errors = [];
        if ($first === '') {
            $errors['first_name'] = 'First name is required.';
        }
        if ($last === '') {
            $errors['last_name'] = 'Last name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if ($message === '') {
            $errors['message'] = 'Message is required.';
        }
        if ($errors !== []) {
            Flash::errors($errors);
            Flash::set('old', $request->body);
            Response::redirect(Url::to('contact'));
        }

        (new InquiryRepository())->create([
            'type' => 'contact',
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'message' => $message,
        ]);

        $inquiry = [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'message' => $message,
        ];
        $settings = (new SettingsRepository())->all();
        (new ContactMailer())->sendContactInquiry($inquiry, $settings);

        Flash::success('Thank you. We will get back to you soon.');
        Response::redirect(Url::to('contact'));
    }

    public function volunteer(Request $request): never
    {
        $settings = (new SettingsRepository())->all();
        $this->view('public/volunteer', [
            'title' => 'Volunteer — ADHERI',
            'settings' => $settings,
            'meta' => $this->pageShareMeta($settings, 'volunteer', 'Volunteer — ADHERI'),
            'sections' => $this->sections('volunteer'),
            'errors' => Flash::pullErrors(),
            'old' => Flash::get('old', []),
            'success' => Flash::get('success'),
        ]);
    }

    public function volunteerSubmit(Request $request): never
    {
        $this->requireCsrf($request);
        $name = $request->string('full_name');
        $email = $request->string('email');
        $phone = $request->string('phone');
        $location = $request->string('location');
        $availability = $request->string('availability');
        $message = $request->string('message');
        $interests = $request->body['interests'] ?? [];
        if (!is_array($interests)) {
            $interests = [];
        }

        $errors = [];
        if ($name === '') {
            $errors['full_name'] = 'Full name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if ($message === '') {
            $errors['message'] = 'Please tell us how you want to help.';
        }
        if ($errors !== []) {
            Flash::errors($errors);
            Flash::set('old', $request->body);
            Response::redirect(Url::to('volunteer'));
        }

        (new InquiryRepository())->create([
            'type' => 'volunteer',
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'location' => $location !== '' ? $location : null,
            'interests_json' => json_encode(array_values($interests), JSON_THROW_ON_ERROR),
            'availability' => $availability !== '' ? $availability : null,
            'message' => $message,
        ]);

        $settings = (new SettingsRepository())->all();
        (new ContactMailer())->sendVolunteerInquiry([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'location' => $location !== '' ? $location : null,
            'interests' => array_values(array_map('strval', $interests)),
            'availability' => $availability !== '' ? $availability : null,
            'message' => $message,
        ], $settings);

        Flash::success('Thank you for offering to volunteer. Our team will follow up.');
        Response::redirect(Url::to('volunteer'));
    }

    public function donate(Request $request): never
    {
        $donations = new DonationRepository();
        $gateway = new FlutterwaveGateway();
        $settings = (new SettingsRepository())->all();
        $this->view('public/donate', [
            'title' => 'Donate — ADHERI',
            'settings' => $settings,
            'meta' => $this->pageShareMeta($settings, 'donate', 'Donate — ADHERI'),
            'sections' => $this->sections('donate'),
            'minAmount' => $donations->minimumAmount(),
            'gatewayConfigured' => $gateway->isConfigured(),
            'publicKey' => $gateway->publicKey(),
            'errors' => Flash::pullErrors(),
            'old' => Flash::get('old', []),
            'success' => Flash::get('success'),
            'error' => Flash::get('error'),
        ]);
    }

    public function donateInitiate(Request $request): never
    {
        $this->requireCsrf($request);
        $repo = new DonationRepository();
        $min = $repo->minimumAmount();
        $name = $request->string('donor_name');
        $email = $request->string('donor_email');
        $phone = $request->string('donor_phone');
        $amount = (float) $request->string('amount', '0');

        $errors = [];
        if ($name === '') {
            $errors['donor_name'] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['donor_email'] = 'A valid email is required.';
        }
        if (!is_finite($amount) || $amount < $min) {
            $errors['amount'] = 'Please enter at least ₦' . number_format($min) . ' to continue to payment.';
        }
        if ($errors !== []) {
            Flash::errors($errors);
            Flash::set('old', $request->body);
            Response::redirect(Url::to('donate'));
        }

        $txRef = 'ADHERI-' . bin2hex(random_bytes(8));
        $repo->createPending([
            'donor_name' => $name,
            'donor_email' => $email,
            'donor_phone' => $phone !== '' ? $phone : null,
            'amount' => $amount,
            'currency' => Config::get('DONATION_CURRENCY', 'NGN'),
            'flutterwave_tx_ref' => $txRef,
        ]);

        Flash::set('checkout', [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => Config::get('DONATION_CURRENCY', 'NGN'),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
        Response::redirect(Url::to('donate/checkout'));
    }

    public function donateCheckout(Request $request): never
    {
        $checkout = Flash::get('checkout');
        if (!is_array($checkout)) {
            Response::redirect(Url::to('donate'));
        }
        // Re-flash for one render cycle if needed — store in session explicitly
        $_SESSION['_checkout_payload'] = $checkout;
        $gateway = new FlutterwaveGateway();
        $this->view('public/donate-checkout', [
            'title' => 'Complete donation — ADHERI',
            'settings' => (new SettingsRepository())->all(),
            'checkout' => $checkout,
            'publicKey' => $gateway->publicKey(),
            'gatewayConfigured' => $gateway->isConfigured(),
            'redirectUrl' => Url::to('donate/success'),
        ]);
    }

    public function donateSuccess(Request $request): never
    {
        $this->view('public/donate-result', [
            'title' => 'Thank you — ADHERI',
            'settings' => (new SettingsRepository())->all(),
            'status' => 'success',
            'heading' => 'Thank you for your gift',
            'message' => 'Your support strengthens advocacy, counselling, empowerment, and artivism for women and youth.',
        ]);
    }

    public function donateFailed(Request $request): never
    {
        $this->view('public/donate-result', [
            'title' => 'Donation not completed — ADHERI',
            'settings' => (new SettingsRepository())->all(),
            'status' => 'failed',
            'heading' => 'Payment was not completed',
            'message' => 'No charge was finalized. You can try again or contact us for help.',
        ]);
    }

    public function donateWebhook(Request $request): never
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            Response::json(['status' => 'ignored'], 400);
        }

        $data = $payload['data'] ?? [];
        $txRef = (string) ($data['tx_ref'] ?? '');
        $status = strtolower((string) ($data['status'] ?? ''));
        $fwId = isset($data['id']) ? (string) $data['id'] : null;

        if ($txRef !== '') {
            $mapped = $status === 'successful' ? 'successful' : ($status === 'failed' ? 'failed' : 'pending');
            (new DonationRepository())->markStatus($txRef, $mapped, $fwId);
        }

        Response::json(['status' => 'ok']);
    }

    /**
     * Share meta from CMS page SEO fields when present.
     *
     * @param array<string, string|null> $settings
     * @return array{title?: string, description?: string|null, image?: string|null, type?: string}
     */
    private function pageShareMeta(array $settings, string $slug, string $fallbackTitle, ?string $image = null): array
    {
        $page = (new PageRepository())->findBySlug($slug);
        $title = $fallbackTitle;
        $description = null;
        if ($page !== null) {
            if (!empty($page['seo_title'])) {
                $title = (string) $page['seo_title'];
            }
            if (!empty($page['seo_description'])) {
                $description = (string) $page['seo_description'];
            }
        }
        if ($description === null || $description === '') {
            $description = (string) ($settings['seo_default_description'] ?? '');
        }

        return [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'image' => $image,
            'type' => 'website',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function sections(string $slug): array
    {
        $page = (new PageRepository())->findBySlug($slug);
        if ($page === null) {
            return [];
        }
        return (new PageRepository())->sectionsForPage((int) $page['id']);
    }
}
