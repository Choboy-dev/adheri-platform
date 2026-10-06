<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Database;
use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Albums\AlbumRepository;
use Adheri\Support\Flash;
use Adheri\Support\Url;

final class AlbumController extends Controller
{
    public function createForm(Request $request): never
    {
        $this->adminView('admin/album-form', [
            'title' => 'New album',
            'album' => null,
            'mediaItems' => [],
            'library' => $this->recentMedia(),
        ]);
    }

    public function editForm(Request $request, string $id): never
    {
        $albumId = (int) $id;
        $repo = new AlbumRepository();
        $album = $repo->find($albumId);
        if ($album === null) {
            Flash::error('Album not found.');
            Response::redirect(Url::to('admin/albums'));
        }

        $this->adminView('admin/album-form', [
            'title' => 'Edit album',
            'album' => $album,
            'mediaItems' => $repo->mediaForAlbum($albumId),
            'library' => $this->recentMedia(),
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireCsrf($request);
        $title = $request->string('title');
        if ($title === '') {
            Flash::error('Title is required.');
            Response::redirect(Url::to('admin/albums/new'));
        }

        $repo = new AlbumRepository();
        $payload = $this->albumPayload($request);
        $id = $repo->create($payload);
        Flash::success(!empty($payload['is_published']) ? 'Album published.' : 'Album saved as draft.');
        Response::redirect(Url::to('admin/albums/' . $id . '/edit'));
    }

    public function update(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $albumId = (int) $id;
        $repo = new AlbumRepository();
        if ($repo->find($albumId) === null) {
            Flash::error('Album not found.');
            Response::redirect(Url::to('admin/albums'));
        }

        $title = $request->string('title');
        if ($title === '') {
            Flash::error('Title is required.');
            Response::redirect(Url::to('admin/albums/' . $albumId . '/edit'));
        }

        $payload = $this->albumPayload($request);
        $repo->update($albumId, $payload);
        $this->syncAlbumMedia($request, $albumId, $repo);
        Flash::success(!empty($payload['is_published']) ? 'Album published.' : 'Album saved as draft.');
        Response::redirect(Url::to('admin/albums/' . $albumId . '/edit'));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $albumId = (int) $id;
        if ($albumId > 0) {
            (new AlbumRepository())->delete($albumId);
            Flash::success('Album deleted.');
        }
        Response::redirect(Url::to('admin/albums'));
    }

    public function detachMedia(Request $request, string $id, string $media_id): never
    {
        $this->requireCsrf($request);
        $albumId = (int) $id;
        $mediaId = (int) $media_id;
        (new AlbumRepository())->detachMedia($albumId, $mediaId);
        Flash::success('Removed from album.');
        Response::redirect(Url::to('admin/albums/' . $albumId . '/edit'));
    }

    /** @return list<array<string, mixed>> */
    private function recentMedia(): array
    {
        return Database::fetchAll(
            'SELECT id, url_path, original_name, alt_text FROM media_assets ORDER BY created_at DESC LIMIT 120'
        );
    }

    /** @return array<string, mixed> */
    private function albumPayload(Request $request): array
    {
        $cover = $request->string('cover_id');
        $publishedRaw = $request->input('is_published', '0');
        $isPublished = $publishedRaw === '1' || $publishedRaw === 1 || $publishedRaw === true;

        return [
            'title' => $request->string('title'),
            'slug' => $request->string('slug'),
            'description' => $request->string('description') ?: null,
            'event_date' => $request->string('event_date') ?: null,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_published' => $isPublished,
            'cover_id' => $cover !== '' ? (int) $cover : null,
        ];
    }

    private function syncAlbumMedia(Request $request, int $albumId, AlbumRepository $repo): void
    {
        $attach = $request->input('attach_media_ids', []);
        if (!is_array($attach)) {
            $attach = [];
        }
        foreach ($attach as $mediaId) {
            $mediaId = (int) $mediaId;
            if ($mediaId > 0) {
                $repo->attachMedia($albumId, $mediaId, null, 0);
            }
        }

        $captions = $request->input('media_caption', []);
        $sorts = $request->input('media_sort', []);
        if (!is_array($captions) || !is_array($sorts)) {
            return;
        }
        foreach ($sorts as $mediaId => $sort) {
            $mediaId = (int) $mediaId;
            if ($mediaId <= 0) {
                continue;
            }
            $caption = is_array($captions) && isset($captions[$mediaId])
                ? trim((string) $captions[$mediaId])
                : null;
            $repo->attachMedia($albumId, $mediaId, $caption !== '' ? $caption : null, (int) $sort);
        }
    }
}
