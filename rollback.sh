#!/usr/bin/env bash
#
# الرجوع لآخر إصدار (docs/14 بند ٦: «استرجاع سريع لآخر إصدار»).
#
# بيرجّع **الكود** للكوميت اللي كان شغّال قبل آخر `./deploy.sh`، وبيعيد بناء
# نفس الحالة اللي النشر بيبنيها بالظبط (نفس الدوال من deploy/lib.sh).
#
# ⚠️ قاعدة البيانات مابترجعش تلقائياً — وده مقصود.
#    الرجوع في الكود ≠ الرجوع في السكيما. لو النشر ضاف عمود واتملى بيانات،
#    `migrate:rollback` بيمسح البيانات دي نهائياً. عشان كده الرجوع في السكيما
#    وراء علم صريح، وبيتأكّد قبل ما ينفّذ:
#
#      ./rollback.sh                      # الكود بس (الافتراضي والآمن)
#      ./rollback.sh --with-migrations    # الكود + آخر دفعة ميجريشنز
#
#    كل الميجريشنز عندها `down()` شغّالة ومحمية باختبار (docs/21 بند ٢١)،
#    فالرجوع في السكيما بيشتغل — لكن قرار تنفيذه بتاعك مش بتاع السكربت.
set -euo pipefail

cd "$(dirname "$0")"
# shellcheck source=deploy/lib.sh
source deploy/lib.sh

WITH_MIGRATIONS=false
for arg in "$@"; do
    case "$arg" in
        --with-migrations) WITH_MIGRATIONS=true ;;
        -h|--help)
            awk 'NR>1 && /^#/ { sub(/^# ?/, ""); print; next } NR>1 { exit }' "$0"
            exit 0
            ;;
        *) warn "خيار مش معروف: $arg"; exit 1 ;;
    esac
done

if [ ! -f "$DEPLOY_STATE" ]; then
    warn "مفيش إصدار سابق مسجّل في $DEPLOY_STATE."
    warn 'الملف بيتكتب في أول ./deploy.sh — يعني لازم يكون فيه نشرة واحدة على الأقل قبل الرجوع.'
    exit 1
fi

TARGET_SHA="$(tr -d '[:space:]' < "$DEPLOY_STATE")"

if ! git cat-file -e "${TARGET_SHA}^{commit}" 2>/dev/null; then
    warn "الكوميت المسجّل ($TARGET_SHA) مش موجود في المستودع."
    warn "لو الفرع اتعمله force-push، اجلب الأوبجكت الأول: git fetch origin $TARGET_SHA"
    exit 1
fi

CURRENT_SHA="$(git rev-parse HEAD)"

printf '\n\033[1;33mالرجوع من %s إلى %s\033[0m\n' "${CURRENT_SHA:0:12}" "${TARGET_SHA:0:12}"
git --no-pager log --oneline "${TARGET_SHA}..${CURRENT_SHA}" 2>/dev/null | sed 's/^/  ↩ /' || true

if [ "$WITH_MIGRATIONS" = true ]; then
    printf '\n\033[1;31m⚠ هيتنفّذ migrate:rollback كمان — البيانات في الأعمدة الجديدة هتضيع.\033[0m\n'
    printf '\033[1;31m  و migrate:rollback بيرجّع آخر دفعة، مش بالضرورة بتاعة النشرة دي.\033[0m\n'
    printf '\033[1;31m  لو النشرة اللي بترجعها ماكانش فيها ميجريشنز، الدفعة الأخيرة أقدم منها —\033[0m\n'
    printf '\033[1;31m  راجع جدول migrations قبل ما تكمّل.\033[0m\n'
fi

if [ -t 0 ] && [ "${ROLLBACK_ASSUME_YES:-}" != "1" ]; then
    read -r -p "أكمّل؟ [y/N] " answer
    case "$answer" in [yY]*) ;; *) echo "اتلغى."; exit 1 ;; esac
fi

log "وضع الصيانة"
fc::maintenance_down

trap 'warn "الرجوع فشل في النص — الموقع رجع شغّال لكن حالته مش مضمونة. راجع اللوج فوراً."; php artisan up || true' EXIT

# السكيما بترجع **قبل** الكود: الميجريشنز اللي بترجع لسه موجودة
# في شجرة الملفات الحالية. لو رجّعنا الكود الأول، `migrate:rollback`
# مش هيلاقي الملفات اللي المفروض يشغّل `down()` بتاعها.
if [ "$WITH_MIGRATIONS" = true ]; then
    # قواعد المؤسسات الأول (multi) — ميجريشنزها تابعة لنفس الإصدار
    if [ "$(fc::tenancy_mode)" = "multi" ]; then
        log "الرجوع في سكيما قواعد المؤسسات"
        php artisan tenants:rollback --force
    fi

    log "الرجوع في السكيما"
    php artisan migrate:rollback --force
fi

log "الرجوع في الكود"
git reset --hard "$TARGET_SHA"

fc::dependencies
fc::rebuild_caches
fc::reload_opcache
fc::restart_workers

# الإصدار اللي رجعنا منه بيتسجّل — عشان تقدر ترجع للأمام تاني لو الرجوع كان غلط.
printf '%s\n' "$CURRENT_SHA" > "$DEPLOY_STATE"

log "فحص سريع"
php artisan about --only=environment

trap - EXIT
php artisan up

log "✔ تم الرجوع إلى ${TARGET_SHA:0:12} — و $DEPLOY_STATE بقى فيه ${CURRENT_SHA:0:12} لو حبيت ترجع للأمام"
