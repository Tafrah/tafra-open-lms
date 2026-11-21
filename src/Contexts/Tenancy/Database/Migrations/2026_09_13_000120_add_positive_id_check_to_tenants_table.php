<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * الفريق `0` في spatie محجوز للأدوار المركزية (docs/24 بند ٢).
 *
 * مؤسسة برقم `0` كانت هتورث أدوار فريق التشغيل كلها — فالقيد ده
 * بيخلّي التصادم مستحيل على مستوى القاعدة، مش مجرد «الـ serial بيبدأ من ١».
 *
 * MySQL/MariaDB مابيقبلوش CHECK على عمود auto-increment، وكمان إدخال `0`
 * فيه بيولّد الرقم اللي بعده (إلا لو NO_AUTO_VALUE_ON_ZERO) — فالقيد هناك مش لازم.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsCheck()) {
            return;
        }

        DB::statement('ALTER TABLE tenants ADD CONSTRAINT tenants_id_positive CHECK (id > 0)');
    }

    public function down(): void
    {
        if (! $this->supportsCheck()) {
            return;
        }

        DB::statement('ALTER TABLE tenants DROP CONSTRAINT IF EXISTS tenants_id_positive');
    }

    private function supportsCheck(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
};
