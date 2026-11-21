<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ═══════════ عام ═══════════
        $this->migrator->add('general.app_name', [
            'ar' => 'منصتي التعليمية',
            'en' => 'My learning platform',
        ]);
        $this->migrator->add('general.app_description', [
            'ar' => 'لوحة تحكم إدارية متعددة المؤسسات',
            'en' => 'Multi-tenant administration panel',
        ]);
        $this->migrator->add('general.support_email', 'support@futurecode.test');
        $this->migrator->add('general.support_phone', '+20 100 000 0000');
        $this->migrator->add('general.default_locale', 'ar');
        $this->migrator->add('general.timezone', 'Africa/Cairo');
        $this->migrator->add('general.maintenance_mode', false);
        $this->migrator->add('general.maintenance_message', [
            'ar' => 'النظام في وضع الصيانة دلوقتي. هنرجع قريب.',
            'en' => 'The system is under maintenance. We will be back shortly.',
        ]);

        // ═══════════ المظهر ═══════════
        $this->migrator->add('appearance.primary_color', '#419DF1');
        $this->migrator->add('appearance.logo_light_path', null);
        $this->migrator->add('appearance.logo_dark_path', null);
        $this->migrator->add('appearance.favicon_path', null);
        $this->migrator->add('appearance.default_theme', 'system');
        $this->migrator->add('appearance.allow_theme_switch', true);
        $this->migrator->add('appearance.sidebar_default', 'expanded');
        $this->migrator->add('appearance.footer_text', [
            'ar' => '© ٢٠٢٦ — جميع الحقوق محفوظة',
            'en' => '© 2026 — All rights reserved',
        ]);
        $this->migrator->add('appearance.font_family', 'Almarai');

        // ═══════════ التخزين ═══════════
        $this->migrator->add('storage.default_disk', 'public');
        $this->migrator->add('storage.private_disk', 'local');
        $this->migrator->add('storage.conversions_disk', 'public');
        $this->migrator->add('storage.private_collections', ['documents', 'contracts', 'identity_papers', 'receipt', 'answer_images']);
        $this->migrator->add('storage.collection_disks', []);
        $this->migrator->add('storage.max_upload_size_kb', 10240);
        $this->migrator->add('storage.allowed_mimes', [
            'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
        ]);

        // ═══════════ البريد ═══════════
        $this->migrator->add('mail.driver', 'log');
        $this->migrator->add('mail.host', '127.0.0.1');
        $this->migrator->add('mail.port', 2525);
        $this->migrator->add('mail.username', '');
        $this->migrator->addEncrypted('mail.password', '');
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.from_address', 'admin@futurecode.test');
        $this->migrator->add('mail.from_name', [
            'ar' => 'منصتي التعليمية',
            'en' => 'My learning platform',
        ]);

        // ═══════════ الإشعارات ═══════════
        $this->migrator->add('notifications.database_enabled', true);
        $this->migrator->add('notifications.mail_enabled', true);
        $this->migrator->add('notifications.broadcast_enabled', true);
        $this->migrator->add('notifications.prune_read_after_days', 90);
        $this->migrator->add('notifications.digest_hour', 8);

        // ═══════════ الأمان ═══════════
        $this->migrator->add('security.password_min_length', 12);
        $this->migrator->add('security.password_requires_uncompromised', true);
        $this->migrator->add('security.password_history_size', 5);
        $this->migrator->add('security.password_expiry_days', 0);
        $this->migrator->add('security.two_factor_required_roles', ['super_admin', 'admin']);
        $this->migrator->add('security.two_factor_grace_period_days', 7);
        $this->migrator->add('security.session_lifetime_minutes', 120);
        $this->migrator->add('security.impersonation_max_minutes', 30);
    }
};
