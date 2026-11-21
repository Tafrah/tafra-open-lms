<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

/**
 * بيانات الاتصال بالقاعدة اللي صاحب المنصة كتبها.
 *
 * كلمة السر `#[\SensitiveParameter]` ومش جوه `__debugInfo()` — أي تتبّع
 * أخطاء أو `dump` مايطلّعهاش (docs/20).
 */
final readonly class DatabaseCredentials
{
    public function __construct(
        public string $driver,
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        #[\SensitiveParameter]
        public string $password,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'db_driver' => ['required', 'string', 'in:'.implode(',', (array) config('installer.database.drivers'))],
            'db_host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-_:\[\]]+$/'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-$]+$/'],
            'db_username' => ['required', 'string', 'max:64'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $driver = (string) ($input['db_driver'] ?? 'mysql');

        return new self(
            driver: $driver,
            host: trim((string) ($input['db_host'] ?? '')),
            port: (int) ($input['db_port'] ?? config("installer.database.default_ports.{$driver}", 3306)),
            database: trim((string) ($input['db_database'] ?? '')),
            username: trim((string) ($input['db_username'] ?? '')),
            password: (string) ($input['db_password'] ?? ''),
        );
    }

    /**
     * إعداد اتصال Laravel كامل — بياخد باقي الخيارات (charset، collation…)
     * من الاتصال المعرّف في `config/database.php` لنفس الـ driver.
     *
     * @return array<string, mixed>
     */
    public function toConnectionConfig(): array
    {
        $base = (array) config("database.connections.{$this->driver}", []);

        return array_merge($base, [
            'driver' => $this->driver,
            'url' => null,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ]);
    }

    /** @return array<string, string> القيم اللي بتتكتب في `.env` */
    public function toEnvironment(): array
    {
        return [
            'DB_CONNECTION' => $this->driver,
            'DB_HOST' => $this->host,
            'DB_PORT' => (string) $this->port,
            'DB_DATABASE' => $this->database,
            'DB_USERNAME' => $this->username,
            'DB_PASSWORD' => $this->password,
        ];
    }

    /** @return array<string, string|int> كل حاجة ما عدا كلمة السر — للجلسة والعرض */
    public function withoutPassword(): array
    {
        return [
            'db_driver' => $this->driver,
            'db_host' => $this->host,
            'db_port' => $this->port,
            'db_database' => $this->database,
            'db_username' => $this->username,
        ];
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [...$this->withoutPassword(), 'db_password' => '********'];
    }
}
