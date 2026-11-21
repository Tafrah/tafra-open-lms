# ٢٤ — اللوحة المركزية (`/central`)

> **الحالة:** منفّذة — اللوحة، الأدوار المركزية، تجهيز/إيقاف/انتحال المؤسسات،
> وتقسيم الإعدادات. الخطط والحدود (بند ٥) **لسه**. `PRODUCTION-READINESS.md`
> بيقول إيه اللي اتحقق وبأي اختبار.

## المشكلة

القالب كان لوحة واحدة `/admin` بتخلط شغل المؤسسة بالتحكم في النظام كله:
إدارة المؤسسات، الإعدادات العامة (البريد والتخزين والأمان)، Horizon والصحة
والنسخ الاحتياطي. والخلط ده كان فيه ثغرة حقيقية: دور `admin` بتاع المؤسسة
معاه `settings.*` — يعني مدير مؤسسة واحدة يقدر يغيّر **خادم البريد للنظام كله**.

الحل: لوحتين بحدود واضحة.

| | `/admin/t/{slug}` — لوحة المؤسسة | `/central` — اللوحة المركزية |
|---|---|---|
| **مين** | أعضاء المؤسسة | فريق التشغيل (المدير العام، الدعم) |
| **السياق** | مؤسسة واحدة مهيّأة (`TenantContext`) | مفيش مؤسسة — `tenancy()->end()` |
| **الأدوار** | على فريق المؤسسة | على الفريق المركزي `0` |
| **المحتوى** | المستخدمين، الأدوار، المحتوى، الوسائط، سجل نشاط المؤسسة، ملف المؤسسة، مظهرها وإشعاراتها | المؤسسات، فريق التشغيل، الإعدادات العامة، أدوات النظام، سجل النشاط الكامل |

---

## ١. البنية

- `app/Providers/Filament/CentralPanelProvider.php` — لوحة `central`، من غير
  `->tenant()`، نفس الحارس والثيم.
- كل سياق بيحط شاشاته المركزية في `Presentation/Filament/Central/{Resources,Pages,Widgets,Clusters}`،
  و`ContextPanelRegistry` بيكتشفها للوحة `central` زي ما بيكتشف الباقي لـ `admin`
  (`PANEL_DIRECTORIES`). الشاشات المشتركة (2FA، أجهزتي، `ActivityLogResource`)
  بتتسجّل صراحةً في المزوّد.
- ميدلوير `EndTenancy` في ستاك اللوحة المركزية: بينهي أي tenancy مفتوحة،
  وبيضبط فريق spatie على الفريق المركزي.
- الدخول: `canAccessPanel('central')` → Gate `access.panel.central`.

> ⚠️ `InitializeTenantContext` بيختار «أول مؤسسة نشطة للمستخدم» لو مفيش
> مؤسسة في الرابط. في اللوحة المركزية ده كان هيحط المدير العام في سياق
> مؤسسة عشوائية — عشان كده `EndTenancy` مش `InitializeTenantContext`.

> ⚠️ **الميدلويرين دول في قايمة الأولوية قبل `Authenticate`** (`bootstrap/app.php`).
> Laravel بيرتّب `Authenticate` فوق باقي ميدلوير اللوحة، و`canAccessPanel()`
> بيسأل على الفريق الحالي — من غير الأولوية كان بيسأل على فريق فاضي في أول
> طلب لأي عامل → 403 للكل (`docs/21` بند ٣٠).

---

## ٢. الأدوار المركزية — الفريق `0`

spatie teams مفعّل، و`model_has_roles.tenant_id` **إلزامي وجزء من المفتاح
الأساسي** ومالوش FK على `tenants`. فالأدوار المركزية مش ممكن تبقى `null` —
بتتربط بفريق محجوز: `config('authorization.central_team_id')` = `0`.

- أرقام المؤسسات بتبدأ من `1`، وقيد `CHECK (id > 0)` على `tenants` بيخلّي
  التصادم مستحيل على مستوى القاعدة.
- `TenantContextBootstrapper::sync(null)` بيضبط الفريق على `0` — يعني أي
  سياق من غير مؤسسة (اللوحة المركزية، أمر، Job مركزي) بيشوف الأدوار المركزية بس.
- الأدوار في `config/authorization.php`: `roles` = أدوار المؤسسة، و`central_roles`
  = `super_admin` و`support`. `authorization:sync` بيزامن الاتنين، وشاشة أدوار
  المؤسسة وحقل الأدوار في المستخدم مابيعرضوش أدوار فريق التشغيل.
- `UserSeeder` مابيربطش `super_admin` بأي مؤسسة — دخول فريق التشغيل للوحة
  مؤسسة **بالانتحال بس**.
