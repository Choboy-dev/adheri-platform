<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Content\PageRepository;
use Adheri\Support\Flash;
use Adheri\Support\Url;

final class PageAdminController extends Controller
{
    public function index(Request $request): never
    {
        $this->adminView('admin/pages', [
            'title' => 'Pages',
            'items' => (new PageRepository())->all(),
        ]);
    }

    public function editForm(Request $request, string $id): never
    {
        $page = (new PageRepository())->find((int) $id);
        if ($page === null) {
            Flash::error('Page not found.');
            Response::redirect(Url::to('admin/pages'));
        }

        $this->adminView('admin/pages/form', [
            'title' => 'Edit page',
            'page' => $page,
            'sections' => (new PageRepository())->sectionsAdmin((int) $id),
        ]);
    }

    public function edit(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $pageId = (int) $id;
        $repo = new PageRepository();
        $page = $repo->find($pageId);
        if ($page === null) {
            Flash::error('Page not found.');
            Response::redirect(Url::to('admin/pages'));
        }

        $title = $request->string('title');
        if ($title === '') {
            Flash::error('Page title is required.');
            Response::redirect(Url::to('admin/pages/edit/' . $pageId));
        }

        $repo->updatePage($pageId, [
            'title' => $title,
            'seo_title' => $request->string('seo_title') ?: null,
            'seo_description' => $request->string('seo_description') ?: null,
        ]);

        $sections = $request->body['sections'] ?? [];
        if (is_array($sections)) {
            foreach ($sections as $sectionId => $payload) {
                if (!is_array($payload)) {
                    continue;
                }
                $sid = (int) $sectionId;
                if ($sid <= 0) {
                    continue;
                }
                $repo->updateSection(
                    $sid,
                    isset($payload['title']) ? trim((string) $payload['title']) : null,
                    isset($payload['body']) ? (string) $payload['body'] : null,
                    !empty($payload['is_visible']) ? 1 : 0
                );
            }
        }

        Flash::success('Page saved.');
        Response::redirect(Url::to('admin/pages/edit/' . $pageId));
    }
}
