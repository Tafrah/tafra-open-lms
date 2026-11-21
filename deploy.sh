#!/usr/bin/env bash
#
# سكربت النشر (docs/14 بند ٣).
#
# الترتيب مش عشوائي:
#   تسجيل الـ SHA قبل السحب      — من غيره مفيش رجوع (rollback.sh)
#   migrate قبل authorization:sync — الجداول لازم تكون موجودة
#   tenancy:init بعد migrate      — بيقفل الوضع أول مرة، وبيوقف النشر لو .env اتغيّر
#   tenants:migrate (multi بس)    — قواعد المؤسسات مش جوه `migrate`
#   optimize:clear قبل بناء الكاش  — عشان مايبقاش فيه كاش قديم مخلوط
#   horizon:terminate بعد كل حاجة  — عشان العمال ياخدوا الكود الجديد
#   php artisan up آخر حاجة
set -euo pipefail

cd "$(dirname "$0")"
# shellcheck source=deploy/lib.sh
source deploy/lib.sh

log "وضع الصيانة"
fc::maintenance_down

# أي فشل بعد كده لازم يرفع الصيانة — وإلا الموقع بيفضل مقفول
trap 'warn "النشر فشل. الموقع رجع شغّال على حالة نص-منشورة — راجع اللوج، ولو محتاج ترجع: ./rollback.sh"; php artisan up || true' EXIT

# الإصدار الحالي بيتسجّل **قبل** السحب — ده اللي rollback.sh بيرجع له.
log "تسجيل الإصدار الحالي"
if CURRENT_SHA="$(git rev-parse HEAD 2>/dev/null)"; then
    printf '%s\n' "$CURRENT_SHA" > "$DEPLOY_STATE"
    printf '  %s\n' "$CURRENT_SHA"
else
    warn "مفيش كوميت سابق — الرجوع مش هيكون متاح بعد النشر ده."
fi

log "سحب الكود"
git pull --ff-only origin "${DEPLOY_BRANCH:-main}"

fc::dependencies

log "الميجريشنز"
php artisan migrate --force

# بعد `migrate` (جدول `system_state` لازم يكون موجود) وقبل أي حاجة بتلمس المؤسسات
fc::tenancy

log "مزامنة الصلاحيات"
php artisan authorization:sync

# مفاتيح توقيع توكنز OAuth (docs/23) — مرة واحدة بس: إعادة توليدها بتفصل
# كل تطبيقات الذكاء الاصطناعي المربوطة. لو المفتاح في `.env` مفيش ملفات أصلاً.
if [[ ! -f storage/oauth-private.key ]] && ! grep -q '^PASSPORT_PRIVATE_KEY=.\+' .env; then
    log "مفاتيح OAuth"
    php artisan passport:keys
fi

fc::rebuild_caches
fc::reload_opcache
fc::restart_workers

log "فحص سريع"
php artisan about --only=environment

trap - EXIT
php artisan up

log "✔ تم النشر"
