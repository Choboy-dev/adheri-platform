<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Announcements\AnnouncementRepository;
use Adheri\Modules\Media\MediaLibrary;
use Adheri\Support\Flash;
use Adheri\Support\Url;
use InvalidArgumentException;

final class AnnouncementController extends Controller
{
    public function createForm(Request $request): never
    {
        $this->adminView('admin/announcement-form', [
            'title' => 'New announcement',
            'item' => null,
            'imageUrl' => null,
        ]);
    }

    public function editForm(Request $request, string $id): never
    {
        $itemId = (int) $id;
        $repo = new AnnouncementRepository();
        $item = $repo->find($itemId);
        if ($item === null) {
            Flash::error('Announcement not found.');
            Response::redirect(Url::to('admin/announcements'));
        }

        $imageUrl = null;
        if (!empty($item['image_id'])) {
            $media = (new MediaLibrary())->find((int) $item['image_id']);
            $imageUrl = $media['url_path'] ?? null;
        }

        $this->adminView('admin/announcement-form', [
            'title' => 'Edit announcement',
            'item' => $item,
            'imageUrl' => $imageUrl,
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireCsrf($request);
        $imageId = $this->resolveImageId($request, true);
        if ($imageId === null) {
            Response::redirect(Url::to('admin/announcements/new'));
        }

        (new AnnouncementRepository())->create($this->payload($request, $imageId));
        Flash::success('Announcement created.');
        Response::redirect(Url::to('admin/announcements'));
    }

    public function update(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $itemId = (int) $id;
        $repo = new AnnouncementRepository();
        $existing = $repo->find($itemId);
        if ($existing === null) {
            Flash::error('Announcement not found.');
            Response::redirect(Url::to('admin/announcements'));
        }

        $imageId = $this->resolveImageId($request, false, (int) $existing['image_id']);
        if ($imageId === null) {
            Response::redirect(Url::to('admin/announcements/' . $itemId . '/edit'));
        }

        $repo->update($itemId, $this->payload($request, $imageId));
        Flash::success('Announcement saved.');
        Response::redirect(Url::to('admin/announcements'));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $itemId = (int) $id;
        if ($itemId > 0) {
            (new AnnouncementRepository())->delete($itemId);
            Flash::success('Announcement deleted.');
        }
        Response::redirect(Url::to('admin/announcements'));
    }

    private function resolveImageId(Request $request, bool $required, int $fallback = 0): ?int
    {
        $file = $request->files['image'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $stored = (new MediaLibrary())->storeUploadedFile($file);
                return (int) $stored['id'];
            } catch (InvalidArgumentException $e) {
                Flash::error($e->getMessage());
                return null;
            }
        }

        if ($required) {
            Flash::error('Flyer image is required.');
            return null;
        }

        return $fallback > 0 ? $fallback : null;
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, int $imageId): array
    {
        return [
            'title' => $request->string('title') ?: null,
            'link_url' => $request->string('link_url') ?: null,
            'image_id' => $imageId,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_published' => $request->input('is_published'),
            'starts_at' => $this->datetimeOrNull($request->string('starts_at')),
            'ends_at' => $this->datetimeOrNull($request->string('ends_at')),
        ];
    }

    private function datetimeOrNull(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }
}
