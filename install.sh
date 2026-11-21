#!/usr/bin/env bash
#
# Tafra Open LMS — تثبيت بأمر واحد على VPS (Ubuntu / Debian).
#
#   curl -fsSL https://raw.githubusercontent.com/Tafrah/tafra-open-lms/main/install.sh | sudo bash
#
# من غير أسئلة (مثلاً من سكربت):
#   curl -fsSL .../install.sh | sudo LMS_DOMAIN=academy.example.com \
#       LMS_ADMIN_EMAIL=teacher@example.com bash
#
# بيعمل: Docker لو مش موجود ← ينزّل المشروع في /opt/tafra-lms ← يولّد كلمات
# السر ← يشغّل MySQL + المنصة + Caddy (HTTPS تلقائي). الدومين لازم يكون
# متوجّه (A record) لعنوان السيرفر قبل التشغيل عشان الشهادة تطلع.
set -euo pipefail

REPO="${LMS_REPO:-https://github.com/Tafrah/tafra-open-lms.git}"
BRANCH="${LMS_BRANCH:-main}"
DIR="${LMS_DIR:-/opt/tafra-lms}"

say() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }
die() { printf '\n\033[1;31m✗\033[0m %s\n' "$*" >&2; exit 1; }

ask() {
    # ask VAR "question" [default] — بيقرا من الطرفية حتى مع curl | bash
    local var="$1" question="$2" default="${3:-}" answer=""
    if [[ -n "${!var:-}" ]]; then
        return
    fi
    if [[ -r /dev/tty ]]; then
        read -r -p "$question${default:+ [$default]}: " answer < /dev/tty || true
    fi
    answer="${answer:-$default}"
    [[ -n "$answer" ]] || die "$var is required"
    printf -v "$var" '%s' "$answer"
}

secret() {
    openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "${1:-32}"
}

[[ "$(id -u)" -eq 0 ]] || die "Run as root (sudo)."

ask LMS_DOMAIN "Domain (e.g. academy.example.com)"
ask LMS_ADMIN_EMAIL "Admin email"
ask LMS_PLATFORM_NAME "Platform name" "Tafra LMS"
ask LMS_TEACHER_NAME "Teacher name" "Teacher"
ask LMS_SUBJECT "Subject" "-"

if ! command -v docker >/dev/null 2>&1; then
    say "Installing Docker"
    curl -fsSL https://get.docker.com | sh
fi
docker compose version >/dev/null 2>&1 || die "docker compose plugin is missing"

if ! command -v git >/dev/null 2>&1; then
    say "Installing git"
    if command -v apt-get >/dev/null 2>&1; then
        apt-get update -qq && apt-get install -y -qq git openssl
    elif command -v dnf >/dev/null 2>&1; then
        dnf install -y -q git openssl
    else
        die "Install git first"
    fi
fi

if [[ -d "$DIR/.git" ]]; then
    say "Updating $DIR"
    git -C "$DIR" fetch --depth 1 origin "$BRANCH"
    git -C "$DIR" reset --hard "origin/$BRANCH"
else
    say "Downloading to $DIR"
    git clone --depth 1 --branch "$BRANCH" "$REPO" "$DIR"
fi

cd "$DIR"

# كلمات السر بتتولّد مرة واحدة — إعادة تشغيل السكربت بتحتفظ بيها
if [[ ! -f .env.docker ]]; then
    say "Generating secrets"
    umask 077
    {
        echo "LMS_DOMAIN=$LMS_DOMAIN"
        echo "LMS_ADMIN_EMAIL=$LMS_ADMIN_EMAIL"
        echo "LMS_PLATFORM_NAME=\"$LMS_PLATFORM_NAME\""
        echo "LMS_TEACHER_NAME=\"$LMS_TEACHER_NAME\""
        echo "LMS_SUBJECT=\"$LMS_SUBJECT\""
        echo "SERVICE_USER_DB=lms"
        echo "SERVICE_PASSWORD_DB=$(secret 32)"
        echo "SERVICE_PASSWORD_ROOT=$(secret 32)"
        echo "SERVICE_PASSWORD_ADMIN=$(secret 20)"
        echo "APP_KEY=base64:$(openssl rand -base64 32)"
    } > .env.docker
fi

say "Starting (first build takes a few minutes)"
docker compose --env-file .env.docker -f docker-compose.coolify.yml -f docker-compose.vps.yml up -d --build

ADMIN_PASSWORD="$(grep '^SERVICE_PASSWORD_ADMIN=' .env.docker | cut -d= -f2-)"

say "Done"
cat <<SUMMARY

  Admin panel : https://${LMS_DOMAIN}/admin
  Email       : ${LMS_ADMIN_EMAIL}
  Password    : ${ADMIN_PASSWORD}

  Secrets are in ${DIR}/.env.docker — keep a copy somewhere safe.
  Logs        : cd ${DIR} && docker compose -f docker-compose.coolify.yml -f docker-compose.vps.yml logs -f app
  Update      : re-run this script.

SUMMARY
