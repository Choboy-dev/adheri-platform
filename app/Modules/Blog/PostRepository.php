<?php

declare(strict_types=1);

namespace Adheri\Modules\Blog;

use Adheri\Database;
use Adheri\Support\Str;

final class PostRepository
{
    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        return Database::fetchAll(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = :status
             ORDER BY p.published_at DESC, p.id DESC',
            ['status' => 'published']
        );
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, m.url_path AS cover_url
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN media_assets m ON m.id = p.cover_id
             WHERE p.slug = :slug AND p.status = :status
             LIMIT 1',
            ['slug' => $slug, 'status' => 'published']
        );
    }

    /** @return list<array<string, mixed>> */
    public function allAdmin(int $limit = 12, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return Database::fetchAll(
            "SELECT p.*, c.name AS category_name, m.url_path AS cover_url
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN media_assets m ON m.id = p.cover_id
             ORDER BY p.updated_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function countAdmin(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS total FROM posts');
        return (int) ($row['total'] ?? 0);
    }

    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM posts WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO posts
             (slug, title, excerpt, body, cover_id, category_id, status, seo_title, seo_description, published_at)
             VALUES
             (:slug, :title, :excerpt, :body, :cover_id, :category_id, :status, :seo_title, :seo_description, :published_at)',
            $data
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::execute(
            'UPDATE posts SET
                slug = :slug,
                title = :title,
                excerpt = :excerpt,
                body = :body,
                cover_id = :cover_id,
                category_id = :category_id,
                status = :status,
                seo_title = :seo_title,
                seo_description = :seo_description,
                published_at = :published_at
             WHERE id = :id',
            $data
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM posts WHERE id = :id', ['id' => $id]);
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId !== null) {
            $row = Database::fetch(
                'SELECT id FROM posts WHERE slug = :slug AND id != :id LIMIT 1',
                ['slug' => $slug, 'id' => $ignoreId]
            );
        } else {
            $row = Database::fetch('SELECT id FROM posts WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        }
        return $row !== null;
    }

    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        return Database::fetchAll('SELECT * FROM categories ORDER BY name ASC');
    }
}
