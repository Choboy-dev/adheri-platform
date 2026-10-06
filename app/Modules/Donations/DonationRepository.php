<?php

declare(strict_types=1);

namespace Adheri\Modules\Donations;

use Adheri\Config;
use Adheri\Database;

final class DonationRepository
{
    public function minimumAmount(): int
    {
        return Config::int('DONATION_MIN_AMOUNT', 1000);
    }

    /** @param array<string, mixed> $data */
    public function createPending(array $data): int
    {
        Database::execute(
            'INSERT INTO donations
              (donor_name, donor_email, donor_phone, amount, currency, status, flutterwave_tx_ref)
             VALUES
              (:donor_name, :donor_email, :donor_phone, :amount, :currency, :status, :tx_ref)',
            [
                'donor_name' => $data['donor_name'],
                'donor_email' => $data['donor_email'],
                'donor_phone' => $data['donor_phone'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'NGN',
                'status' => 'pending',
                'tx_ref' => $data['flutterwave_tx_ref'],
            ]
        );
        return (int) Database::lastId();
    }

    public function findByTxRef(string $txRef): ?array
    {
        return Database::fetch(
            'SELECT * FROM donations WHERE flutterwave_tx_ref = :ref LIMIT 1',
            ['ref' => $txRef]
        );
    }

    public function markStatus(string $txRef, string $status, ?string $flutterwaveId = null): void
    {
        Database::execute(
            'UPDATE donations SET status = :status, flutterwave_id = COALESCE(:fw_id, flutterwave_id), updated_at = NOW()
             WHERE flutterwave_tx_ref = :ref',
            ['status' => $status, 'fw_id' => $flutterwaveId, 'ref' => $txRef]
        );
    }

    /** @return list<array<string, mixed>> */
    public function latest(int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT * FROM donations ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
    }
}
