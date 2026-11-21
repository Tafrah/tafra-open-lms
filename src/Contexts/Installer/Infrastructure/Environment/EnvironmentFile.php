<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure\Environment;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * قراءة وكتابة `.env`.
 *
 * الكتابة ذرّية (ملف مؤقت ← `rename`) — طلب بيقرا الملف وهو بيتكتب
 * كان ممكن يشوف نصه ويقوم على قاعدة فاضية.
 *
 * ⚠️ الكلاس ده مابيعتمدش على الحاوية: `PreInstallEnvironment` بيستخدمه
 * قبل ما Laravel يحمّل الإعدادات.
 */
final class EnvironmentFile
{
    public function __construct(private readonly string $path) {}

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /** الملف موجود وقابل للكتابة، أو مش موجود والمجلد قابل للكتابة. */
    public function isWritable(): bool
    {
        return $this->exists() ? is_writable($this->path) : is_writable(dirname($this->path));
    }

    /** @return array<string, string|null> */
    public function read(): array
    {
        if (! $this->exists()) {
            return [];
        }

        return Dotenv::parse((string) file_get_contents($this->path));
    }

    /** @param array<string, array<string, string>> $sections */
    public function write(array $sections): void
    {
        $temporary = $this->path.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (@file_put_contents($temporary, self::render($sections), LOCK_EX) === false) {
            throw new RuntimeException($this->path);
        }

        if (! @rename($temporary, $this->path)) {
            @unlink($temporary);

            throw new RuntimeException($this->path);
        }
    }

    /** @param array<string, array<string, string>> $sections */
    public static function render(array $sections): string
    {
        $lines = [];

        foreach ($sections as $title => $values) {
            $lines[] = '# '.$title;

            foreach ($values as $key => $value) {
                $lines[] = $key.'='.self::format($value);
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * قيمة آمنة لـ phpdotenv. أي حاجة غير الحروف الآمنة بتتحط بين علامتين
     * مزدوجتين، و`\` و`"` و`$` بيتهرّبوا — كلمة سر فيها `$` أو `#` كانت
     * هتتقطع أو تتفسّر كمتغيّر.
     */
    public static function format(string $value): string
    {
        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw new RuntimeException('newline');
        }

        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+,=\-]+$/', $value) === 1) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }
}
