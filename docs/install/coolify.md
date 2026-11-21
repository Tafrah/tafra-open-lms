# التثبيت على Coolify

[Coolify](https://coolify.io) بيبني الصورة من المستودع، ويعمل الدومين والشهادة وكلمات السر لوحده.

## الخطوات

1. **Projects ← New ← Resource ← Docker Compose** (من «Public Repository» أو GitHub App).
2. المستودع: `https://github.com/Tafrah/tafra-open-lms` — الفرع `main`.
3. **Docker Compose Location:** `/docker-compose.coolify.yml`.
4. **Environment Variables** — المطلوب واحد بس:
   | المتغيّر | مثال |
   |---|---|
   | `LMS_ADMIN_EMAIL` | `teacher@example.com` |
   | `LMS_PLATFORM_NAME` (اختياري) | `أكاديمية النجاح` |
   | `LMS_TEACHER_NAME` (اختياري) | `أ. محمد أحمد` |
   | `LMS_SUBJECT` (اختياري) | `الفيزياء` |
   | `LMS_ADMIN_PHONE` (اختياري) | `+201001234567` |
5. **Domains** لخدمة `app`: اكتب دومينك (`https://academy.example.com`) — أو سيب اللي Coolify ولّده.
6. **Deploy**.

## اللي Coolify بيعمله لوحده

الملف بيستخدم «المتغيّرات السحرية»:

| المتغيّر | بيتحط فيه |
|---|---|
| `SERVICE_FQDN_APP` | رابط المنصة ← `APP_URL` (والـ entrypoint بيضيف `https://` لو ناقص) |
| `SERVICE_USER_DB` / `SERVICE_PASSWORD_DB` | مستخدم MySQL وكلمة سره |
| `SERVICE_PASSWORD_ROOT` | root بتاع MySQL |
| `SERVICE_PASSWORD_ADMIN` | **كلمة سر حساب المدير** — تلاقيها في تبويب Environment Variables |

`APP_KEY` لو سبته فاضي بيتولّد أول تشغيل ويتحفظ في volume الـ `storage` (`storage/app/.app-key`). لو حطيته إنت، ماتغيّروش بعد كده.

## إيه اللي بيحصل في كل نشر

حاوية `app` (`docker/coolify/Dockerfile`) فيها nginx + php-fpm + `schedule:work` + `queue:work` تحت supervisord. الـ entrypoint (`docker/coolify/entrypoint.sh`):

1. يستنّى MySQL.
2. **أول مرة بس:** `php artisan lms:install --skip-env --if-not-installed` بالقيم اللي فوق.
3. **كل مرة:** `migrate --force`، `authorization:sync`، `storage:link`، `passport:keys` (مرة)، والكاش (`config/route/view/event:cache`).

مفيش Redis: الكاش والجلسات والطوابير على MySQL. البيانات في volumes: `lms-storage` (الصور، الملفات، النسخ الاحتياطية، المفاتيح) و`lms-mysql`.

## البريد

ضيف في Environment Variables: `MAIL_MAILER=smtp`، `MAIL_HOST`، `MAIL_PORT`، `MAIL_USERNAME`، `MAIL_PASSWORD` — أو اضبطه من لوحة التحكم ← الإعدادات ← البريد.

## مشاكل شائعة

- **الحاوية بتقف في الأول:** `LMS_ADMIN_EMAIL` ناقص (الـ compose بيرفض يقوم من غيره)، أو MySQL لسه بيبدأ — Coolify بيعيد المحاولة.
- **صفحة الدخول بترجع لنفسها:** الدومين `http` مش `https`. خلّي الدومين في Coolify بـ `https://`، أو `SESSION_SECURE_COOKIE=false` لو بتجرّب من غير شهادة.
