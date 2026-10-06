<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Controllers\Admin\Concerns\HandlesAdminUploads;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Blog\PostRepository;
use Adheri\Modules\Media\MediaLibrary;
use Adheri\Security\Auth;
use Adheri\Support\Flash;
use Adheri\Support\Url;
use InvalidArgumentException;

final class PostAdminController extends Controller
{
    use HandlesAdminUploads;

    public function index(Request $request): never
    {
        $perPage = 12;
        $total = (new PostRepository())->countAdmin();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = (int) $request->string('page', '1');
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $this->adminView('admin/posts', [
            'title' => 'Blog',
            'items' => (new PostRepository())->allAdmin($perPage, $offset),
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
        ]);
    }

    public function createForm(Request $request): never
    {
        $this->adminView('admin/posts/form', [
            'title' => 'New post',
            'item' => null,
            'categories' => (new PostRepository())->categories(),
            'featuredImageUrl' => null,
        ]);
    }

    public function create(Request $request): never
    {
        $this->requireCsrf($request);
        $repo = new PostRepository();
        $data = $this->payload($request, $repo, null);
        if ($data === null) {
            Response::redirect(Url::to('admin/posts/create'));
        }
        $id = $repo->create($data);
        Flash::success('Post saved.');
        Response::redirect(Url::to('admin/posts/edit/' . $id));
    }

    public function editForm(Request $request, string $id): never
    {
        $item = (new PostRepository())->find((int) $id);
        if ($item === null) {
            Flash::error('Post not found.');
            Response::redirect(Url::to('admin/posts'));
        }

        $featuredImageUrl = null;
        $coverId = (int) ($item['cover_id'] ?? 0);
        if ($coverId > 0) {
            $media = (new MediaLibrary())->find($coverId);
            if ($media !== null && !empty($media['url_path'])) {
                $featuredImageUrl = Url::to(ltrim((string) $media['url_path'], '/'));
            }
        }

        $this->adminView('admin/posts/form', [
            'title' => 'Edit post',
            'item' => $item,
            'categories' => (new PostRepository())->categories(),
            'featuredImageUrl' => $featuredImageUrl,
        ]);
    }

    public function edit(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $postId = (int) $id;
        $repo = new PostRepository();
        if ($repo->find($postId) === null) {
            Flash::error('Post not found.');
            Response::redirect(Url::to('admin/posts'));
        }
        $data = $this->payload($request, $repo, $postId);
        if ($data === null) {
            Response::redirect(Url::to('admin/posts/edit/' . $postId));
        }
        $repo->update($postId, $data);
        Flash::success('Post updated.');
        Response::redirect(Url::to('admin/posts/edit/' . $postId));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        (new PostRepository())->delete((int) $id);
        Flash::success('Post deleted.');
        Response::redirect(Url::to('admin/posts'));
    }

    public function uploadImage(Request $request): never
    {
        Auth::requireLogin();
        if (!\Adheri\Security\Csrf::validate($request->string('_token'))) {
            Response::json(['ok' => false, 'error' => 'Invalid security token.'], 403);
        }

        $file = $request->files['image'] ?? $request->files['file'] ?? null;
        if (!is_array($file)) {
            Response::json(['ok' => false, 'error' => 'No image uploaded.'], 422);
        }

        try {
            $stored = (new MediaLibrary())->storeUploadedFile($file);
            Response::json([
                'ok' => true,
                'url' => Url::to($stored['url_path']),
                'id' => $stored['id'],
            ]);
        } catch (InvalidArgumentException $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            Response::json(['ok' => false, 'error' => 'Upload failed.'], 500);
        }
    }

    /** @return array<string, mixed>|null */
    private function payload(Request $request, PostRepository $repo, ?int $id): ?array
    {
        $title = $request->string('title');
        $body = (string) ($request->body['body'] ?? '');
        if ($title === '') {
            Flash::error('Title is required.');
            return null;
        }
        if (trim(strip_tags($body)) === '' && !str_contains($body, '<img')) {
            Flash::error('Write some content before saving.');
            return null;
        }

        $slugInput = $request->string('slug');
        $slug = $slugInput !== '' ? $repo->uniqueSlug($slugInput, $id) : $repo->uniqueSlug($title, $id);
        $status = $request->string('status') === 'published' ? 'published' : 'draft';
        $categoryId = (int) $request->string('category_id');
        $coverId = $this->optionalImageUpload($request, 'cover');
        $removeCover = $request->string('remove_cover') === '1';
        if ($coverId === null && $id !== null) {
            if ($removeCover) {
                $coverId = null;
            } else {
                $existing = $repo->find($id);
                $coverId = isset($existing['cover_id']) ? (int) $existing['cover_id'] : null;
                if ($coverId === 0) {
                    $coverId = null;
                }
            }
        }

        $publishedAt = $request->string('published_at');
        if ($status === 'published') {
            $publishedAt = $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt) ?: time()) : date('Y-m-d H:i:s');
        } else {
            $publishedAt = $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt) ?: time()) : null;
        }

        return [
            'slug' => $slug,
            'title' => $title,
            'excerpt' => $request->string('excerpt') ?: null,
            'body' => $body,
            'cover_id' => $coverId,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'status' => $status,
            'seo_title' => $request->string('seo_title') ?: null,
            'seo_description' => $request->string('seo_description') ?: null,
            'published_at' => $publishedAt,
        ];
    }
}
