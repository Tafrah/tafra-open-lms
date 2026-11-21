<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\Actions;

use Src\Contexts\Installer\Application\DataObjects\RequirementCheck;
use Src\Contexts\Installer\Application\DataObjects\RequirementsReport;
use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentFile;
use Src\Contexts\Installer\Infrastructure\StorageLinker;

/**
 * الخطوة الأولى: هل الاستضافة دي تقدر تشغّل المنصة؟
 *
 * البنود «المانعة» بتوقف التثبيت. الباقي تحذيرات: المنصة بتشتغل من غيرها
 * بس في حاجة هتبقى أبطأ أو محتاجة بديل (الملفات من غير symlink مثلاً).
 */
final readonly class CheckRequirementsAction
{
    public function __construct(private EnvironmentFile $environment) {}

    public function handle(bool $checkEnvironmentFile = true): RequirementsReport
    {
        return new RequirementsReport([
            ...$this->php(),
            ...$this->extensions(),
            ...$this->permissions($checkEnvironmentFile),
            ...$this->recommendations(),
        ]);
    }

    /** @return list<RequirementCheck> */
    private function php(): array
    {
        $minimum = (string) config('installer.requirements.php');

        return [new RequirementCheck(
            group: 'php',
            label: __('installer::installer.requirements.php_version', ['version' => $minimum]),
            passed: version_compare(PHP_VERSION, $minimum, '>='),
            current: PHP_VERSION,
            hint: __('installer::installer.requirements.hints.php_version'),
        )];
    }

    /** @return list<RequirementCheck> */
    private function extensions(): array
    {
        $checks = [];

        foreach ((array) config('installer.requirements.extensions') as $extension) {
            $checks[] = new RequirementCheck(
                group: 'extensions',
                label: (string) $extension,
                passed: extension_loaded((string) $extension),
                hint: __('installer::installer.requirements.hints.extension'),
            );
        }

        foreach ((array) config('installer.requirements.one_of') as $group => $options) {
            $options = array_map('strval', (array) $options);
            $loaded = array_values(array_filter($options, 'extension_loaded'));

            $checks[] = new RequirementCheck(
                group: 'extensions',
                label: implode(' / ', $options),
                passed: $loaded !== [],
                current: implode(', ', $loaded),
                hint: __('installer::installer.requirements.hints.one_of.'.$group),
            );
        }

        return $checks;
    }

    /** @return list<RequirementCheck> */
    private function permissions(bool $checkEnvironmentFile): array
    {
        $checks = [];

        foreach ((array) config('installer.requirements.writable') as $directory) {
            $path = base_path((string) $directory);

            $checks[] = new RequirementCheck(
                group: 'permissions',
                label: (string) $directory,
                passed: is_dir($path) && is_writable($path),
                hint: __('installer::installer.requirements.hints.writable'),
            );
        }

        if ($checkEnvironmentFile) {
            $checks[] = new RequirementCheck(
                group: 'permissions',
                label: '.env',
                passed: $this->environment->isWritable(),
                hint: __('installer::installer.requirements.hints.env'),
            );
        }

        return $checks;
    }

    /** @return list<RequirementCheck> */
    private function recommendations(): array
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return [
            new RequirementCheck(
                group: 'recommended',
                label: 'symlink()',
                passed: StorageLinker::symlinksAvailable(),
                blocking: false,
                hint: __('installer::installer.requirements.hints.symlink'),
            ),
            new RequirementCheck(
                group: 'recommended',
                label: 'proc_open()',
                passed: function_exists('proc_open') && ! in_array('proc_open', $disabled, true),
                blocking: false,
                hint: __('installer::installer.requirements.hints.proc_open'),
            ),
            new RequirementCheck(
                group: 'recommended',
                label: __('installer::installer.requirements.memory', ['size' => '128M']),
                passed: $this->memoryLimitAtLeast(128 * 1024 * 1024),
                blocking: false,
                current: (string) ini_get('memory_limit'),
                hint: __('installer::installer.requirements.hints.memory'),
            ),
        ];
    }

    private function memoryLimitAtLeast(int $bytes): bool
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return true;
        }

        $value = (int) $limit;

        $value *= match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return $value >= $bytes;
    }
}
