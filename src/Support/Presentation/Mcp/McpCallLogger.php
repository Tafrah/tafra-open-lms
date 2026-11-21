<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Throwable;

/**
 * كل نداء أداة = صف في سجل النشاط تحت `mcp` (docs/23 بند ٥).
 *
 * مين (الفاعل)، أي تطبيق وأي ربط، أي أداة، المدخلات **بعد التنقية**،
 * والنتيجة. المؤسسة و`request_id` بيتحقنوا في `ActivityLog::booted()`.
 * فشل التسجيل مايوقعش النداء — بس بيتبلّغ.
 */
final class McpCallLogger
{
    public const SUCCEEDED = 'succeeded';

    public const DENIED = 'denied';

    public const FAILED = 'failed';

    private const MAX_VALUE_LENGTH = 200;

    public function __construct(private readonly McpSession $session) {}

    /** @param array<string, mixed> $arguments */
    public function log(string $tool, array $arguments, string $outcome, ?string $reason = null): void
    {
        try {
            $logger = activity('mcp')->event($outcome);

            if (($user = $this->session->user()) !== null) {
                $logger->causedBy($user);
            }

            $logger->withProperties(array_filter([
                'tool' => $tool,
                'outcome' => $outcome,
                'reason' => $reason,
                'client' => $this->session->clientName(),
                'connection_id' => $this->session->connectionId(),
                'arguments' => $this->redact($arguments),
            ], static fn (mixed $value): bool => $value !== null))->log($tool);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<array-key, mixed>
     */
    public function redact(array $values): array
    {
        $sensitive = array_map('strtolower', [
            ...(array) config('logging.redact', []),
            ...(array) config('ai.redact', []),
        ]);

        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitive, true)) {
                $values[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redact($value);
            } elseif (is_string($value) && mb_strlen($value) > self::MAX_VALUE_LENGTH) {
                $values[$key] = mb_substr($value, 0, self::MAX_VALUE_LENGTH).'…';
            }
        }

        return $values;
    }
}
