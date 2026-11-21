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

# القالب الأساسي — قالب لوحة التحكم من كود المستقبل

> **الهدف:** بناء قالب Laravel + Filament جاهز للإنتاج، نستنسخه في أي مشروع جديد فنكسب من أول يوم: صلاحيات كاملة، تعدد مستأجرين، ملفات، إعدادات ديناميكية، إشعارات، ترجمة، ثيم كود المستقبل، ولوجينج محترم.
>
> **الجمهور:** فريق الإنترن — الملف ده هو المرجع الوحيد. مفيش شغل بره الوارد هنا من غير مناقشة.
> **الإصدار:** v1.0 · أغسطس ٢٠٢٦

---

## ١. اقرأ ده الأول (٥ دقايق)

القالب ده مش «مشروع». ده **أساس (foundation)** هيتبني عليه ٥ أو ١٠ مشاريع بعد كده. يعني كل قرار بتاخده هنا هيتضرب × ١٠. القاعدة الحاكمة:

> **لو حاجة اتكتبت hardcoded، هي غلط.** الألوان، الصلاحيات، الديسك، الإعدادات، النصوص — كلها من config أو DB أو ملف ترجمة. مفيش استثناء.

القاعدة التانية:

> **أي عنصر في الواجهة — حتى زرار — لازم يعدّي على فحص تفويض عشان يظهر.** لو مفيش صلاحية، العنصر مش موجود في الـ DOM أصلاً، ومع كده الـ endpoint وراه محمي كمان.

القاعدة التالتة — وهي اللي بتفرّق بين قالب شغّال وقالب محترم:

> **التفويض كله يمرّ على Laravel Policies. مفيش استثناء.**
>
> `hasPermissionTo()` و`hasRole()` مسموح ليهم في مكانين بس في التطبيق كله: الكلاس الأساسي `Policy`، وتعريفات الـ Gates. أي مكان تاني بيسأل `can($ability, $model)`.
>
> السبب: «هل ينفع أنشر؟» مش سؤال صلاحية واحد — هو صلاحية **زائد** قواعد أعمال (الحالة مسودة؟ العنوان مكتوب؟ مش محذوف؟). لو القواعد دي اتفرّقت على الواجهة، هتتكرر في الـ API والـ Job وهتنسى واحدة. الـ Policy بتجمّعهم في مكان واحد، **بترجّع سبب الرفض** عشان المستخدم يفهم، وبتتختبر لوحدها من غير واجهة. التفاصيل في `docs/19-policies.md`.

---

## ٢. الستاك (الإصدارات مثبّتة — متغيّرش من غير مناقشة)

| المكوّن | الإصدار | ملاحظة |
|---|---|---|
| PHP | **8.4** | مطلوب لـ activitylog 5.x و backup 10.x |
| Laravel | **13.x** (`^13.0`) | صدر مارس ٢٠٢٦ |
| Filament | **5.x** (`^5.7`) | v5 = v4 + دعم Livewire v4. الـ API متطابق مع v4 |
| Livewire | **4.x** | بييجي مع Filament v5 |
| Tailwind CSS | **4.1+** | Filament v4/v5 بيتطلبه |
| PostgreSQL | **18** | 18.6 مستقر · دعم حتى ٢٠٣٠ |
| Redis | **8.10** | cache + queue + session |
| Node | **22 LTS** | للـ Vite build |

### الباكدجات الأساسية

