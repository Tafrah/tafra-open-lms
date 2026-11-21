<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * الصلاحيات مصدرها `config/authorization.php` بس —
 * مفيش `Permission::create()` في أي سيدر (docs/02 بند ١٠).
 */
final class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('authorization:sync');

        $this->command->getOutput()->write(Artisan::output());
    }
}