- `CentralRoles` بيسأل عن الأدوار على الفريق `0` **صراحةً** (استعلام على
  `model_has_roles`) مش `hasRole()` — لأن `hasRole()` بيسأل عن الفريق الحالي،
  وجوه لوحة مؤسسة المدير العام كان هيبان «مالوش دور». الإجابة بتتخزّن لكل طلب
  وبتتمسح مع كل تبديل مستأجر.
- `Gate::before` بيسأل `CentralRoles::isSuperAdmin()` ومع احترام
  `InvariantRegistry` زي ما هو (`docs/19` بند ٥). و`Decision::permission()`
  بيعدّي المدير العام عبر `CentralAuthority` — القدرات المحمية بتوصل للـ Policy
  والمدير العام مالوش صلاحيات على فريق المؤسسة، فخطوة الصلاحية كانت هترفضه
  قبل ما قاعدة السلامة نفسها تتقيّم.

---

## ٣. تجهيز مؤسسة

`ProvisionTenantAction` هو الطريق الوحيد لإنشاء مؤسسة من اللوحة:

| الوضع | الخطوات |
|---|---|
| **single** | الصف في `tenants` (`ready` فوراً) ← `TenantDefaultsSeeder` بسياق المؤسسة |
| **multi** | الصف (`pending`) ← `CreateDatabase` ← `MigrateDatabase` ← `SeedDatabase` (`TenantDefaultsSeeder`) ← `MarkTenantProvisioned` (`ready`) |

- في multi السلسلة بتتربط بحدث `TenantCreated` في `TenancyServiceProvider`،
  وبتشتغل في الطابور لو `TENANCY_PROVISION_QUEUED=true` (الإنتاج).
- `provisioning_status` الافتراضي في القاعدة `pending` — لو السلسلة وقفت بعد
  `CreateDatabase` المؤسسة ماتبانش جاهزة. الانتحال على مؤسسة مش `ready` مرفوض
  (`tenancy.not_provisioned`).
- مفيش `DB::transaction` حوالين التجهيز: Postgres بيرفض `CREATE DATABASE` جوه transaction.
- `TenantDefaultsSeeder` لازم يكون **idempotent** — التجهيز ممكن يتعاد.
- المُنشئ مابينضمش للمؤسسة — الشاشة المركزية بتعرض كل المؤسسات أصلاً.
- **الحذف:** ناعم أولاً. الحذف النهائي بس هو اللي بيمسح القاعدة
  (`Tenant::forceDeleted()`)، وبيتطلب كتابة المُعرّف للتأكيد. حذف/إيقاف آخر
  مؤسسة نشطة invariant مايتخطّاهوش حتى المدير العام (`docs/21` بند ٣٠).
- **ملف المؤسسة** (الاسم، الوصف، لون العلامة) بيتعدّل من جوه لوحتها:
  `EditOrganization` عبر `->tenantProfile()` وبصلاحية `access.organization_profile`.

---

## ٤. الإيقاف المؤقت والانتحال

- `SuspendTenantAction` بيحط `suspended_at` + `suspension_reason`، و`ActivateTenantAction` بيشيلهم.
- `Tenant::scopeActive()` و`User::getTenants()` بيستبعدوا الموقوف — فالمؤسسة
  بتختفي من قائمة التبديل.
- ميدلوير `EnsureTenantIsActive` في ستاك المؤسسة: الرابط المباشر بيرجّع صفحة
  «المؤسسة موقوفة» مترجمة (403 + السبب)، مش 404 ولا البيانات.
- `ImpersonateTenantAction`: فريق التشغيل بيدخل المؤسسة بصفة **أقدم مدير نشط**
  فيها (`authorization.tenant_admin_role`)، والجلسة بتتسجّل (`tenant_impersonated`).
  مسموح على مؤسسة موقوفة — عشان الدعم يقدر يشوف المشكلة.
- **الرجوع:** فريق التشغيل مش عضو في أي مؤسسة، فالرجوع لـ `/admin` = طريق
  مسدود. الـ Action بيسجّل `impersonate.back_to` = `/central`، ومسار «إنهاء
  الانتحال» في الباكدج و`EnforceImpersonationTimeout` الاتنين بيرجّعوا له.

---

## ٥. الخطط والحدود

> **لسه مش منفّذ.**

- سياق جديد `Subscriptions`: موديل `Plan` مركزي — اسم مترجم، `limits` (json:
  `max_users`، `max_storage_mb`، ميزات)، `trial_days`.
- `tenants.plan_id`.
- `PlanLimits` بتتقرا **جوه الـ Policies** عبر `PolicyMemo` — مثلاً
  `UserPolicy::create` عليها قاعدة `plan.max_users_reached` برسالة مترجمة.
  الحد مش في الواجهة: الواجهة بتخفي/تعطّل، والـ Policy هي اللي بتمنع.

