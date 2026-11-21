<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/*
 * مفتاح إيقاف خادم MCP للنظام كله — من غير ديبلوي (docs/23 بند ٥).
 * فوقه `ai.enabled` في الكونفيج، وتحته مفتاح كل مؤسسة.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('security.mcp_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('security.mcp_enabled');
    }
};
