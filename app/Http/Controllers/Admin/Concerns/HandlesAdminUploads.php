<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin\Concerns;

use Adheri\Http\Request;
use Adheri\Modules\Media\MediaLibrary;
use Adheri\Support\Flash;
use InvalidArgumentException;

trait HandlesAdminUploads
{
    protected function hasUploadAttempt(Request $request, string $field): bool
    {
        $file = $request->files[$field] ?? null;
        return is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @return array{id: int, url_path: string, disk_path: string}|null */
    protected function storeOptionalImage(Request $request, string $field = 'image', ?string $alt = null): ?array
    {
        $file = $request->files[$field] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            return (new MediaLibrary())->storeUploadedFile($file, $alt);
        } catch (InvalidArgumentException $e) {
            Flash::error($e->getMessage());
            return null;
        }
    }

    protected function optionalImageUpload(Request $request, string $field = 'image', ?string $alt = null): ?int
    {
        $stored = $this->storeOptionalImage($request, $field, $alt);
        return $stored !== null ? (int) $stored['id'] : null;
    }

    protected function optionalImageIdFromRequest(Request $request): ?int
    {
        $raw = $request->string('image_id');
        if ($raw === '') {
            return null;
        }
        $id = (int) $raw;
        return $id > 0 ? $id : null;
    }
}
