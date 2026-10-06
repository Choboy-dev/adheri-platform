<?php

declare(strict_types=1);

namespace Adheri\Modules\Donations;

use Adheri\Config;

/**
 * Payment gateway seam. Flutterwave adapter implementation.
 * Keys stay in env / SiteSetting — never hardcode live secrets.
 */
final class FlutterwaveGateway
{
    public function isConfigured(): bool
    {
        return (Config::get('FLUTTERWAVE_PUBLIC_KEY') ?? '') !== ''
            && (Config::get('FLUTTERWAVE_SECRET_KEY') ?? '') !== '';
    }

    public function publicKey(): string
    {
        return (string) Config::get('FLUTTERWAVE_PUBLIC_KEY', '');
    }

    /** @param array<string, mixed> $payload */
    public function initializePayment(array $payload): array
    {
        // Placeholder for server-side initialize call.
        // Controllers may also use inline JS checkout with public key.
        return [
            'status' => 'ready',
            'public_key' => $this->publicKey(),
            'tx_ref' => $payload['tx_ref'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'] ?? 'NGN',
            'customer' => $payload['customer'],
            'redirect_url' => $payload['redirect_url'],
        ];
    }

    public function verifyTransaction(string $transactionId): array
    {
        $secret = (string) Config::get('FLUTTERWAVE_SECRET_KEY', '');
        if ($secret === '') {
            return ['status' => 'error', 'message' => 'Flutterwave is not configured.'];
        }

        $ch = curl_init('https://api.flutterwave.com/v3/transactions/' . rawurlencode($transactionId) . '/verify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secret,
                'Content-Type: application/json',
            ],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['status' => 'error', 'message' => 'Unable to reach Flutterwave.'];
        }

        $json = json_decode($raw, true);
        return is_array($json) ? $json : ['status' => 'error', 'message' => 'Invalid verify response', 'http' => $code];
    }
}
