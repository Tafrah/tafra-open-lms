<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Authorization;

use Closure;

/**
 * ذاكرة قصيرة للفحوصات المتكررة داخل نفس الطلب.
 *
 * ليه؟ الـ Policy بتتنفّذ **لكل صف** في الجدول (كل إجراء بيسأل).
 * أي استعلام جوه قاعدة أعمال بيتضرب × عدد الصفوف — ٢٥ صف × ٣ إجراءات
 * = ٧٥ استعلام على صفحة واحدة. الفحوصات اللي إجابتها واحدة للصفحة
 * كلها (فيه مدير تاني؟ كام إعلان مثبّت؟) بتتحسب مرة وخلاص.
 *
 * الذاكرة دي **لكل طلب** — بتتولد جديدة مع كل request، فمفيش خطر
 * إنها تحمل إجابة بايتة لطلب تاني.
 */
final class PolicyMemo
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function remember(string $key, Closure $resolve): mixed
    {
        return array_key_exists($key, $this->values)
            ? $this->values[$key]
            : $this->values[$key] = $resolve();
    }

    public function forget(?string $key = null): void
    {
        if ($key === null) {
            $this->values = [];

            return;
        }

        unset($this->values[$key]);
    }
}
