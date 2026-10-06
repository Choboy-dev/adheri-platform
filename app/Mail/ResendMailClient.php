<?php

declare(strict_types=1);

namespace Adheri\Mail;

use Adheri\Config;

/**
 * Resend HTTP client for webmail send/receive + Svix webhook verify.
 * Separate from Adheri\Modules\Notifications\ResendClient (transactional forms).
 */
final class ResendMailClient
{
    private const API = 'https://api.resend.com';

    public function isConfigured(): bool
    {
        return (Config::get('RESEND_API_KEY') ?? '') !== '';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok:bool,id?:string,error?:string,http?:int,raw?:array}
     */
    public function sendEmail(array $payload): array
    {
        return $this->request('POST', '/emails', $payload);
    }

    /** @return array{ok:bool,error?:string,http?:int} */
    public function cancelEmail(string $emailId): array
    {
        return $this->request('POST', '/emails/' . rawurlencode($emailId) . '/cancel', []);
    }

    /** @return array{ok:bool,data?:array,error?:string,http?:int} */
    public function getReceivedEmail(string $emailId): array
    {
        $res = $this->request('GET', '/emails/receiving/' . rawurlencode($emailId), null);
        if ($res['ok'] && isset($res['raw']) && is_array($res['raw'])) {
            return ['ok' => true, 'data' => $res['raw'], 'http' => $res['http'] ?? 0];
        }
        return ['ok' => false, 'error' => $res['error'] ?? 'Fetch failed', 'http' => $res['http'] ?? 0];
    }

    /** @return array{ok:bool,content?:string,error?:string} */
    public function downloadReceivedAttachment(string $emailId, string $attachmentId): array
    {
        $apiKey = (string) Config::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Resend not configured'];
        }
        $url = self::API . '/emails/receiving/' . rawurlencode($emailId) . '/attachments/' . rawurlencode($attachmentId);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
        ]);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $http >= 400) {
            return ['ok' => false, 'error' => 'Attachment download failed'];
        }
        return ['ok' => true, 'content' => (string) $raw];
    }

    /**
     * @return array{ok:bool,data?:list<array>,error?:string}
     */
    public function listReceivedEmails(int $limit = 50): array
    {
        $res = $this->request('GET', '/emails/receiving?limit=' . $limit, null);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'] ?? 'List failed'];
        }
        $raw = $res['raw'] ?? [];
        $data = [];
        if (is_array($raw)) {
            if (isset($raw['data']) && is_array($raw['data'])) {
                $data = $raw['data'];
            } elseif (array_is_list($raw)) {
                $data = $raw;
            }
        }
        return ['ok' => true, 'data' => $data];
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $secret = (string) Config::get('RESEND_WEBHOOK_SECRET', '');
        $env = (string) Config::get('APP_ENV', 'production');
        if ($secret === '') {
            return $env !== 'production';
        }

        $id = $headers['svix-id'] ?? $headers['Svix-Id'] ?? '';
        $timestamp = $headers['svix-timestamp'] ?? $headers['Svix-Timestamp'] ?? '';
        $signature = $headers['svix-signature'] ?? $headers['Svix-Signature'] ?? '';
        if ($id === '' || $timestamp === '' || $signature === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $key = $secret;
        if (str_starts_with($secret, 'whsec_')) {
            $decoded = base64_decode(substr($secret, 6), true);
            if ($decoded !== false) {
                $key = $decoded;
            }
        }

        $toSign = $id . '.' . $timestamp . '.' . $payload;
        $expected = base64_encode(hash_hmac('sha256', $toSign, $key, true));

        foreach (explode(' ', $signature) as $part) {
            $part = trim($part);
            if (str_starts_with($part, 'v1,')) {
                $sig = substr($part, 3);
                if (hash_equals($expected, $sig)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{ok:bool,id?:string,error?:string,http?:int,raw?:array}
     */
    private function request(string $method, string $path, ?array $payload): array
    {
        $apiKey = (string) Config::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Resend is not configured.'];
        }

        $ch = curl_init(self::API . $path);
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
        ];
        if ($payload !== null && $method !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($raw === false) {
            $message = $err !== '' ? $err : 'curl failed';
            if ($errno === CURLE_OPERATION_TIMEDOUT || $errno === 28) {
                $message = 'Sending timed out. Try smaller attachments or send without them.';
            }
            return ['ok' => false, 'error' => $message, 'http' => $http];
        }

        /** @var array<string, mixed>|null $json */
        $json = json_decode($raw, true);
        if ($http >= 200 && $http < 300) {
            $out = ['ok' => true, 'http' => $http, 'raw' => is_array($json) ? $json : []];
            if (is_array($json) && isset($json['id'])) {
                $out['id'] = (string) $json['id'];
            }
            return $out;
        }

        $message = 'Resend request failed.';
        if (is_array($json) && isset($json['message']) && is_string($json['message'])) {
            $message = $json['message'];
        }
        return ['ok' => false, 'error' => $message, 'http' => $http, 'raw' => is_array($json) ? $json : []];
    }
}
