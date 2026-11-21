<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * بيضيف السياق المشترك لكل سطر، **وبينقّي** الحقول الحساسة قبل ما تتكتب.
 * ممنوع في اللوج: كلمات مرور، توكنز، أسرار (docs/20 بند ١٣).
 */
final class ContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            extra: [
                ...$record->extra,
                'env' => (string) config('app.env'),
                'version' => (string) config('app.version'),
            ],
            context: $this->redact($record->context),
        );
    }

    /**
     * @param array<mixed> $context
     * @return array<mixed>
     */
    private function redact(array $context): array
    {
        $redacted = (array) config('logging.redact', []);

        foreach ($context as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $redacted, true)) {
                $context[$key] = '[محذوف]';

                continue;
            }

            if (is_array($value)) {
                $context[$key] = $this->redact($value);
            }
        }

        return $context;
    }
}
