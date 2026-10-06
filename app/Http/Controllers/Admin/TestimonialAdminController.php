<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Controllers\Admin\Concerns\HandlesAdminUploads;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Testimonials\TestimonialRepository;
use Adheri\Support\Flash;
use Adheri\Support\Url;

final class TestimonialAdminController extends Controller
{
    use HandlesAdminUploads;

    public function index(Request $request): never
    {
        $this->adminView('admin/testimonials', [
            'title' => 'Testimonials',
            'items' => (new TestimonialRepository())->all(),
        ]);
    }

    public function createForm(Request $request): never
    {
        $this->adminView('admin/testimonials/form', [
            'title' => 'Add testimonial',
            'item' => null,
        ]);
    }

    public function create(Request $request): never
    {
        $this->requireCsrf($request);
        $data = $this->payloadFromRequest($request, null);
        if ($data === null) {
            Response::redirect(Url::to('admin/testimonials/create'));
        }

        (new TestimonialRepository())->create($data);
        Flash::success('Testimonial created.');
        Response::redirect(Url::to('admin/testimonials'));
    }

    public function editForm(Request $request, string $id): never
    {
        $item = (new TestimonialRepository())->find((int) $id);
        if ($item === null) {
            Flash::error('Testimonial not found.');
            Response::redirect(Url::to('admin/testimonials'));
        }

        $this->adminView('admin/testimonials/form', [
            'title' => 'Edit testimonial',
            'item' => $item,
        ]);
    }

    public function edit(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $testimonialId = (int) $id;
        $repo = new TestimonialRepository();
        if ($repo->find($testimonialId) === null) {
            Flash::error('Testimonial not found.');
            Response::redirect(Url::to('admin/testimonials'));
        }

        $data = $this->payloadFromRequest($request, $testimonialId);
        if ($data === null) {
            Response::redirect(Url::to('admin/testimonials/edit/' . $testimonialId));
        }

        $repo->update($testimonialId, $data);
        Flash::success('Testimonial updated.');
        Response::redirect(Url::to('admin/testimonials'));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        (new TestimonialRepository())->delete((int) $id);
        Flash::success('Testimonial deleted.');
        Response::redirect(Url::to('admin/testimonials'));
    }

    /** @return array<string, mixed>|null */
    private function payloadFromRequest(Request $request, ?int $exceptId): ?array
    {
        $quote = $request->string('quote');
        $personName = $request->string('person_name');
        if ($quote === '' || $personName === '') {
            Flash::error('Quote and person name are required.');
            return null;
        }

        $photoPath = $request->string('photo_path');
        $photoId = null;
        $removePhoto = $request->string('remove_photo', '0') === '1';

        $stored = $this->storeOptionalImage($request, 'photo', $personName);
        if ($this->hasUploadAttempt($request, 'photo') && $stored === null) {
            return null;
        }
        if ($stored !== null) {
            $photoId = (int) $stored['id'];
            $photoPath = (string) $stored['url_path'];
        } elseif ($removePhoto) {
            $photoPath = '';
            $photoId = null;
        } elseif ($exceptId !== null && $photoPath === '') {
            $existing = (new TestimonialRepository())->find($exceptId);
            if ($existing !== null) {
                $photoPath = (string) ($existing['photo_path'] ?? '');
                $photoId = isset($existing['photo_id']) ? (int) $existing['photo_id'] : null;
                if ($photoId === 0) {
                    $photoId = null;
                }
            }
        }

        return [
            'quote' => $quote,
            'person_name' => $personName,
            'location' => $request->string('location'),
            'photo_path' => $photoPath,
            'photo_id' => $photoId,
            'sort_order' => (int) $request->string('sort_order', '0'),
            'is_published' => $request->string('is_published', '0') === '1',
        ];
    }
}
