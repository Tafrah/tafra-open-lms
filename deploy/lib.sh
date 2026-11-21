#!/usr/bin/env bash
#
# الخطوات المشتركة بين النشر (`deploy.sh`) والرجوع (`rollback.sh`).
#
# ليه ملف مشترك بدل ما كل سكربت يكرّر الخطوات؟
# لأن الرجوع لازم يعيد بناء **نفس** الحالة اللي النشر بيبنيها بالظبط.
# لو حد زوّد `icons:cache` في النشر ونسي الرجوع، الرجوع هيسيب كاش ناقص —
# وده أسوأ من مفيش رجوع أصلاً. الملف ده بيمنع الانحراف ده هيكلياً.

# اسم خدمة FPM مش مكتوب في الكود: بيختلف حسب التوزيعة
# (php8.4-fpm على Debian/Ubuntu · php-fpm على Alpine/RHEL).
FPM_SERVICE="${FPM_SERVICE:-php8.4-fpm}"

# فين بنسجّل الكوميت اللي كان شغّال قبل آخر نشر.
# `storage/app/.gitignore` بيتجاهل `*` فالملف ده مش بيتكوميت.
DEPLOY_STATE="${DEPLOY_STATE:-storage/app/.last-deploy-sha}"

log()  { printf '\n\033[1;36m→ %s\033[0m\n' "$1"; }
warn() { printf '\n\033[1;33m⚠ %s\033[0m\n' "$1" >&2; }

fc::maintenance_down() {
    if [ -n "${DEPLOY_SECRET:-}" ]; then
        php artisan down --retry=60 --secret="$DEPLOY_SECRET"
    else
        php artisan down --retry=60
    fi
}

# وضع تعدد المستأجرين من `.env` — نفس القيمة اللي التطبيق هيقرأها.
fc::tenancy_mode() {
    local mode
    mode="$(grep -E '^TENANCY_MODE=' .env 2>/dev/null | tail -n 1 | cut -d= -f2 | tr -d '"[:space:]' || true)"
    printf '%s' "${mode:-single}"
}

# أول نشر بيقفل الوضع؛ بعده الأمر بيقول «مقفول بالفعل». لو `.env` اتغيّر عن
# الوضع المقفول، التطبيق بيرفض يقوم والنشر بيقف هنا — قبل ما يشتغل على
# القاعدة الغلط (docs/03 بند ١). في multi جداول المؤسسات في قواعدها،
# و`migrate` العادي مابيوصلهاش.
fc::tenancy() {
    local mode
    mode="$(fc::tenancy_mode)"

    log "وضع تعدد المستأجرين ($mode)"
    php artisan tenancy:init --mode="$mode"

    if [ "$mode" = "multi" ]; then
        log "ميجريشنز قواعد المؤسسات"
        php artisan tenants:migrate --force
    fi
}

fc::dependencies() {
    log "الاعتماديات"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

    log "الأصول"
    npm ci
    npm run build
}

fc::rebuild_caches() {
    log "مسح الكاش القديم"
    php artisan optimize:clear

    log "بناء الكاش"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan filament:optimize
    php artisan icons:cache
}

# إعادة تحميل OPcache.
#
# ⚠️ الخطوة دي بالذات ماينفعش تفشل بصمت. `opcache.validate_timestamps=0`
# في الإنتاج (docs/14 بند ٥) معناه إن PHP **مش هيقرأ** الكود الجديد من على
# الديسك غير بعد إعادة تحميل FPM. لو الاسم غلط والفشل اتبلع، الموقع بيرجع
# شغّال وهو بيقدّم **الكود القديم على السكيما الجديدة** — أسوأ حالة ممكنة.
#
# ومع ذلك مابنعملش exit: الـ trap ساعتها هيرفع الصيانة على نص نشر.
# التحذير الصريح أأمن من الانهيار في النص.
fc::reload_opcache() {
    log "إعادة تحميل OPcache ($FPM_SERVICE)"

    if ! command -v systemctl >/dev/null 2>&1; then
        warn "مفيش systemctl — لو الاستضافة بحاويات، إعادة تشغيل الحاوية بتصفّر OPcache تلقائياً. غير كده صفّرها يدوياً."
        return 0
    fi

    if sudo systemctl reload "$FPM_SERVICE"; then
        return 0
    fi

    warn "فشل إعادة تحميل $FPM_SERVICE."
    warn "مع validate_timestamps=0 ده معناه إن الموقع هيقدّم الكود القديم على السكيما الجديدة."
    warn "صحّح الاسم بـ FPM_SERVICE=<الاسم> وأعد التحميل يدوياً قبل ما تسيب السيرفر."
}

fc::restart_workers() {
    log "إعادة تشغيل العمال"
    php artisan horizon:terminate || true
    php artisan queue:restart
}