```bash
# صلاحيات + إعدادات + ملفات + سجل نشاط
composer require spatie/laravel-permission:^8.3
composer require spatie/laravel-medialibrary:^11.23
composer require spatie/laravel-settings:^3.9
composer require spatie/laravel-activitylog:^5.1
composer require spatie/laravel-translatable:^6.14
composer require spatie/laravel-backup:^10.3
composer require spatie/laravel-health:^1.40

# طوابير ومراقبة
composer require laravel/horizon:^5.48
composer require laravel/pulse:^1.8
composer require laravel/telescope:^5.22 --dev

# إضافات Filament
composer require bezhansalleh/filament-shield:^4.3
composer require filament/spatie-laravel-media-library-plugin:"^5.7"
composer require filament/spatie-laravel-settings-plugin:"^5.7"
composer require stechstudio/filament-impersonate:^5.6
composer require pxlrbt/filament-activity-log:^3.1
composer require pxlrbt/filament-excel
composer require jibaymcs/filament-tour:^5.0

# مراقبة أخطاء (اختار واحد)
composer require sentry/sentry-laravel
```

> ⚠️ **قبل ما تثبّت أي حاجة:** شغّل `composer show <package> --available | head -30` وتأكد من أحدث إصدار فعلياً وأن الـ constraint بتاع Filament فيه `^5.0`. لو باكدج مش داعم v5، وقف وبلّغ — متنزّلش Filament عشان باكدج.
>
> **القرارات اللي اتاخدت فعلاً** (التفاصيل والأسباب في `docs/21-implementation-notes.md` بند ٨):
> - ❌ `bezhansalleh/filament-shield` → ✅ `RoleResource` بإيدنا (الصلاحيات من الكونفيج مش من مسح الموارد)
> - ❌ `pxlrbt/filament-excel` → ✅ مُصدِّرات Filament الأصلية (في الطابور افتراضياً وبتحترم `->authorize()`)
> - ❌ `opcodesio/log-viewer` → ✅ صفحة عارض بتاعتنا (شرط تنقية الأسرار قبل العرض)
> - ❌ `jibaymcs/filament-tour` → ✅ `packages/future-code/filament-onboarding` (قرار `docs/15` نفسه)
> - ❌ `pxlrbt/filament-spotlight` → ✅ البحث الشامل المدمج (Ctrl+K)
> - ⚠️ `filament/spatie-laravel-translatable-plugin` — **مفيش إصدار v5**. البديل: تبويب لكل لغة زي `docs/10` بند ٣.

---

## ٣. خريطة الوثائق

| الملف | المحتوى | متى تقراه |
|---|---|---|
| `docs/00-setup.md` | التثبيت من الصفر، Docker، .env، أول تشغيل | يوم ١ |
| `docs/01-architecture-ddd.md` | بنية DDD، الـ Contexts، الـ autoload، الـ ServiceProviders | يوم ١ — **إلزامي** |
| `docs/02-permissions.md` | كتالوج الصلاحيات من config، الترجمة، الكاش | أسبوع ١ |
| `docs/19-policies.md` | **طبقة السياسات — المرجع الوحيد للتفويض** | أسبوع ١ — **إلزامي** |
| `docs/03-multi-tenancy.md` | تعدد المستأجرين، الـ scoping، عزل الكاش | أسبوع ١ |
| `docs/04-media-filesystem.md` | Media Library، الـ DiskResolver، S3 | أسبوع ٢ |
| `docs/05-settings.md` | spatie/settings، الإعدادات الديناميكية، صفحات Filament | أسبوع ٢ |
| `docs/06-theme-branding.md` | ثيم كود المستقبل، التوكنز، الخطوط، الفوتر والحقوق | أسبوع ٢ |
| `docs/07-navigation-sidebar.md` | السايدبار المتقدم للتطبيقات الكبيرة | أسبوع ٣ |
| `docs/08-tables-ux.md` | جداول ممتازة: تحميل مؤجّل، فلاتر محفوظة، تصدير | أسبوع ٣ |
| `docs/09-notifications.md` | نظام الإشعارات الكامل + التفضيلات | أسبوع ٣ |
| `docs/10-i18n.md` | الترجمة عربي/إنجليزي، RTL، التواريخ والأرقام | أسبوع ٤ |
| `docs/11-logging-monitoring.md` | لوجينج مهيكل، request_id، Sentry، Health | أسبوع ٤ |
| `docs/12-auth-security.md` | 2FA، الأجهزة، الانتحال، سجل النشاط | أسبوع ٤ |
| `docs/13-queues-jobs.md` | Redis queues، Horizon، الجدولة | أسبوع ٥ |
| `docs/14-production-https.md` | الإنتاج، HTTPS، الكاش، النشر | أسبوع ٥ |
| `docs/15-onboarding.md` | جولات إرشادية وonboarding للمستخدم الجديد | أسبوع ٥ |
| `docs/16-adding-a-feature.md` | **الوصفة:** إزاي تضيف ميزة جديدة من الصفر | مرجع دائم |
| `docs/17-testing-acceptance.md` | الاختبارات ومعايير القبول لكل تاسك | مرجع دائم |
| `docs/18-conventions.md` | اتفاقيات التسمية والكود والـ Git | يوم ١ — **إلزامي** |
| `docs/20-security.md` | **تعليمات الأمان — نموذج التهديد وشروط القبول** | أسبوع ١ — **إلزامي** |
| `docs/21-implementation-notes.md` | **فين اختلف الكود عن الوثيقة وليه** | يوم ١ — **إلزامي** |
| `docs/22-webhooks.md` | استقبال وإرسال الـ webhooks: التوقيع، التكرار، الطابور، SSRF | قبل أول تكامل خارجي |
| `docs/23-mcp-server.md` | خادم MCP: توصيل الذكاء الاصطناعي بالنظام من غير ما يتخطّى الـ Policies | قبل أي تكامل AI |
| `docs/24-central-panel.md` | **اللوحة المركزية:** المؤسسات، التجهيز، الخطط، الإعدادات العامة، أدوات النظام | أسبوع ١ — **إلزامي** |
| `PRODUCTION-READINESS.md` | حالة كل شرط قبول: منفّذ / يعتمد على البيئة | قبل أي إطلاق |

