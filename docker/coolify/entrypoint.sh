#!/bin/sh
#
# أول تشغيل: تثبيت من غير واجهة (lms:install). كل تشغيل: migrate + الصلاحيات
# + الكاش. القيم كلها من متغيّرات البيئة (Coolify / docker-compose) — مفيش .env.
set -eu

cd /var/www/html

as_app() {
    su-exec www-data "$@"
}

# الـ volume ممكن يبقى فاضي أول مرة
mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# APP_KEY: لو مش متبعت، بيتولّد مرة واحدة ويتحفظ في الـ volume —
# تغييره بعد كده بيفصل كل الجلسات ويبوّظ أي قيمة متشفّرة
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app/.app-key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/.app-key
        chown www-data:www-data storage/app/.app-key
        chmod 600 storage/app/.app-key
    fi
    APP_KEY="$(cat storage/app/.app-key)"
    export APP_KEY
fi

# Coolify ممكن يدّي الدومين من غير بروتوكول
case "${APP_URL:-}" in
    http://* | https://* | "") ;;
    *) APP_URL="https://${APP_URL}"; export APP_URL ;;
esac

echo "[lms] waiting for the database…"
tries=0
# shellcheck disable=SC2016 # كود PHP — الـ $ بتاعه مش متغيّرات shell
until php -r '
    $driver = getenv("DB_CONNECTION") ?: "mysql";
    $dsn = sprintf("%s:host=%s;port=%s;dbname=%s", $driver, getenv("DB_HOST"), getenv("DB_PORT") ?: ($driver === "pgsql" ? 5432 : 3306), getenv("DB_DATABASE"));
    try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]); exit(0); } catch (Throwable) { exit(1); }
'; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "[lms] database not reachable after 120s" >&2
        exit 1
    fi
    sleep 2
done

# كاش الملفات بس — `optimize:clear` بيمسح كاش القاعدة كمان، وجدول `cache`
# مش موجود قبل أول migrate ← الحاوية كانت بتقع وتعيد التشغيل للأبد
as_app php artisan config:clear >/dev/null
as_app php artisan route:clear >/dev/null
as_app php artisan view:clear >/dev/null
as_app php artisan event:clear >/dev/null

if [ "${DEMO_MODE:-false}" = "true" ]; then
    # نسخة تجربة: تثبيت + بيانات + دخول بضغطة (docs/install/demo.md)
    as_app php artisan lms:demo --if-not-installed
elif [ -n "${LMS_ADMIN_EMAIL:-}" ]; then
    as_app php artisan lms:install --no-interaction --skip-env --if-not-installed \
        --url="${APP_URL:-http://localhost}" \
        --platform-name="${LMS_PLATFORM_NAME:-Tafra LMS}" \
        --teacher-name="${LMS_TEACHER_NAME:-Teacher}" \
        --subject="${LMS_SUBJECT:--}" \
        --admin-email="${LMS_ADMIN_EMAIL}" \
        --admin-phone="${LMS_ADMIN_PHONE:-}" \
        --admin-password="${LMS_ADMIN_PASSWORD:-}"
else
    echo "[lms] LMS_ADMIN_EMAIL is not set — open /install in the browser to finish." >&2
fi

as_app php artisan migrate --force
as_app php artisan authorization:sync
as_app php artisan storage:link >/dev/null 2>&1 || true

# مفاتيح OAuth (MCP) — مرة واحدة، في الـ volume
if [ ! -f storage/oauth-private.key ] && [ -z "${PASSPORT_PRIVATE_KEY:-}" ]; then
    as_app php artisan passport:keys >/dev/null 2>&1 || true
fi

as_app php artisan config:cache
as_app php artisan route:cache
as_app php artisan view:cache
as_app php artisan event:cache
as_app php artisan filament:optimize >/dev/null 2>&1 || true

exec "$@"
