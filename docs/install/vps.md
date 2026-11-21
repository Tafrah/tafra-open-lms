# التثبيت على VPS (Ubuntu / Debian)

## الطريقة السريعة — أمر واحد (Docker)

قبل ما تبدأ: وجّه الدومين (A record) لعنوان السيرفر، وافتح البورتات **80** و**443**.

```bash
curl -fsSL https://raw.githubusercontent.com/Tafrah/tafra-open-lms/main/install.sh | sudo bash
```

السكربت (`install.sh`) بيسألك عن الدومين وبريد المدير واسم المنصة، وبعدين:

1. بيثبّت Docker لو مش موجود (`get.docker.com`).
2. بينزّل المشروع في `/opt/tafra-lms`.
3. بيولّد كلمات السر و`APP_KEY` في `/opt/tafra-lms/.env.docker` (مرة واحدة — إعادة التشغيل بتحتفظ بيها).
4. بيشغّل `docker-compose.coolify.yml` + `docker-compose.vps.yml`: MySQL 8، المنصة (nginx + php-fpm + الجدولة + عامل طابور)، وCaddy بشهادة Let's Encrypt تلقائية.
5. أول تشغيل للحاوية بيعمل التثبيت من غير واجهة (`php artisan lms:install --skip-env`)، وكل تشغيل بعده بيعمل `migrate` و`authorization:sync` والكاش.

في الآخر بيطبع رابط لوحة التحكم وكلمة سر المدير.

من غير أسئلة (سكربت / CI):

```bash
curl -fsSL https://raw.githubusercontent.com/Tafrah/tafra-open-lms/main/install.sh \
  | sudo LMS_DOMAIN=academy.example.com LMS_ADMIN_EMAIL=teacher@example.com \
         LMS_PLATFORM_NAME="أكاديمية النجاح" LMS_TEACHER_NAME="أ. محمد" LMS_SUBJECT="فيزياء" bash
```

**التحديث:** شغّل نفس الأمر تاني — بيسحب آخر نسخة ويعيد البناء. البيانات في volumes (`lms-storage`، `lms-mysql`).

**اللوجز:**
```bash
cd /opt/tafra-lms
docker compose --env-file .env.docker -f docker-compose.coolify.yml -f docker-compose.vps.yml logs -f app
```

## الطريقة اليدوية — PHP-FPM + nginx على السيرفر

لو عندك سيرفر متجهّز (PHP 8.4، MySQL أو PostgreSQL، nginx/Caddy):

```bash
git clone https://github.com/Tafrah/tafra-open-lms.git /var/www/lms && cd /var/www/lms
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan lms:install --no-interaction \
  --db-host=127.0.0.1 --db-database=lms --db-username=lms --db-password='...' \
  --url=https://academy.example.com \
  --platform-name="أكاديمية النجاح" --teacher-name="أ. محمد" --subject="فيزياء" \
  --admin-email=teacher@example.com --admin-password='...'
```

الأمر بيعمل نفس خطوات `/install`: يختبر القاعدة، يكتب `.env`، الجداول، الصلاحيات، المنصة والمدير (`admin` في المنصة + `super_admin` مركزي)، `storage:link`، يقفل وضع `single`، ويكتب `storage/app/installed.json`. من غير `--no-interaction` بيسألك عن أي حاجة ناقصة (وكلمات السر مابتظهرش وإنت بتكتبها).

- خلّي جذر nginx على `public/` (مثال في `docker/coolify/nginx.conf`).
- **الطابور والجدولة:** أبسط حاجة سطر Cron واحد:
  ```
  * * * * * php /var/www/lms/artisan schedule:run >> /dev/null 2>&1
  ```
  الجدولة فيها عامل طابور (`queue:work --stop-when-empty --max-time=50`) كل دقيقة — كفاية لمدرّس واحد.
- **لو عايز Redis + Horizon** (حمل أعلى): `CACHE_STORE=redis`، `QUEUE_CONNECTION=redis`، `QUEUE_WORKER_VIA_SCHEDULER=false`، وشغّل `php artisan horizon` تحت supervisor (`deploy/supervisor/`). عامل الجدولة بيقف لوحده لما الطابور يبقى Redis.
- النشر بعد كده: `./deploy.sh` (migrate، الصلاحيات، الكاش، إعادة تحميل FPM).

## خيارات `lms:install`

| الخيار | المعنى |
|---|---|
| `--db-driver` | `mysql` (افتراضي) أو `pgsql` |
| `--db-host` `--db-port` `--db-database` `--db-username` `--db-password` | القاعدة |
| `--skip-env` | القيم من متغيّرات البيئة (Docker) — مايكتبش `.env` ومايلمسش الاتصال |
| `--url` | `APP_URL` |
| `--platform-name` `--teacher-name` `--subject` | المنصة |
| `--admin-email` `--admin-phone` `--admin-password` | حساب المدير (كلمة السر ١٢ حرف على الأقل في الوضع ده) |
| `--if-not-installed` | يخرج بنجاح لو متثبّت (للـ entrypoint) — من غيره بيفشل |
