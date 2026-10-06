<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Controllers\Admin\Concerns\HandlesAdminUploads;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Media\MediaLibrary;
use Adheri\Modules\Programs\ProgramRepository;
use Adheri\Support\Flash;
use Adheri\Support\Str;
use Adheri\Support\Url;

final class ProgramAdminController extends Controller
{
    use HandlesAdminUploads;

    public function index(Request $request): never
    {
        $this->adminView('admin/programs', [
            'title' => 'Programs',
            'items' => (new ProgramRepository())->all(),
        ]);
    }

    public function createForm(Request $request): never
    {
        $this->adminView('admin/programs/form', [
            'title' => 'Add program',
            'item' => null,
            'imageUrl' => null,
        ]);
    }

    public function create(Request $request): never
    {
        $this->requireCsrf($request);
        $repo = new ProgramRepository();
        $data = $this->payloadFromRequest($request, $repo, null);
        if ($data === null) {
            Response::redirect(Url::to('admin/programs/create'));
        }

        $id = $repo->create($data);
        Flash::success(!empty($data['is_published']) ? 'Program published.' : 'Program saved as draft.');
        Response::redirect(Url::to('admin/programs/edit/' . $id));
    }

    public function editForm(Request $request, string $id): never
    {
        $item = (new ProgramRepository())->find((int) $id);
        if ($item === null) {
            Flash::error('Program not found.');
            Response::redirect(Url::to('admin/programs'));
        }

        $imageUrl = null;
        $imageId = (int) ($item['image_id'] ?? 0);
        if ($imageId > 0) {
            $media = (new MediaLibrary())->find($imageId);
            if ($media !== null && !empty($media['url_path'])) {
                $imageUrl = Url::to(ltrim((string) $media['url_path'], '/'));
            }
        }

        $this->adminView('admin/programs/form', [
            'title' => 'Edit program',
            'item' => $item,
            'imageUrl' => $imageUrl,
        ]);
    }

    public function edit(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $programId = (int) $id;
        $repo = new ProgramRepository();
        if ($repo->find($programId) === null) {
            Flash::error('Program not found.');
            Response::redirect(Url::to('admin/programs'));
        }

        $data = $this->payloadFromRequest($request, $repo, $programId);
        if ($data === null) {
            Response::redirect(Url::to('admin/programs/edit/' . $programId));
        }

        $repo->update($programId, $data);
        Flash::success(!empty($data['is_published']) ? 'Program published.' : 'Program saved as draft.');
        Response::redirect(Url::to('admin/programs/edit/' . $programId));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        (new ProgramRepository())->delete((int) $id);
        Flash::success('Program deleted.');
        Response::redirect(Url::to('admin/programs'));
    }

    /** @return array<string, mixed>|null */
    private function payloadFromRequest(Request $request, ProgramRepository $repo, ?int $exceptId): ?array
    {
        $title = $request->string('title');
        if ($title === '') {
            Flash::error('Title is required.');
            return null;
        }

        $slug = $request->string('slug');
        if ($slug === '') {
            $slug = Str::slug($title);
        } else {
            $slug = Str::slug($slug);
        }
        $slug = $repo->uniqueSlug($slug, $exceptId);

        $stored = $this->storeOptionalImage($request, 'image', $title);
        if ($this->hasUploadAttempt($request, 'image') && $stored === null) {
            return null;
        }

        $imageId = $stored !== null ? (int) $stored['id'] : null;
        $removeImage = $request->string('remove_image') === '1';
        if ($imageId === null && $exceptId !== null) {
            if ($removeImage) {
                $imageId = null;
            } else {
                $existing = $repo->find($exceptId);
                $imageId = isset($existing['image_id']) ? (int) $existing['image_id'] : null;
                if ($imageId === 0) {
                    $imageId = null;
                }
            }
        }

        $publishedRaw = $request->input('is_published', '0');
        $isPublished = $publishedRaw === '1' || $publishedRaw === 1 || $publishedRaw === true;

        return [
            'title' => $title,
            'slug' => $slug,
            'summary' => $request->string('summary'),
            'body' => (string) ($request->body['body'] ?? ''),
            'image_id' => $imageId,
            'sort_order' => (int) $request->string('sort_order', '0'),
            'featured_on_home' => isset($request->body['featured_on_home']),
            'is_published' => $isPublished,
        ];
    }
}
