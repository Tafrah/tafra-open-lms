# طفرة — منصة تعليمية مجانية ومفتوحة المصدر للمدرّسين

منصة واحدة لكل مدرّس: كورسات، امتحانات، طلاب، ومدفوعات — على استضافتك أنت.

## التثبيت — اختار طريقة

| الطريقة | لمين | الدليل |
|---|---|---|
| **استضافة مشتركة** (cPanel / Hostinger) | أي مدرّس — من غير SSH ولا خبرة تقنية. ارفع الـ zip من [Releases](https://github.com/Tafrah/tafra-open-lms/releases)، اعمل قاعدة MySQL، وافتح `/install`. | [`docs/install/shared-hosting.md`](docs/install/shared-hosting.md) |
| **VPS بأمر واحد** | سيرفر Ubuntu/Debian: `curl -fsSL https://raw.githubusercontent.com/Tafrah/tafra-open-lms/main/install.sh \| sudo bash` (Docker + MySQL + HTTPS تلقائي). | [`docs/install/vps.md`](docs/install/vps.md) |
| **Coolify** | Docker Compose ← `docker-compose.coolify.yml` — الدومين وكلمات السر بتتولّد لوحدها. | [`docs/install/coolify.md`](docs/install/coolify.md) |

بعد التثبيت: لوحة التحكم على `/admin`، وسطر Cron واحد (`php artisan schedule:run` كل دقيقة) بيشغّل الإشعارات ومعالجة الصور والنسخ الاحتياطي — مفيش Redis ولا عامل طابور مطلوب. من غير واجهة: `php artisan lms:install --help`.

**English:** Tafra is a free, open-source LMS for teachers (one teacher per install). Install it on **shared hosting** (upload the release zip, create a MySQL database, open `/install`), on a **VPS** with one command (`install.sh`, Docker + MySQL + automatic HTTPS), or on **Coolify** (`docker-compose.coolify.yml`). Headless: `php artisan lms:install`. Guides: `docs/install/`.

---

## الرخصة

[AGPL-3.0](LICENSE): استخدمه وعدّله ببلاش — بس أي نسخة معدّلة بتتشغّل للناس لازم كودها يتنشر بنفس الرخصة.
اسم «طفرة» واللوجو علامة تجارية ومش جزء من الرخصة.

## للمطورين

- خريطة المنصة التعليمية: `src/Contexts/{Academy,Exams,Students,Billing,Storefront,Transfer,Installer}` — والقواعد في `CLAUDE.md`.
- النقل من/إلى طفرة: [`docs/transfer/format.md`](docs/transfer/format.md).
- الخطة والميزات: [`docs/product/plan.html`](docs/product/plan.html) · [`docs/product/features.html`](docs/product/features.html) · التصميم: [`docs/product/design-spec.md`](docs/product/design-spec.md).

---

### الستاك

PHP 8.4+ · Laravel 13 · Filament 5 · Livewire 4 · Tailwind 4 · **MySQL 8** (MariaDB 10.6+ شغالة كمان).
مفيش Redis ولا عامل طابور مطلوب: الكاش والجلسات والطابور على قاعدة البيانات، والطابور بيشتغل من `schedule:run`.

```bash
composer setup            # تثبيت + migrate + seed + build
composer dev              # سيرفر + vite + طابور
composer test             # Pest (MySQL: open_lms_testing)
composer test:browser     # رحلات المستخدم في متصفح حقيقي (Playwright)
```

### الوثائق التقنية (من القالب الأساسي)

| الملف | المحتوى |
|---|---|
| `docs/00-setup.md` | بيئة التطوير المحلية |
| `docs/01-architecture-ddd.md` | بنية DDD، الـ Contexts، الـ autoload، الـ ServiceProviders |
| `docs/02-permissions.md` | كتالوج الصلاحيات من config، الترجمة، الكاش |
| `docs/19-policies.md` | **طبقة السياسات — المرجع الوحيد للتفويض** |
| `docs/03-multi-tenancy.md` | تعدد المستأجرين، الـ scoping، عزل الكاش |
| `docs/04-media-filesystem.md` | Media Library، الـ DiskResolver، S3 |
| `docs/05-settings.md` | spatie/settings، الإعدادات الديناميكية، صفحات Filament |
| `docs/06-theme-branding.md` | ثيم كود المستقبل، التوكنز، الخطوط، الفوتر والحقوق |
| `docs/07-navigation-sidebar.md` | السايدبار المتقدم للتطبيقات الكبيرة |
| `docs/08-tables-ux.md` | جداول ممتازة: تحميل مؤجّل، فلاتر محفوظة، تصدير |
| `docs/09-notifications.md` | نظام الإشعارات الكامل + التفضيلات |
| `docs/10-i18n.md` | الترجمة عربي/إنجليزي، RTL، التواريخ والأرقام |
| `docs/11-logging-monitoring.md` | لوجينج مهيكل، request_id، Sentry، Health |
| `docs/12-auth-security.md` | 2FA، الأجهزة، الانتحال، سجل النشاط |
| `docs/13-queues-jobs.md` | الطابور والجدولة (cron على الاستضافة المشتركة) |
| `docs/14-production-https.md` | الإنتاج، HTTPS، الكاش، النشر |
| `docs/15-onboarding.md` | جولات إرشادية وonboarding للمستخدم الجديد |
| `docs/16-adding-a-feature.md` | **الوصفة:** إزاي تضيف ميزة جديدة من الصفر |
| `docs/17-testing-acceptance.md` | الاختبارات ومعايير القبول لكل تاسك |
| `docs/18-conventions.md` | اتفاقيات التسمية والكود والـ Git |
| `docs/20-security.md` | **تعليمات الأمان — نموذج التهديد وشروط القبول** |
| `docs/21-implementation-notes.md` | **فين اختلف الكود عن الوثيقة وليه** |
| `docs/22-webhooks.md` | استقبال وإرسال الـ webhooks: التوقيع، التكرار، الطابور، SSRF |
| `docs/23-mcp-server.md` | خادم MCP: توصيل الذكاء الاصطناعي بالنظام من غير ما يتخطّى الـ Policies |
| `docs/24-central-panel.md` | **اللوحة المركزية:** المؤسسات، التجهيز، الخطط، الإعدادات العامة، أدوات النظام |
| `PRODUCTION-READINESS.md` | حالة كل شرط قبول: منفّذ / يعتمد على البيئة |

### معايير القبول العامة (Definition of Done)

أي تاسك مش مقبول غير لما **كل** ده يتحقق:

1. **التفويض:** الميزة لها Policy فيها الصلاحية **وقواعد الأعمال** معاً، والصلاحية ورسائل الرفض مترجمة عربي وإنجليزي. صفر فحص صلاحية مباشر بره الـ Policy.
2. **العزل:** لو الميزة بتلمس بيانات، فيها `tenant_id` وفيها اختبار بيثبت إن مستأجر مش بيشوف بيانات التاني.
3. **الترجمة:** صفر نص مكتوب في الكود. كله من `__()` أو `trans_choice()`. الملفين `ar` و `en` كاملين.
4. **الـ RTL:** الشاشة اتفحصت بالعربي — مفيش `left/right` مكتوبة، كلها `start/end`.
5. **الاختبار:** فيه Pest test على الأقل للمسار السعيد + رفض الصلاحية.
6. **الوضع الداكن:** الشاشة اتفحصت في الوضعين.
7. **N+1:** اتفحصت بـ Telescope أو `->with()` صريح. مفيش استعلام جوه لوب.
8. **الفهرسة:** أي عمود `searchable` أو `sortable` عليه index في الميجريشن.
9. **اللوج:** أي عملية حساسة (حذف، صلاحية، فلوس) بتتسجّل في activitylog.
10. **الأمان:** بنود مراجعة الأمان في `docs/20-security.md` بند ١٤ متعلّم عليها.
11. **الوثيقة:** لو الميزة أضافت pattern جديد، اتضاف لـ `docs/`.

---

### الممنوعات (كسرها = رفض الـ PR فوراً)

- ❌ لون مكتوب مباشرة في مكوّن (`bg-blue-500`, `#12454F`) — استخدم التوكنز.
- ❌ نص مكتوب مباشرة في Blade أو PHP — استخدم `__()`.
- ❌ `left`/`right` في CSS — استخدم `start`/`end`.
- ❌ عنصر واجهة من غير فحص تفويض.
- ❌ `hasPermissionTo()` أو `hasRole()` بره الكلاس الأساسي `Policy` والـ Gates.
- ❌ `can('action.resource')` بنص صلاحية — استخدم `can('ability', $model)`.
- ❌ قاعدة أعمال في الـ Resource بدل الـ Policy.
- ❌ `->skipAuthorization()` — رفض فوري.
- ❌ `->disabled()` أو `->visible()` لوحدهم على حقل حساس — لازم منع حفظ فعلي.
- ❌ `Gate::before` بيرجّع `true` للمدير العام من غير احترام `invariants()`.
- ❌ `$guarded = []` — قائمة `$fillable` بيضاء صريحة.
- ❌ `{!! !!}` على محتوى غير منقّى.
- ❌ `whereRaw`/`orderByRaw` بمدخل مستخدم من غير قائمة بيضاء.
- ❌ استعلام من غير `tenant_id` scope على موديل تابع لمستأجر.
- ❌ منطق أعمال جوه Filament Resource — يروح لـ Action/Service في طبقة Application.
- ❌ `env()` بره ملفات `config/` — بيتكسر مع `config:cache`.
- ❌ `dd()`, `dump()`, `ray()` في كود مدموج.
- ❌ ميجريشن بيعدّل ميجريشن قديم — اعمل ميجريشن جديد.
- ❌ `composer update` من غير مناقشة — استخدم `composer require` لباكدج محدد.
- ❌ رفع ملف `.env` أو أي مفتاح لـ Git.
- ❌ التدرج أزرق→بنفسجي، قبعة تخرّج، أيقونات AI/شبكات/أدمغة، صور Stock — ممنوعة بدليل الهوية.

---

### إزاي تشتغل

1. اقرأ `docs/18-conventions.md` و `docs/01-architecture-ddd.md` و `docs/19-policies.md` و `docs/20-security.md` **قبل** ما تكتب أي سطر.
2. خد تاسك واحد بس في المرة — والخطة في `docs/product/plan.html`.
3. افتح branch باسم `feat/<context>-<short-name>` (مثال: `feat/identity-two-factor`).
4. قبل الـ PR: `composer check` (الوضعين single و multi) و`npm run build && composer test:browser` لازم يعدّوا.
5. الـ PR فيه: وصف بالعربي + لقطة شاشة عربي + لقطة شاشة إنجليزي + وضع داكن.
6. أي سؤال مش لاقي إجابته في `docs/` — اسأل، ولما تعرف الإجابة **اكتبها في `docs/`**.

---

### ملاحظة أخيرة للفريق

الوثائق دي مش نصوص مقدسة. لو لقيت طريقة أحسن، أو الباكدج اتغيّر، أو حاجة مكتوبة هنا غلط — **عدّلها**. لكن عدّلها في الوثيقة الأول، وبعدين في الكود. الكود اللي مش متوثّق مش موجود.

الهدف النهائي مش «قالب شغّال». الهدف إن مطوّر جديد يفتح الريبو، يقرا ساعة، ويبقى منتج.
