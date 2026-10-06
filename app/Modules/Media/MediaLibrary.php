<?php

declare(strict_types=1);

namespace Adheri\Modules\Media;

use Adheri\Config;
use Adheri\Database;
use InvalidArgumentException;
use RuntimeException;

final class MediaLibrary
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * @param array{error?: int, tmp_name?: string, name?: string, size?: int, type?: string} $file
     * @return array{id: int, url_path: string, disk_path: string}
     */
    public function storeUploadedFile(array $file, ?string $alt = null): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed or no file was provided.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException('Invalid upload.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image must be between 1 byte and 8 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName) ?: '';
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            throw new InvalidArgumentException('Only JPEG, PNG, WebP, and GIF images are allowed.');
        }

        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            throw new InvalidArgumentException('The file is not a valid image.');
        }

        $ext = self::MIME_EXTENSIONS[$mime];
        $subdir = date('Y') . '/' . date('m');
        $uploadRoot = rtrim((string) Config::get('UPLOAD_PATH'), '/');
        $targetDir = $uploadRoot . '/' . $subdir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Could not create upload directory.');
        }

        $basename = bin2hex(random_bytes(8)) . '.' . $ext;
        $diskPath = $targetDir . '/' . $basename;
        if (!move_uploaded_file($tmpName, $diskPath)) {
            throw new RuntimeException('Could not save uploaded file.');
        }

        $urlPath = 'uploads/' . $subdir . '/' . $basename;
        $relativeDisk = 'public/' . $urlPath;

        Database::execute(
            'INSERT INTO media_assets (disk_path, url_path, mime_type, original_name, alt_text, width, height, size_bytes)
             VALUES (:disk_path, :url_path, :mime_type, :original_name, :alt_text, :width, :height, :size_bytes)',
            [
                'disk_path' => $relativeDisk,
                'url_path' => $urlPath,
                'mime_type' => $mime,
                'original_name' => (string) ($file['name'] ?? $basename),
                'alt_text' => $alt !== null && $alt !== '' ? $alt : null,
                'width' => (int) ($imageInfo[0] ?? 0) ?: null,
                'height' => (int) ($imageInfo[1] ?? 0) ?: null,
                'size_bytes' => $size,
            ]
        );

        $id = (int) Database::lastId();

        return [
            'id' => $id,
            'url_path' => $urlPath,
            'disk_path' => $diskPath,
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM media_assets WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $row = $this->find($id);
        if ($row === null) {
            return false;
        }

        Database::execute('DELETE FROM media_assets WHERE id = :id', ['id' => $id]);

        $diskPath = (string) ($row['disk_path'] ?? '');
        $absolute = $this->absolutePath($diskPath);
        if ($absolute !== null && is_file($absolute)) {
            @unlink($absolute);
        }

        return true;
    }

    private function absolutePath(string $diskPath): ?string
    {
        if ($diskPath === '') {
            return null;
        }
        if (str_starts_with($diskPath, '/')) {
            return $diskPath;
        }
        return rtrim(Config::root(), '/') . '/' . ltrim($diskPath, '/');
    }
}
