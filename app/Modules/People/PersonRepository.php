<?php

declare(strict_types=1);

namespace Adheri\Modules\People;

use Adheri\Database;

final class PersonRepository
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return Database::fetchAll(
            'SELECT p.*, m.url_path AS photo_url
             FROM people p
             LEFT JOIN media_assets m ON m.id = p.photo_id
             ORDER BY p.person_group ASC, p.sort_order ASC, p.id ASC'
        );
    }

    /** @return list<array<string, mixed>> */
    public function publishedByGroup(string $group): array
    {
        if (!in_array($group, ['board', 'youth_advisory'], true)) {
            return [];
        }

        return Database::fetchAll(
            'SELECT p.*, m.url_path AS photo_url
             FROM people p
             LEFT JOIN media_assets m ON m.id = p.photo_id
             WHERE p.is_published = 1 AND p.person_group = :group
             ORDER BY p.sort_order ASC, p.id ASC',
            ['group' => $group]
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM people WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO people (name, person_group, role_title, bio, photo_id, sort_order, is_published)
             VALUES (:name, :person_group, :role_title, :bio, :photo_id, :sort_order, :is_published)',
            $this->bindParams($data)
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE people SET
                name = :name,
                person_group = :person_group,
                role_title = :role_title,
                bio = :bio,
                photo_id = :photo_id,
                sort_order = :sort_order,
                is_published = :is_published
             WHERE id = :id',
            $this->bindParams($data, $id)
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM people WHERE id = :id', ['id' => $id]);
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function bindParams(array $data, ?int $id = null): array
    {
        $group = (string) ($data['person_group'] ?? 'board');
        if (!in_array($group, ['board', 'youth_advisory'], true)) {
            $group = 'board';
        }

        $params = [
            'name' => (string) ($data['name'] ?? ''),
            'person_group' => $group,
            'role_title' => ($data['role_title'] ?? '') !== '' ? (string) $data['role_title'] : null,
            'bio' => ($data['bio'] ?? '') !== '' ? (string) $data['bio'] : null,
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
