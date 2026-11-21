<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Domain\Enums;

/**
 * إزاي ملفات الديسك العام بتوصل للمتصفح.
 *
 * - `symlink`: `public/storage` رابط رمزي — السيرفر بيقدّم الملفات مباشرة (الأسرع).
 * - `route`: الاستضافة قافلة `symlink()` — التطبيق بيقدّمها من `/uploads/...`
 *   (`ServePublicFileController`) و`PUBLIC_STORAGE_URL` بيشاور عليه.
 */
enum MediaServing: string
{
    case Symlink = 'symlink';
    case Route = 'route';
}
