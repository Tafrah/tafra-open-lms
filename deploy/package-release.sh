#!/usr/bin/env bash
#
# حزمة الاستضافة المشتركة: zip فيه vendor (من غير dev) + public/build + .htaccess
# في الجذر، من غير .env ولا اختبارات ولا node_modules.
#
#   deploy/package-release.sh 1.2.3      ← dist/tafra-open-lms-v1.2.3.zip
#
# لازم يتشغّل بعد `composer install --no-dev` و`npm run build`. نفس السكربت
# بيستخدمه الـ Release والـ CI (اللي بيثبّت الحزمة فعلاً ويتأكد إنها شغالة).
set -euo pipefail
VERSION="${1:?usage: deploy/package-release.sh <version>}"
VERSION="${VERSION#v}"
NAME="tafra-open-lms-v${VERSION}"
STAGE="dist/${NAME}"
mkdir -p "$STAGE"

# rsync بيتبع الـ symlinks (-L): vendor/future-code/filament-onboarding
# رابط لـ packages/ — الاستضافة لازم تاخد ملفات حقيقية
rsync -aL \
  --exclude='/.git/' \
  --exclude='/.github/' \
  --exclude='/node_modules/' \
  --exclude='/tests/' \
  --exclude='/docs/' \
  --exclude='/dist/' \
  --exclude='/docker/' \
  --exclude='/deploy/' \
  --exclude='/src/Contexts/*/Tests/' \
  --exclude='/packages/*/*/tests/' \
  --exclude='/.env' \
  --exclude='/.env.backup' \
  --exclude='/.env.production' \
  --exclude='/.env.docker' \
  --exclude='/phpunit.xml' \
  --exclude='/phpstan.neon' \
  --exclude='/pint.json' \
  --exclude='/Caddyfile' \
  --exclude='/docker-compose*.yml' \
  --exclude='/.dockerignore' \
  --exclude='/.gitleaks.toml' \
  --exclude='/.editorconfig' \
  --exclude='/deploy.sh' \
  --exclude='/rollback.sh' \
  --exclude='/install.sh' \
  --exclude='/public/hot' \
  --exclude='/public/storage' \
  --exclude='/storage/logs/*.log' \
  --exclude='/storage/app/installed.json' \
  --exclude='/storage/app/latest-release.json' \
  --exclude='/storage/framework/cache/data/*' \
  --exclude='/storage/framework/sessions/*' \
  --exclude='/storage/framework/views/*.php' \
  --exclude='/bootstrap/cache/config.php' \
  --exclude='/bootstrap/cache/routes-*.php' \
  --exclude='/bootstrap/cache/events.php' \
  ./ "$STAGE/"

# الطريقة التانية (public_html منفصل) — index.php جاهز جوه الحزمة
mkdir -p "$STAGE/deploy/shared-hosting"
cp deploy/shared-hosting/index.php "$STAGE/deploy/shared-hosting/index.php"
cp docs/install/shared-hosting.md "$STAGE/INSTALL-shared-hosting.md"

echo "v${VERSION}" > "$STAGE/VERSION"

# مفيش .env ولا أسرار جوه الحزمة — آخر خط دفاع
test ! -e "$STAGE/.env"
test -f "$STAGE/public/build/manifest.json"
test -f "$STAGE/vendor/autoload.php"
test -f "$STAGE/.htaccess"

(cd "$STAGE" && zip -qr -X "../${NAME}.zip" .)
if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    echo "zip=dist/${NAME}.zip" >> "$GITHUB_OUTPUT"
fi
ls -lh "dist/${NAME}.zip"