---

## ٦. الإعدادات: عامة ومؤسسة

| المجموعة | المكان | التخزين | القارئ |
|---|---|---|---|
| العامة كلها (عام، مظهر، تخزين، بريد، أمان، إشعارات) | `SettingsCluster` في `/central` | spatie settings (مركزي) | كل التطبيق كقيمة افتراضية |
| مظهر المؤسسة (الخط، السماح بتبديل الثيم) | `TenantSettingsCluster` في `/admin/t/{slug}` | `tenant_settings` | `AdminPanelProvider` عبر `TenantSettings` |
| قنوات إشعارات المؤسسة | نفس الـ cluster | `tenant_settings` | `NotificationChannelResolver` عبر `TenantSettings` |
| لون العلامة | `EditOrganization` | `tenants.primary_color` | `ThemeColorResolver` |

- `settings.*` اتشالت من دور `admin` بتاع المؤسسة؛ فاضل له `manage_appearance`
  و`manage_notifications` بس — وبيكتبوا في `tenant_settings`.
- الكتابة عبر `UpdateTenantSettingsAction` ← `TenantSettingPolicy::updateGroup()`:
  المجموعة لازم تكون في `TENANT_EDITABLE_GROUPS` — قاعدة سلامة بتسري على المدير العام.
- **كل حقل في صفحة مؤسسة ليه قارئ.** الثيم الافتراضي مش في صفحة المؤسسة لأن
  `defaultThemeMode()` بيتقيّم وقت بناء اللوحة قبل ما المؤسسة تتعرف — حقل
  بيتحفظ ومحدش بيقراه نفس فخ «الإخفاء UX».

---

## ٧. أدوات النظام

`SystemHealth` و`Backups` و`LogViewer` وروابط Horizon وPulse اتنقلوا لـ `/central`
(`Audit/Presentation/Filament/Central/Pages`). `ActivityLogResource` مسجّل في
اللوحتين: في المؤسسة مفلتر بها، وفي المركزية (من غير سياق) بيعرض الكل.
الدعم الفني معاه `access.system` عشان صلاحية سجل النشاط تبقى فعّالة
(`ActivityLogPolicy::area()`)، وصفحات الإعدادات لسه 403 عليه.

سجل النشاط مركزي في الوضعين — عشان يعيش بعد حذف قاعدة مؤسسة.

---

## ٨. معايير القبول

- [x] `/central` لوحة منفصلة من غير مؤسسة في الرابط، ودخولها بـ `access.panel.central` — `TenantAcceptanceTest`
- [x] مستخدم مؤسسة (حتى `admin`) مايقدرش يفتح `/central` — 403 — `TenantAcceptanceTest` + `NavigationReachabilityTest`
- [x] كل لوحة بتضبط سياقها قبل فحص الدخول (أول طلب في عامل جديد) — `TenantAcceptanceTest`
- [x] الأدوار المركزية على الفريق `0`، ودور مؤسسة مابيدّيش صلاحية مركزية — `CentralRolesTest`
- [x] إنشاء مؤسسة من `/central` بيجهّزها كاملة في الوضعين — وفي multi القاعدة موجودة ومتهجّرة — `TenantAcceptanceTest`
- [x] الحذف الناعم مابيمسحش القاعدة، والنهائي بيمسحها — `TenantAcceptanceTest` (multi)
- [x] المؤسسة الموقوفة بتختفي من التبديل، ورابطها المباشر بيعرض صفحة الإيقاف — `TenantAcceptanceTest`
- [x] الانتحال من `/central` بيدخل لوحة المؤسسة، وبيتسجّل، والرجوع لـ `/central` — `TenantAcceptanceTest`
- [ ] حد الخطة بيتفرض في الـ Policy، وله رسالة مترجمة ar + en
- [ ] شاشة فريق التشغيل في `/central`: دعوة، إسناد `super_admin`/`support`، 2FA إجباري — مؤجّلة مع بند ٥
- [x] مدير المؤسسة مايقدرش يفتح إعدادات البريد أو التخزين أو الأمان — 403 — `SettingsAcceptanceTest`
- [x] مظهر المؤسسة وإشعاراتها بيتكتبوا للمؤسسة دي بس وليهم قارئ — `TenantSettingsAcceptanceTest`
- [x] أدوات النظام في `/central` بس — `NavigationReachabilityTest`
- [x] كل شاشة مركزية ليها جولة واختبار قبول ورحلة مستخدم (`docs/17` بند ١٣) — `OnboardingCoverageTest` + `FeatureTestCoverageTest` + `Central/ScreensJourneyTest` + `Central/TenantJourneyTest`
