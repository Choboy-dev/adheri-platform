<?php

declare(strict_types=1);

namespace Adheri\Modules\Programs;

use Adheri\Database;

final class ProgramRepository
{
    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        return Database::fetchAll(
            'SELECT * FROM programs WHERE is_published = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return list<array<string, mixed>> */
    public function featuredOnHome(int $limit = 4): array
    {
        return Database::fetchAll(
            'SELECT * FROM programs WHERE is_published = 1 AND featured_on_home = 1
             ORDER BY sort_order ASC, id ASC LIMIT ' . (int) $limit
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT * FROM programs WHERE slug = :slug AND is_published = 1 LIMIT 1',
            ['slug' => $slug]
        );
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return Database::fetchAll(
            'SELECT p.*, m.url_path AS image_url
             FROM programs p
             LEFT JOIN media_assets m ON m.id = p.image_id
             ORDER BY p.sort_order ASC, p.id ASC'
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM programs WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO programs (slug, title, summary, body, image_id, sort_order, featured_on_home, is_published)
             VALUES (:slug, :title, :summary, :body, :image_id, :sort_order, :featured_on_home, :is_published)',
            $this->bindParams($data)
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE programs SET
                slug = :slug,
                title = :title,
                summary = :summary,
                body = :body,
                image_id = :image_id,
                sort_order = :sort_order,
                featured_on_home = :featured_on_home,
                is_published = :is_published
             WHERE id = :id',
            $this->bindParams($data, $id)
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM programs WHERE id = :id', ['id' => $id]);
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM programs WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    public function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base;
        $n = 2;
        while ($this->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function bindParams(array $data, ?int $id = null): array
    {
        $params = [
            'slug' => (string) ($data['slug'] ?? ''),
            'title' => (string) ($data['title'] ?? ''),
            'summary' => ($data['summary'] ?? '') !== '' ? (string) $data['summary'] : null,
            'body' => ($data['body'] ?? '') !== '' ? (string) $data['body'] : null,
            'image_id' => isset($data['image_id']) && $data['image_id'] !== '' && $data['image_id'] !== null
                ? (int) $data['image_id'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'featured_on_home' => !empty($data['featured_on_home']) ? 1 : 0,
            'is_published' => !empty($data['is_published']) ? 1 : 0,
        ];
        if ($id !== null) {
            $params['id'] = $id;
        }
        return $params;
    }
}
