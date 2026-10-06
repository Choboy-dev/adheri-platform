<?php

declare(strict_types=1);

namespace Adheri\Modules\Content;

use Adheri\Database;

final class PageRepository
{
    public function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM pages WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    }

    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM pages WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return Database::fetchAll('SELECT * FROM pages ORDER BY title ASC');
    }

    /** @return array<string, array<string, mixed>> */
    public function sectionsForPage(int $pageId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM page_sections WHERE page_id = :id AND is_visible = 1 ORDER BY sort_order ASC',
            ['id' => $pageId]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['section_key']] = $row;
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public function sectionsAdmin(int $pageId): array
    {
        return Database::fetchAll(
            'SELECT * FROM page_sections WHERE page_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $pageId]
        );
    }

    /** @param array<string, mixed> $data */
    public function updatePage(int $id, array $data): void
    {
        Database::execute(
            'UPDATE pages SET title = :title, seo_title = :seo_title, seo_description = :seo_description WHERE id = :id',
            [
                'id' => $id,
                'title' => $data['title'],
                'seo_title' => $data['seo_title'],
                'seo_description' => $data['seo_description'],
            ]
        );
    }

    public function updateSection(int $sectionId, ?string $title, ?string $body, int $isVisible): void
    {
        Database::execute(
            'UPDATE page_sections SET title = :title, body = :body, is_visible = :is_visible WHERE id = :id',
            [
                'id' => $sectionId,
                'title' => $title,
                'body' => $body,
                'is_visible' => $isVisible,
            ]
        );
    }
}
