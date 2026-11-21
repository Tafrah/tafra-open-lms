<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Html;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * الحل الوحيد المقبول لمحتوى المحرّر: تنقية **عند الحفظ وعند العرض**.
 *
 * قائمة بيضاء ضيقة — مش سوداء. أي وسم مش هنا بيتشال (docs/20 بند ٥).
 */
final class HtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'blockquote', 'a', 'code', 'pre',
        'span', 'div', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'img',
    ];

    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'span' => ['dir'],
        'div' => ['dir'],
        'p' => ['dir'],
        'code' => ['class'],
        'pre' => ['class'],
    ];

    private readonly SymfonySanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http', 'data'])
            ->forceHttpsUrls(false)
            // كل رابط خارجي بياخد rel آمن — منع tabnabbing
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        foreach (self::ALLOWED_TAGS as $tag) {
            $config = $config->allowElement($tag, self::ALLOWED_ATTRIBUTES[$tag] ?? []);
        }

        // الوسوم دي بتتشال بمحتواها — مش بس بوسمها
        foreach (['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'base'] as $tag) {
            $config = $config->blockElement($tag)->dropElement($tag);
        }

        $this->sanitizer = new SymfonySanitizer($config);
    }

    public function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return $this->sanitizer->sanitize($html);
    }

    /** ينقّي مصفوفة مترجمة: ['ar' => '<p>..</p>', 'en' => '...'] */
    public function cleanTranslations(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->clean($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_map(fn (mixed $item): mixed => $this->cleanTranslations($item), $value);
    }
}
