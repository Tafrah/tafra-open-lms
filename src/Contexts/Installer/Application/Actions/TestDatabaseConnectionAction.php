<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\Actions;

use Illuminate\Database\DatabaseManager;
use PDO;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;
use Src\Contexts\Installer\Domain\Exceptions\DatabaseConnectionFailed;
use Throwable;

/**
 * بيتصل بالقاعدة على اتصال مؤقت ويتأكد من ٣ حاجات: الاتصال نفسه، الإصدار
 * (JSON + utf8mb4)، وصلاحية إنشاء جدول — أشهر مشكلة على cPanel إن المستخدم
 * اتعمل ومااتضافش للقاعدة.
 *
 * ⚠️ الاستثناء الأصلي مابيتسجّلش: تتبّع PDO فيه معاملات الاتصال. اللي بيطلع
 * رسالة متنضّفة من كلمة السر بس.
 */
final readonly class TestDatabaseConnectionAction
{
    private const PROBE = 'installer_probe';

    public function __construct(private DatabaseManager $db) {}

    /** @return string إصدار السيرفر */
    public function handle(DatabaseCredentials $credentials): string
    {
        if (! extension_loaded('pdo_'.$credentials->driver)) {
            throw new DatabaseConnectionFailed('driver_missing');
        }

        $config = $credentials->toConnectionConfig();
        $config['options'] = [
            ...(array) ($config['options'] ?? []),
            PDO::ATTR_TIMEOUT => (int) config('installer.database.connect_timeout', 5),
        ];

        config(['database.connections.'.self::PROBE => $config]);

        try {
            $connection = $this->db->connection(self::PROBE);
            $version = (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

            $this->assertVersion($credentials->driver, $version);

            $table = 'installer_probe_'.bin2hex(random_bytes(3));
            $connection->getSchemaBuilder()->create($table, static function ($blueprint): void {
                $blueprint->id();
            });
            $connection->getSchemaBuilder()->drop($table);

            return $version;
        } catch (DatabaseConnectionFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new DatabaseConnectionFailed(
                self::reason($exception->getMessage()),
                self::sanitize($exception->getMessage(), $credentials->password),
            );
        } finally {
            $this->db->purge(self::PROBE);
            config(['database.connections.'.self::PROBE => null]);
        }
    }

    public static function reason(string $message): string
    {
        $patterns = [
            'no_database_access' => '/\b1044\b|access denied for user .* to database/i',
            'access_denied' => '/\b1045\b|access denied|28P01|password authentication failed/i',
            'unknown_database' => '/\b1049\b|unknown database|3D000|database ".*" does not exist/i',
            'no_create_privilege' => '/\b1142\b|command denied|42501|permission denied/i',
            'driver_missing' => '/could not find driver/i',
            'unreachable' => '/\b200[2356]\b|connection refused|getaddrinfo|name or service not known|timed out|no such host|08006|could not connect|php_network/i',
        ];

        foreach ($patterns as $reason => $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return $reason;
            }
        }

        return 'unknown';
    }

    public static function sanitize(string $message, string $password): string
    {
        $message = $password !== '' ? str_replace($password, '********', $message) : $message;

        // رسالة PDO الطويلة فيها الاستعلام — أول سطر كفاية للتشخيص
        return mb_substr(strtok($message, "\n") ?: $message, 0, 500);
    }

    private function assertVersion(string $driver, string $version): void
    {
        $minimums = (array) config('installer.database.min_versions');

        if ($driver === 'pgsql') {
            $flavour = 'pgsql';
            $number = $this->number($version);
        } elseif (stripos($version, 'mariadb') !== false) {
            $flavour = 'mariadb';
            // `5.5.5-10.6.12-MariaDB` — البادئة دي تمويه قديم للتوافق
            $number = preg_match('/(\d+\.\d+\.\d+)-MariaDB/i', $version, $found) === 1 ? $found[1] : $this->number($version);
        } else {
            $flavour = 'mysql';
            $number = $this->number($version);
        }

        $minimum = (string) ($minimums[$flavour] ?? '0');

        if (version_compare($number, $minimum, '<')) {
            throw new DatabaseConnectionFailed('version_too_old', $version);
        }
    }

    private function number(string $version): string
    {
        return preg_match('/\d+(\.\d+){0,2}/', $version, $found) === 1 ? $found[0] : '0';
    }
}