---

## ٤. الخطة الزمنية (٦ أسابيع)

كل أسبوع له **مخرَج قابل للعرض**. آخر يوم في الأسبوع = ديمو للفريق.

### الأسبوع ١ — الأساس
- [ ] تثبيت المشروع + Docker Compose (PHP 8.4، Postgres 18، Redis 8)
- [ ] بنية DDD كاملة + Context تجريبي واحد شغّال (`Identity`)
- [ ] Filament panel واحد (`admin`) شغّال على HTTPS محلياً
- [ ] Multi-tenancy: جدول `tenants`، عمود `tenant_id`، `HasTenants` على المستخدم
- [ ] spatie/permission بـ `teams => true` + Redis cache
- [ ] ملف `config/authorization.php` + أمر `php artisan authorization:sync`
- [ ] **طبقة السياسات:** `Policy` + `Decision` + `InvariantRegistry` + `Gate::guessPolicyNamesUsing()`
- [ ] الاختبارات المعمارية اللي بتمنع فحص الصلاحيات المباشر
- **الديمو:** مستخدمين في مستأجرين مختلفين كل واحد يشوف بياناته بس، ومدير عام **مش** قادر يحذف نفسه.

### الأسبوع ٢ — الهوية والمحتوى
- [ ] ثيم Filament مخصص كامل بتوكنز كود المستقبل (فاتح + داكن)
- [ ] الخطوط: IBM Plex Sans Arabic + IBM Plex Mono
- [ ] الفوتر بالحقوق عبر render hook
- [ ] Media Library + `DiskResolver` + دعم S3 (local في dev، S3 في prod)
- [ ] spatie/settings + صفحات إعدادات في Filament (عامة، البريد، التخزين، المظهر)
- **الديمو:** رفع صورة تروح S3، وتغيير اللون الأساسي من صفحة الإعدادات يغيّر اللوحة.

