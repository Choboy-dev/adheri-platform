<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Media\MediaLibrary;
use Adheri\Support\Flash;
use Adheri\Support\Url;
use InvalidArgumentException;

final class MediaController extends Controller
{
    public function upload(Request $request): never
    {
        $this->requireCsrf($request);
        $library = new MediaLibrary();
        $alt = $request->string('alt_text');
        $uploaded = 0;
        $errors = [];

        $files = $request->files['files'] ?? $request->files['file'] ?? null;
        if ($files === null) {
            Flash::error('No file selected.');
            Response::redirect(Url::to('admin/media'));
        }

        $batch = $this->normalizeFiles($files);
        foreach ($batch as $file) {
            try {
                $library->storeUploadedFile($file, $alt !== '' ? $alt : null);
                $uploaded++;
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($uploaded > 0) {
            Flash::success($uploaded === 1 ? 'Image uploaded.' : $uploaded . ' images uploaded.');
        }
        if ($errors !== []) {
            Flash::error(implode(' ', array_unique($errors)));
        }

        Response::redirect(Url::to('admin/media'));
    }

    public function delete(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $mediaId = (int) $id;
        if ($mediaId > 0) {
            (new MediaLibrary())->delete($mediaId);
            Flash::success('Media deleted.');
        }
        Response::redirect(Url::to('admin/media'));
    }

    /**
     * @param array<string, mixed> $files
     * @return list<array<string, mixed>>
     */
    private function normalizeFiles(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [$files];
        }

        $out = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $out[] = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
    }
}
