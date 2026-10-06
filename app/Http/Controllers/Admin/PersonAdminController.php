<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Controllers\Admin\Concerns\HandlesAdminUploads;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Media\MediaLibrary;
use Adheri\Modules\People\PersonRepository;
use Adheri\Support\Flash;
use Adheri\Support\Url;

final class PersonAdminController extends Controller
{
    use HandlesAdminUploads;

    public function index(Request $request): never
    {
        $this->adminView('admin/people', [
            'title' => 'People',
            'items' => (new PersonRepository())->all(),
        ]);
    }

    public function createForm(Request $request): never
    {
        $this->adminView('admin/people/form', [
            'title' => 'Add person',
            'item' => null,
            'photoUrl' => '',
        ]);
    }

    public function create(Request $request): never
    {
        $this->requireCsrf($request);
        $data = $this->payloadFromRequest($request, null);
        if ($data === null) {
            Response::redirect(Url::to('admin/people/create'));
        }

        (new PersonRepository())->create($data);
        Flash::success('Person created.');
        Response::redirect(Url::to('admin/people'));
    }

    public function editForm(Request $request, string $id): never
    {
        $item = (new PersonRepository())->find((int) $id);
        if ($item === null) {
            Flash::error('Person not found.');
            Response::redirect(Url::to('admin/people'));
        }

        $this->adminView('admin/people/form', [
            'title' => 'Edit person',
            'item' => $item,
            'photoUrl' => $this->photoUrlForItem($item),
        ]);
    }

    public function edit(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $personId = (int) $id;
        $repo = new PersonRepository();
        if ($repo->find($personId) === null) {
            Flash::error('Person not found.');
            Response::redirect(Url::to('admin/people'));
        }

        $data = $this->payloadFromRequest($request, $personId);
        if ($data === null) {
            Response::redirect(Url::to('admin/people/edit/' . $personId));
        }

        $repo->update($personId, $data);
        Flash::success('Person updated.');
        Response::redirect(Url::to('admin/people'));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        (new PersonRepository())->delete((int) $id);
        Flash::success('Person deleted.');
        Response::redirect(Url::to('admin/people'));
    }

    /** @return array<string, mixed>|null */
    private function payloadFromRequest(Request $request, ?int $exceptId): ?array
    {
        $name = $request->string('name');
        if ($name === '') {
            Flash::error('Name is required.');
            return null;
        }

        $stored = $this->storeOptionalImage($request, 'photo', $name);
        if ($this->hasUploadAttempt($request, 'photo') && $stored === null) {
            return null;
        }

        $removePhoto = $request->string('remove_photo', '0') === '1';
        $photoId = $stored !== null ? (int) $stored['id'] : null;
        if ($photoId === null && $removePhoto) {
            $photoId = null;
        } elseif ($photoId === null && $exceptId !== null && !$removePhoto) {
            $existing = (new PersonRepository())->find($exceptId);
            if ($existing !== null && isset($existing['photo_id'])) {
                $photoId = (int) $existing['photo_id'];
                if ($photoId === 0) {
                    $photoId = null;
                }
            }
        }

        return [
            'name' => $name,
            'person_group' => $request->string('person_group', 'board'),
            'role_title' => $request->string('role_title'),
            'bio' => $request->string('bio'),
            'photo_id' => $photoId,
            'sort_order' => (int) $request->string('sort_order', '0'),
            'is_published' => $request->string('is_published', '0') === '1',
        ];
    }

    /** @param array<string, mixed> $item */
    private function photoUrlForItem(array $item): string
    {
        if (empty($item['photo_id'])) {
            return '';
        }
        $media = (new MediaLibrary())->find((int) $item['photo_id']);
        if ($media === null) {
            return '';
        }
        return Url::to((string) $media['url_path']);
    }
}
