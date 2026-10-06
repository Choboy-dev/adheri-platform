<?php

declare(strict_types=1);

namespace Adheri\Modules\Albums;

use Adheri\Database;
use Adheri\Support\Str;

final class AlbumRepository
{
    /** @return list<array<string, mixed>> */
    public function allForAdmin(): array
    {
        return Database::fetchAll(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM album_media am WHERE am.album_id = a.id) AS media_count,
                    c.url_path AS cover_url
             FROM albums a
             LEFT JOIN media_assets c ON c.id = a.cover_id
             ORDER BY a.sort_order ASC, a.id DESC'
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM albums WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $slug = $this->uniqueSlug((string) ($data['slug'] ?? ''), (string) $data['title']);
        Database::execute(
            'INSERT INTO albums (slug, title, description, cover_id, event_date, sort_order, is_published)
             VALUES (:slug, :title, :description, :cover_id, :event_date, :sort_order, :is_published)',
            [
                'slug' => $slug,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'cover_id' => $data['cover_id'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_published' => !empty($data['is_published']) ? 1 : 0,
            ]
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return;
        }
        $slugInput = (string) ($data['slug'] ?? '');
        $slug = $slugInput !== ''
            ? $this->uniqueSlug($slugInput, (string) $data['title'], $id)
            : (string) $existing['slug'];

        Database::execute(
            'UPDATE albums SET slug = :slug, title = :title, description = :description,
             cover_id = :cover_id, event_date = :event_date, sort_order = :sort_order, is_published = :is_published
             WHERE id = :id',
            [
                'id' => $id,
                'slug' => $slug,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'cover_id' => $data['cover_id'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_published' => !empty($data['is_published']) ? 1 : 0,
            ]
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM albums WHERE id = :id', ['id' => $id]);
    }

    public function attachMedia(int $albumId, int $mediaId, ?string $caption = null, int $sortOrder = 0): void
    {
        Database::execute(
            'INSERT INTO album_media (album_id, media_id, caption, sort_order)
             VALUES (:album_id, :media_id, :caption, :sort_order)
             ON DUPLICATE KEY UPDATE caption = VALUES(caption), sort_order = VALUES(sort_order)',
            [
                'album_id' => $albumId,
                'media_id' => $mediaId,
                'caption' => $caption,
                'sort_order' => $sortOrder,
            ]
        );
    }

    public function detachMedia(int $albumId, int $mediaId): void
    {
        Database::execute(
            'DELETE FROM album_media WHERE album_id = :album_id AND media_id = :media_id',
            ['album_id' => $albumId, 'media_id' => $mediaId]
        );
    }

    private function uniqueSlug(string $slug, string $title, ?int $exceptId = null): string
    {
        $base = Str::slug($slug !== '' ? $slug : $title);
        $candidate = $base;
        $n = 1;
        while ($this->slugTaken($candidate, $exceptId)) {
            $candidate = $base . '-' . $n;
            $n++;
        }
        return $candidate;
    }

    private function slugTaken(string $slug, ?int $exceptId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM albums WHERE slug = :slug' . ($exceptId !== null ? ' AND id != :id' : '') . ' LIMIT 1',
            $exceptId !== null ? ['slug' => $slug, 'id' => $exceptId] : ['slug' => $slug]
        );
        return $row !== null;
    }

    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        return Database::fetchAll(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM album_media am WHERE am.album_id = a.id) AS media_count,
                    COALESCE(c.url_path, (
                        SELECT m.url_path FROM album_media am2
                        JOIN media_assets m ON m.id = am2.media_id
                        WHERE am2.album_id = a.id
                        ORDER BY am2.sort_order ASC, am2.media_id ASC
                        LIMIT 1
                    )) AS cover_url
             FROM albums a
             LEFT JOIN media_assets c ON c.id = a.cover_id
             WHERE a.is_published = 1
             ORDER BY a.sort_order ASC, a.event_date DESC, a.id DESC'
        );
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT a.*, c.url_path AS cover_url
             FROM albums a
             LEFT JOIN media_assets c ON c.id = a.cover_id
             WHERE a.slug = :slug AND a.is_published = 1
             LIMIT 1',
            ['slug' => $slug]
        );
    }

    /** @return list<array<string, mixed>> */
    public function mediaForAlbum(int $albumId): array
    {
        return Database::fetchAll(
            'SELECT m.*, am.caption, am.sort_order
             FROM album_media am
             INNER JOIN media_assets m ON m.id = am.media_id
             WHERE am.album_id = :id
             ORDER BY am.sort_order ASC, m.id ASC',
            ['id' => $albumId]
        );
    }
}
