<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentBlueprint;
use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentFile;

it('أي قيمة بترجع زي ما هي بعد الكتابة والقراءة', function (string $value): void {
    $path = sys_get_temp_dir().'/lms-env-'.bin2hex(random_bytes(4));
    $file = new EnvironmentFile($path);

    $file->write(['Test' => ['VALUE' => $value, 'AFTER' => 'ok']]);

    expect(Dotenv::parse((string) file_get_contents($path)))->toBe(['VALUE' => $value, 'AFTER' => 'ok']);

    unlink($path);
})->with([
    'simple',
    '',
    'p@ss w0rd',
    'with#hash',
    'dollar$sign${NOT_A_VAR}',
    'quote"and\'single',
    'back\\slash',
    'أكاديمية النجاح',
    'base64:'.base64_encode(random_bytes(32)),
]);

it('سطر جديد في القيمة مرفوض — كان هيكسر الملف', function (): void {
    expect(fn () => EnvironmentFile::format("a\nb"))->toThrow(RuntimeException::class);
});

it('.env قبل التثبيت: مفتاح صالح، وجلسات وكاش على ملفات (مفيش قاعدة لسه)', function (): void {
    $values = array_merge(...array_values(EnvironmentBlueprint::preInstall(EnvironmentBlueprint::generateKey())));

    expect($values)->toMatchArray([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'SESSION_DRIVER' => 'file',
        'CACHE_STORE' => 'file',
        'QUEUE_CONNECTION' => 'sync',
        'TENANCY_MODE' => 'single',
    ])->and($values['APP_KEY'])->toStartWith('base64:');
});

it('قابلية الكتابة: ملف موجود أو مجلده', function (): void {
    $dir = sys_get_temp_dir().'/lms-env-dir-'.bin2hex(random_bytes(4));
    mkdir($dir);

    expect((new EnvironmentFile($dir.'/.env'))->isWritable())->toBeTrue();

    chmod($dir, 0555);
    expect((new EnvironmentFile($dir.'/.env'))->isWritable())->toBeFalse();
    chmod($dir, 0755);
    rmdir($dir);
});
