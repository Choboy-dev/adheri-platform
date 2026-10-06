<?php

declare(strict_types=1);

namespace Adheri\Modules\Inquiries;

use Adheri\Database;

final class InquiryRepository
{
    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO inquiries
              (type, first_name, last_name, full_name, email, phone, location, interests_json, availability, message, status)
             VALUES
              (:type, :first_name, :last_name, :full_name, :email, :phone, :location, :interests_json, :availability, :message, :status)',
            [
                'type' => $data['type'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'full_name' => $data['full_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'location' => $data['location'] ?? null,
                'interests_json' => $data['interests_json'] ?? null,
                'availability' => $data['availability'] ?? null,
                'message' => $data['message'],
                'status' => 'new',
            ]
        );
        return (int) Database::lastId();
    }

    /** @return list<array<string, mixed>> */
    public function latest(int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT * FROM inquiries ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
    }

    public function countNew(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS c FROM inquiries WHERE status = :status',
            ['status' => 'new']
        );
        return (int) ($row['c'] ?? 0);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM inquiries WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['new', 'read', 'archived'], true)) {
            return false;
        }
        return Database::execute(
            'UPDATE inquiries SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $id]
        ) > 0;
    }
}
