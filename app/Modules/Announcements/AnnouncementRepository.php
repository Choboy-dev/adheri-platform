<?php

declare(strict_types=1);

namespace Adheri\Modules\Announcements;

use Adheri\Database;

final class AnnouncementRepository
{
    /** @return list<array<string, mixed>> */
    public function allForAdmin(): array
    {
        return Database::fetchAll(
            'SELECT a.*, m.url_path AS image_url
             FROM announcements a
             INNER JOIN media_assets m ON m.id = a.image_id
             ORDER BY a.sort_order ASC, a.id DESC'
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM announcements WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO announcements (title, link_url, image_id, sort_order, is_published, starts_at, ends_at)
             VALUES (:title, :link_url, :image_id, :sort_order, :is_published, :starts_at, :ends_at)',
            [
                'title' => $data['title'] ?? null,
                'link_url' => $data['link_url'] ?? null,
                'image_id' => (int) $data['image_id'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_published' => !empty($data['is_published']) ? 1 : 0,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE announcements SET title = :title, link_url = :link_url, image_id = :image_id,
             sort_order = :sort_order, is_published = :is_published, starts_at = :starts_at, ends_at = :ends_at
             WHERE id = :id',
            [
                'id' => $id,
                'title' => $data['title'] ?? null,
                'link_url' => $data['link_url'] ?? null,
                'image_id' => (int) $data['image_id'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_published' => !empty($data['is_published']) ? 1 : 0,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM announcements WHERE id = :id', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function publishedActive(): array
    {
        return Database::fetchAll(
            'SELECT a.*, m.url_path AS image_url, m.alt_text
             FROM announcements a
             INNER JOIN media_assets m ON m.id = a.image_id
             WHERE a.is_published = 1
               AND (a.starts_at IS NULL OR a.starts_at <= NOW())
               AND (a.ends_at IS NULL OR a.ends_at >= NOW())
             ORDER BY a.sort_order ASC, a.id DESC'
        );
    }
}
