<?php

declare(strict_types=1);

namespace Adheri\Modules\Testimonials;

use Adheri\Database;

final class TestimonialRepository
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return Database::fetchAll('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC');
    }

    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        return Database::fetchAll(
            'SELECT * FROM testimonials WHERE is_published = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM testimonials WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO testimonials (quote, person_name, location, photo_path, photo_id, sort_order, is_published)
             VALUES (:quote, :person_name, :location, :photo_path, :photo_id, :sort_order, :is_published)',
            $this->bindParams($data)
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE testimonials SET
                quote = :quote,
                person_name = :person_name,
                location = :location,
                photo_path = :photo_path,
                photo_id = :photo_id,
                sort_order = :sort_order,
                is_published = :is_published
             WHERE id = :id',
            $this->bindParams($data, $id)
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM testimonials WHERE id = :id', ['id' => $id]);
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function bindParams(array $data, ?int $id = null): array
    {
        $params = [
            'quote' => (string) ($data['quote'] ?? ''),
            'person_name' => (string) ($data['person_name'] ?? ''),
            'location' => ($data['location'] ?? '') !== '' ? (string) $data['location'] : null,
            'photo_path' => ($data['photo_path'] ?? '') !== '' ? (string) $data['photo_path'] : null,
            'photo_id' => isset($data['photo_id']) && $data['photo_id'] !== '' && $data['photo_id'] !== null
                ? (int) $data['photo_id'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => !empty($data['is_published']) ? 1 : 0,
        ];
        if ($id !== null) {
            $params['id'] = $id;
        }
        return $params;
    }
}