### الأسبوع ٣ — الواجهة الاحترافية
- [ ] السايدبار المتقدم (مجموعات، طيّ، بحث، اختصارات، بادچات)
- [ ] `Table::configureUsing()` بالإعدادات الافتراضية الممتازة لكل الجداول
- [ ] Resource كامل نموذجي (`UserResource`) بكل الـ patterns
- [ ] نظام الإشعارات: database + broadcast + تفضيلات لكل مستخدم
- **الديمو:** جدول بـ ٥٠ ألف صف بيفتح فوراً، وإشعار حي بيوصل من غير refresh.

### الأسبوع ٤ — الترجمة والمراقبة
- [ ] ترجمة كاملة عربي/إنجليزي + RTL صحيح ١٠٠٪
- [ ] كل الصلاحيات مترجمة
- [ ] لوجينج JSON مهيكل + `request_id` + `tenant_id` في كل سطر
- [ ] Sentry + spatie/health + صفحة صحة النظام في Filament
- [ ] 2FA + إدارة الأجهزة + الانتحال + سجل النشاط
- [ ] `HtmlSanitizer` على محتوى المحرّر + الرؤوس الأمنية + فحص الأسرار في CI
- **الديمو:** تبديل اللغة يقلب اللوحة بالكامل، خطأ متعمّد يظهر في Sentry بكل السياق، ومحاولة حقن `<script>` في المحرّر بتتنقّى.

### الأسبوع ٥ — الطوابير والإنتاج
- [ ] Horizon + supervisor + المهام المجدولة
- [ ] النسخ الاحتياطي التلقائي (spatie/backup) لـ S3
- [ ] HTTPS كامل + TrustProxies + HSTS + الكوكيز الآمنة
- [ ] سكربت النشر + الـ caching commands
- [ ] الجولات الإرشادية (onboarding tours)
- **الديمو:** نشر على سيرفر staging حقيقي بـ HTTPS شغّال.

### الأسبوع ٦ — الصقل والتوثيق
- [ ] اختبارات Pest: كل صلاحية، كل قاعدة أعمال في كل Policy، كل عزل مستأجر
- [ ] اختبار اختراق يدوي (`docs/20-security.md` بند ١٥) ومُوثّق
- [ ] `make:fc-feature` — أمر يولّد ميزة كاملة بالبنية الصحيحة
- [ ] مراجعة كاملة لـ `docs/16-adding-a-feature.md` وتجربته بميزة حقيقية
- [ ] فيديو ٢٠ دقيقة يشرح القالب للمطوّر الجديد
- **الديمو:** مطوّر من بره الفريق يضيف ميزة كاملة في ساعة واحدة بالوثائق بس.

---

## ٥. معايير القبول العامة (Definition of Done)

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

## ٦. الممنوعات (كسرها = رفض الـ PR فوراً)

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

## ٧. إزاي تشتغل

1. اقرأ `docs/18-conventions.md` و `docs/01-architecture-ddd.md` و `docs/19-policies.md` و `docs/20-security.md` **قبل** ما تكتب أي سطر.
2. خد تاسك من الأسبوع الحالي بس. متسبقش.
3. افتح branch باسم `feat/<context>-<short-name>` (مثال: `feat/identity-two-factor`).
4. قبل الـ PR: `composer check` (الوضعين single و multi) و`npm run build && composer test:browser` لازم يعدّوا.
5. الـ PR فيه: وصف بالعربي + لقطة شاشة عربي + لقطة شاشة إنجليزي + وضع داكن.
6. أي سؤال مش لاقي إجابته في `docs/` — اسأل، ولما تعرف الإجابة **اكتبها في `docs/`**.

---

## ٨. ملاحظة أخيرة للفريق

الوثائق دي مش نصوص مقدسة. لو لقيت طريقة أحسن، أو الباكدج اتغيّر، أو حاجة مكتوبة هنا غلط — **عدّلها**. لكن عدّلها في الوثيقة الأول، وبعدين في الكود. الكود اللي مش متوثّق مش موجود.

الهدف النهائي مش «قالب شغّال». الهدف إن مطوّر جديد يفتح الريبو، يقرا ساعة، ويبقى منتج.
