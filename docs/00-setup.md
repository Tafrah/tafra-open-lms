# ٠٠ — التثبيت والإعداد

## المتطلبات على جهازك

- Docker + Docker Compose (الطريقة الموصى بها — مفيش «شغال عندي»)
- Node 22 LTS
- Composer 2.8+

## ١. Docker Compose

`docker-compose.yml` في جذر المشروع:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
    depends_on: [postgres, redis]
    environment:
      PHP_IDE_CONFIG: "serverName=fc-admin"

  web:
    image: nginx:1.27-alpine
    ports: ["8443:443", "8080:80"]
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./docker/nginx/certs:/etc/nginx/certs
    depends_on: [app]

  postgres:
    image: postgres:18-alpine
    environment:
      POSTGRES_DB: fc_admin
      POSTGRES_USER: fc
      POSTGRES_PASSWORD: secret
    volumes: ["pgdata:/var/lib/postgresql/data"]
    ports: ["5432:5432"]

  redis:
    image: redis:8-alpine
    command: redis-server --appendonly yes
    volumes: ["redisdata:/data"]
    ports: ["6379:6379"]

  minio:                       # يحاكي S3 محلياً
    image: minio/minio
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: minio
      MINIO_ROOT_PASSWORD: minio12345
    volumes: ["miniodata:/data"]
    ports: ["9000:9000", "9001:9001"]

  horizon:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php artisan horizon
    volumes: ["./:/var/www/html"]
    depends_on: [redis, postgres]

  scheduler:                   # بديل cron
    build: { context: ., dockerfile: docker/php/Dockerfile, target: development }
    command: php artisan schedule:work
    volumes: ["./:/var/www/html"]
    depends_on: [redis, postgres]

  reverb:                      # البث الحي
    build: { context: ., dockerfile: docker/php/Dockerfile, target: development }
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    volumes: ["./:/var/www/html"]
    ports: ["8081:8080"]
    depends_on: [redis]

volumes: { pgdata: {}, redisdata: {}, miniodata: {} }
```

> الملف الحقيقي في الجذر فيه كمان `healthcheck` لـ postgres وredis
> و`target: development` على صور PHP — الفروق دي مقصودة ومتحدّثة هناك.

> **ليه MinIO؟** عشان نجرّب S3 من يوم ١ من غير ما ندفع ولا نتصل بالإنترنت. الكود بيشوفه S3 عادي.

## ٢. شهادة HTTPS محلية

الشرط إن التطبيق يشتغل على HTTPS من أول يوم — مش آخر يوم.

### الطريقة أ — Caddy (موصى بيها، من غير Docker)

`Caddyfile` موجود في جذر المشروع. Caddy بيولّد شهادة موثوقة محلياً لوحده،
فمفيش حاجة اسمها mkcert مطلوبة:

```bash
brew install caddy
php artisan serve --port=8000 &
sudo caddy run --config Caddyfile
```

بعدها `https://fc-admin.test/admin` بقفل أخضر. اضبط `APP_URL` على نفس العنوان.

### الطريقة ب — Docker + mkcert

```bash
brew install mkcert   # أو choco install mkcert على ويندوز
mkcert -install
mkdir -p docker/nginx/certs
mkcert -key-file docker/nginx/certs/local-key.pem \
       -cert-file docker/nginx/certs/local.pem \
       fc-admin.test "*.fc-admin.test" localhost
```

ضيف في `/etc/hosts`:
```
127.0.0.1 fc-admin.test
127.0.0.1 acme.fc-admin.test
127.0.0.1 beta.fc-admin.test
```

> الساب‑دومينات دي للمستأجرين — هنستخدمها في `docs/03-multi-tenancy.md`.

## ٣. إنشاء المشروع

```bash
composer create-project laravel/laravel:^13.0 .
composer require filament/filament:"^5.7" -W
php artisan filament:install --panels
```

`php artisan filament:install --panels` هيسألك عن اسم اللوحة — اكتب `admin`.

## ٤. ملف .env المحلي

```dotenv
APP_NAME="Future Code Admin"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://fc-admin.test:8443
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Africa/Cairo

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=fc_admin
DB_USERNAME=fc
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CLIENT=phpredis

CACHE_STORE=redis
# الجلسات في قاعدة البيانات مش Redis — ForceLogoutAction محتاج يمسح
# جلسة **محددة**، وده مايتعملش على Redis من غير SCAN يدوي.
# القرار موثّق في docs/12 بند ٢ و docs/21 بند ٧.
SESSION_DRIVER=database
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio12345
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=fc-admin
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/fc-admin
AWS_USE_PATH_STYLE_ENDPOINT=true

LOG_CHANNEL=stack
LOG_STACK=daily,stderr
LOG_LEVEL=debug
```

> ⚠️ **قاعدة:** أي مفتاح جديد في `.env` لازم يتضاف في `.env.example` **في نفس الـ commit**. مفيش استثناء.
>
> ⚠️ **`.env.example` بيشحن `FILESYSTEM_DISK=public` مش `s3`** — عشان أول
> `composer setup` يشتغل على أي جهاز من غير MinIO. لو بتشغّل Docker،
> غيّرها لـ `s3` وحط قيم `AWS_*` اللي فوق عشان تجرّب المسار الحقيقي.

## ٥. أول تشغيل

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
npm install && npm run build
```

## ٦. حسابات البذور (seeders)

السيدر لازم يعمل:
- مستأجرين اتنين: `acme` و `beta`
- مستخدم `super_admin` (بيشوف كل حاجة، `Gate::before`)
- مستخدم `admin` في `acme` بس
- مستخدم `viewer` في `acme` بصلاحيات قراءة فقط
- ٥٠ ألف صف بيانات وهمية في جدول تجريبي — عشان نختبر أداء الجداول من يوم ١

```bash
php artisan db:seed --class=DemoSeeder   # ٥٠ ألف إعلان + ٢٠٠ مستخدم، ≈١٫٥ ثانية
```

الأرقام المقيسة فعلياً على الداتاست ده في `PRODUCTION-READINESS.md`.

## ٧. أوامر يومية

```bash
composer test          # Pest
composer lint          # Pint + PHPStan
php artisan authorization:sync    # مزامنة الصلاحيات من الكونفيج
php artisan filament:optimize-clear
npm run dev
```

## ٨. معايير القبول لهذه المرحلة

- [ ] `https://fc-admin.test:8443/admin` بيفتح بقفل أخضر
- [ ] `php artisan about` بيقول Redis للكاش والطابور — **والجلسة في قاعدة البيانات**
      (`ForceLogoutAction` محتاج يمسح جلسة محددة · بند ٤ و`docs/12` بند ٢)
- [ ] رفع ملف تجريبي بيوصل MinIO ويظهر في الكونسول بتاعه
- [ ] `docker compose down && docker compose up -d` والبيانات لسه موجودة
- [ ] مطوّر تاني نزّل الريبو ووصل لنفس النتيجة بالخطوات دي بس
