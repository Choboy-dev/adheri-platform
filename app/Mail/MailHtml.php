<?php

declare(strict_types=1);

namespace Adheri\Mail;

/**
 * Sanitize outbound / stored HTML for compose and quotes.
 */
final class MailHtml
{
    private const ALLOW_TAGS = [
        'p', 'div', 'span', 'br', 'hr', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
        'a', 'ul', 'ol', 'li', 'blockquote', 'img',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
    ];

    public static function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button|textarea|select|link|meta|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button|textarea|select|link|meta|style)[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;

        $allowed = '<' . implode('><', self::ALLOW_TAGS) . '>';
        $clean = strip_tags($html, $allowed);

        $clean = preg_replace_callback(
            '/<a\s+([^>]*?)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                $href = '';
                if (preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $hm)) {
                    $href = $hm[2] !== '' ? $hm[2] : ($hm[3] !== '' ? $hm[3] : $hm[4]);
                }
                if ($href === '' || preg_match('#^\s*javascript:#i', $href)) {
                    return '<a>';
                }
                $safe = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return '<a href="' . $safe . '" rel="noopener noreferrer nofollow" target="_blank">';
            },
            $clean
        ) ?? $clean;

        $clean = preg_replace_callback(
            '/<img\s+([^>]*?)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                $src = '';
                if (preg_match('/src\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $sm)) {
                    $src = $sm[2] !== '' ? $sm[2] : ($sm[3] !== '' ? $sm[3] : $sm[4]);
                }
                if ($src === '' || (!str_starts_with($src, 'https://') && !str_starts_with($src, 'http://') && !str_starts_with($src, 'data:image/'))) {
                    return '';
                }
                $safe = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return '<img src="' . $safe . '" alt="" style="max-width:100%;height:auto">';
            },
            $clean
        ) ?? $clean;

        return $clean;
    }

    public static function toText(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }
}
