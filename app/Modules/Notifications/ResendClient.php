<?php

declare(strict_types=1);

namespace Adheri\Modules\Notifications;

use Adheri\Config;

/**
 * Resend email API adapter. Keys stay in env — never hardcode secrets.
 */
final class ResendClient
{
    private const API_URL = 'https://api.resend.com/emails';

    public function isConfigured(): bool
    {
        return (Config::get('RESEND_API_KEY') ?? '') !== '';
    }

    /**
     * @param list<string> $to
     * @return array{ok: bool, id?: string, error?: string, http?: int}
     */
    public function send(string $from, array $to, string $subject, string $html, ?string $replyTo = null, ?string $text = null): array
    {
        $apiKey = (string) Config::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Resend is not configured.'];
        }

        $to = array_values(array_filter($to, static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));
        if ($to === []) {
            return ['ok' => false, 'error' => 'No valid recipients.'];
        }

        $payload = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $payload['reply_to'] = $replyTo;
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => $curlError !== '' ? $curlError : 'Unable to reach Resend.', 'http' => $http];
        }

        /** @var array<string, mixed>|null $json */
        $json = json_decode($raw, true);
        if ($http >= 200 && $http < 300 && is_array($json) && isset($json['id'])) {
            return ['ok' => true, 'id' => (string) $json['id'], 'http' => $http];
        }

        $message = 'Resend request failed.';
        if (is_array($json)) {
            if (isset($json['message']) && is_string($json['message'])) {
                $message = $json['message'];
            } elseif (isset($json['error']) && is_string($json['error'])) {
                $message = $json['error'];
            }
        }

        return ['ok' => false, 'error' => $message, 'http' => $http];
    }
}
