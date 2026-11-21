<?php

declare(strict_types=1);

namespace Src\Support\Domain\ValueObjects;

/**
 * قراءة بسيطة لـ User-Agent — من غير باكدج خارجي.
 *
 * الهدف مش تحليل دقيق، الهدف إن المستخدم يعرف «Chrome على Windows»
 * بدل سطر تقني مالوش معنى (docs/12 بند ٢).
 */
final readonly class DeviceSignature
{
    private function __construct(
        public string $fingerprint,
        public string $platform,
        public string $browser,
        public string $name,
    ) {}

    public static function fromUserAgent(?string $userAgent): self
    {
        $userAgent ??= '';

        $platform = self::detectPlatform($userAgent);
        $browser = self::detectBrowser($userAgent);

        return new self(
            fingerprint: hash('sha256', $platform.'|'.$browser.'|'.$userAgent),
            platform: $platform,
            browser: $browser,
            name: trim("{$browser} · {$platform}", ' ·'),
        );
    }

    private static function detectPlatform(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows NT') => 'Windows',
            str_contains($userAgent, 'iPhone') => 'iPhone',
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'CrOS') => 'ChromeOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => '—',
        };
    }

    private static function detectBrowser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => '—',
        };
    }
}
