<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Src\Support\Infrastructure\Html\HtmlSanitizer;

/**
 * شكل الرد اللي بيرجع للنموذج — بيانات في حقول JSON، مش نص حر.
 *
 * محتوى كتبه مستخدمين ممكن يكون فيه «تجاهل التعليمات السابقة». التنقية
 * مستحيلة، فالحماية في الشكل: النص جوه حقل بيانات، HTML بيتحوّل لنص
 * عادي، والطول محدود (docs/23 بند ٥).
 */
final class ToolOutput
{
    /** HTML ← نص عادي: منقّى الأول، وبعدين من غير وسوم، وبطول محدود. */
    public static function plainText(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $clean = app(HtmlSanitizer::class)->clean($html);

        // الكتل بتبقى سطور — من غيرها الفقرات بتلزق في بعض
        $text = (string) preg_replace('#<(?:br|/p|/div|/li|/h[1-6]|/blockquote)\s*/?>#i', "\n", $clean);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace("/[ \t]+/u", ' ', (string) preg_replace("/\n{3,}/", "\n\n", $text)));

        return self::limitText($text);
    }

    public static function limitText(string $text): string
    {
        $max = (int) config('ai.max_text_length', 4_000);

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max).'…' : $text;
    }

    /**
     * بيقصّ القوائم لحد ما الرد يدخل في الحد المسموح، وبيقول إنه قصّ.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function fit(array $payload): array
    {
        $max = (int) config('ai.max_response_bytes', 60_000);

        while (self::size($payload) > $max && is_array($payload['items'] ?? null) && $payload['items'] !== []) {
            array_pop($payload['items']);
            $payload['truncated'] = true;
            $payload['notice'] = __('mcp.output.truncated');
        }

        if (self::size($payload) > $max) {
            return ['truncated' => true, 'notice' => __('mcp.output.too_large')];
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private static function size(array $payload): int
    {
        return strlen((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
