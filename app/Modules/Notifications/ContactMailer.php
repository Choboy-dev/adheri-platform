<?php

declare(strict_types=1);

namespace Adheri\Modules\Notifications;

use Adheri\Config;
use Adheri\Database;

/**
 * Sends public form notifications to ADHERI inboxes via Resend.
 */
final class ContactMailer
{
    public function __construct(
        private readonly ResendClient $client = new ResendClient(),
    ) {
    }

    /**
     * @param array{first_name: string, last_name: string, email: string, phone?: string|null, message: string} $inquiry
     * @param array<string, string|null> $settings
     */
    public function sendContactInquiry(array $inquiry, array $settings): bool
    {
        $fullName = trim($inquiry['first_name'] . ' ' . $inquiry['last_name']);
        $rows = [
            'Name' => $fullName,
            'Email' => $inquiry['email'],
        ];
        if (!empty($inquiry['phone'])) {
            $rows['Phone'] = (string) $inquiry['phone'];
        }
        $rows['Message'] = $inquiry['message'];

        return $this->dispatch(
            type: 'contact',
            subject: 'New contact message from ' . $fullName,
            heading: 'New contact form submission',
            intro: 'Someone submitted the contact form on the ADHERI website.',
            rows: $rows,
            replyTo: $inquiry['email'],
            settings: $settings,
            context: $inquiry,
        );
    }

    /**
     * @param array{
     *   full_name: string,
     *   email: string,
     *   phone?: string|null,
     *   location?: string|null,
     *   interests?: list<string>,
     *   availability?: string|null,
     *   message: string
     * } $inquiry
     * @param array<string, string|null> $settings
     */
    public function sendVolunteerInquiry(array $inquiry, array $settings): bool
    {
        $rows = [
            'Name' => $inquiry['full_name'],
            'Email' => $inquiry['email'],
        ];
        if (!empty($inquiry['phone'])) {
            $rows['Phone'] = (string) $inquiry['phone'];
        }
        if (!empty($inquiry['location'])) {
            $rows['Location'] = (string) $inquiry['location'];
        }
        $interests = $inquiry['interests'] ?? [];
        if ($interests !== []) {
            $rows['Interests'] = implode(', ', $interests);
        }
        if (!empty($inquiry['availability'])) {
            $rows['Availability'] = (string) $inquiry['availability'];
        }
        $rows['How they want to help'] = $inquiry['message'];

        return $this->dispatch(
            type: 'volunteer',
            subject: 'New volunteer interest from ' . $inquiry['full_name'],
            heading: 'New volunteer form submission',
            intro: 'Someone offered to volunteer with ADHERI.',
            rows: $rows,
            replyTo: $inquiry['email'],
            settings: $settings,
            context: $inquiry,
        );
    }

    /**
     * @param array<string, string> $rows
     * @param array<string, string|null> $settings
     * @param array<string, mixed> $context
     */
    private function dispatch(
        string $type,
        string $subject,
        string $heading,
        string $intro,
        array $rows,
        string $replyTo,
        array $settings,
        array $context,
    ): bool {
        if (!$this->client->isConfigured()) {
            $this->log('resend', 'none', $subject . ' (Resend not configured)', $context, [
                'type' => $type,
                'skipped' => true,
            ]);
            return false;
        }

        $recipients = $this->recipients($settings);
        if ($recipients === []) {
            $this->log('resend', 'none', $subject . ' (no recipients)', $context, [
                'type' => $type,
                'error' => 'No valid contact recipients configured.',
            ]);
            return false;
        }

        $html = $this->buildHtml($heading, $intro, $rows);
        $text = $this->buildText($heading, $rows);

        $result = $this->client->send(
            $this->fromAddress(),
            $recipients,
            $subject,
            $html,
            $replyTo,
            $text,
        );

        $this->log(
            'resend',
            implode(', ', $recipients),
            $subject,
            $context,
            array_merge(['type' => $type], $result),
        );

        return $result['ok'] ?? false;
    }

    /** @param array<string, string|null> $settings */
    /** @return list<string> */
    private function recipients(array $settings): array
    {
        $candidates = [
            $settings['contact_email_primary'] ?? null,
            $settings['contact_email_info'] ?? null,
            Config::get('MAIL_INQUIRY_TO'),
        ];

        $emails = [];
        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            foreach (preg_split('/\s*,\s*/', (string) $candidate) ?: [] as $email) {
                $email = trim($email);
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[strtolower($email)] = $email;
                }
            }
        }

        return array_values($emails);
    }

    private function fromAddress(): string
    {
        $name = (string) Config::get('MAIL_FROM_NAME', 'ADHERI');
        $email = (string) Config::get('MAIL_FROM', 'onboarding@resend.dev');

        return sprintf('%s <%s>', $name, $email);
    }

    /** @param array<string, string> $rows */
    private function buildHtml(string $heading, string $intro, array $rows): string
    {
        $table = '';
        foreach ($rows as $label => $value) {
            $table .= '<tr><th align="left" style="padding:8px 12px;border-bottom:1px solid #e5e7eb;vertical-align:top;">'
                . $this->e($label) . '</th>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;white-space:pre-wrap;">'
                . $this->e($value) . '</td></tr>';
        }

        return '<!DOCTYPE html><html><body style="font-family:system-ui,sans-serif;color:#111827;line-height:1.5;">'
            . '<h2 style="margin:0 0 16px;color:#0071BC;">' . $this->e($heading) . '</h2>'
            . '<p style="margin:0 0 16px;">' . $this->e($intro) . '</p>'
            . '<table style="border-collapse:collapse;width:100%;max-width:560px;">' . $table . '</table>'
            . '<p style="margin:24px 0 0;font-size:12px;color:#6b7280;">Reply directly to this email to respond to the sender.</p>'
            . '</body></html>';
    }

    /** @param array<string, string> $rows */
    private function buildText(string $heading, array $rows): string
    {
        $lines = [$heading, ''];
        foreach ($rows as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $payload */
    /** @param array<string, mixed> $meta */
    private function log(string $channel, string $recipient, string $subject, array $payload, array $meta): void
    {
        try {
            Database::execute(
                'INSERT INTO notification_logs (channel, recipient, subject, body, context_json)
                 VALUES (:channel, :recipient, :subject, :body, :context_json)',
                [
                    'channel' => $channel,
                    'recipient' => $recipient,
                    'subject' => $subject,
                    'body' => is_string($payload['message'] ?? null) ? $payload['message'] : null,
                    'context_json' => json_encode([
                        'payload' => $payload,
                        'meta' => $meta,
                    ], JSON_THROW_ON_ERROR),
                ]
            );
        } catch (\Throwable) {
            // Delivery should not break form flow if logging fails.
        }
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
