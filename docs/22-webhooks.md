# ٢٢ — الـ Webhooks: الاستقبال والإرسال

> **الحالة:** وصفة معتمدة — **مش منفّذة في القالب**. أول مشروع يحتاج تكامل
> خارجي (بوابة دفع، رسائل، CRM) ينفّذها بالشكل ده بالظبط، ويحدّث
> `PRODUCTION-READINESS.md` بالدليل.
>
> الباكدجين المقترحين متأكدين إنهم بيقبلوا Laravel 13 (`illuminate/* ^13.0`):
> `spatie/laravel-webhook-client` **3.11** و`spatie/laravel-webhook-server` **3.7**.
> إضافتهم محتاجة مناقشة زي أي اعتمادية (`docs/18-conventions.md` بند ٦).

## المشكلة

الـ webhook هو **مسار عام من غير تسجيل دخول، بيغيّر بيانات**. يعني هو أخطر
مدخل في النظام بعد شاشة الدخول — وغالباً بيتكتب في نص ساعة كـ controller
بيعمل `json_decode` ويحدّث الطلب.

كل الأخطاء المشهورة هنا صامتة: توقيع مش متحقَّق منه، نفس الحدث بيتنفّذ
مرتين، المؤسسة جاية من الـ payload، أو رابط إرسال بيشاور على
`169.254.169.254` وبيسرّب مفاتيح السيرفر.

---

## ١. المبادئ

1. **الـ payload عدائي لحد ما التوقيع يثبت العكس.** مفيش قراءة ولا تخزين
   ولا لوج لمحتواه قبل التحقق.
2. **الاستقبال ≠ المعالجة.** المسار بيتحقق ويخزّن ويرد `200` في أقل من ثانية.
   الشغل كله في الطابور.
3. **كل حدث بيتنفّذ مرة واحدة.** المرسل هيعيد الإرسال — ده مضمون، مش احتمال.
4. **المؤسسة من الـ endpoint، مش من الـ payload.** أي `tenant_id` جوه
   البيانات بيتجاهل.
5. **الإرسال بيتحكم فيه المستخدم** — يعني الرابط نفسه مدخل عدائي (SSRF).
6. **منطق الأعمال في Application Action** — الـ webhook مجرد طريقة تانية
   لاستدعاءه، زي زرار في Filament بالظبط.

---

## ٢. مكانها في البنية

سياق جديد اسمه `Integrations` — الـ webhooks بتخدم كل السياقات، ومحدش فيهم
يملكها:

```
src/Contexts/Integrations/
├── Domain/
│   ├── Models/          WebhookEndpoint · WebhookDelivery   (BelongsToTenant)
│   ├── Enums/           WebhookProvider · DeliveryStatus
│   └── Events/          InboundWebhookReceived
├── Application/
│   ├── Actions/         RegisterEndpointAction · RotateSecretAction · ReplayDeliveryAction
│   └── DTOs/            WebhookEnvelope
├── Infrastructure/
│   ├── Webhooks/Inbound/    Validators · Profiles · ProcessInboundWebhookJob
│   ├── Webhooks/Outbound/   Listeners · SafeUrlGuard
│   └── Policies/            WebhookEndpointPolicy · WebhookDeliveryPolicy
├── Presentation/
│   └── Filament/Resources/  WebhookEndpointResource (بشاشة سجل الإرسال)
├── Routes/webhooks.php
└── IntegrationsServiceProvider.php
```

وزي أي سياق جديد: تسجيله في `bootstrap/providers.php`، ومسار ميجريشناته في
`phpstan.neon`. الاختبار المعماري هيفكّرك لو نسيت.

**السياقات بتتكلم بالأحداث بس.** بوابة الدفع مثلاً: الـ job بتاع
`Integrations` بيطلق `InboundWebhookReceived`، ومستمع في سياق `Billing`
بينادي الـ Action بتاعه. `Integrations` مابيعرفش موديلات `Billing`.

---

## ٣. الاستقبال — الترتيب الإلزامي

```
الطلب ← المسار (من غير جلسة، throttle) ← التحقق من التوقيع ← الطابع الزمني
      ← منع التكرار ← تخزين ← 200 ← Job (بسياق المؤسسة) ← حدث ← Action
```

### المسار

```php
// Routes/webhooks.php — بره ستاك اللوحة وبره `web`
Route::middleware(['throttle:webhooks'])
    ->prefix('webhooks')
    ->group(function (): void {
        Route::webhooks('stripe', 'stripe');
        // مؤسسة لكل endpoint: السر مربوط بالـ uuid مش بإعداد عام
        Route::webhooks('incoming/{endpoint:uuid}', 'tenant-endpoints');
    });
```

- **مش في ستاك `web`:** مفيش جلسة ولا كوكيز ولا `SetLocale`. لو اتسجّل
  هناك، استثنيه من CSRF في `bootstrap/app.php` بـ
  `validateCsrfTokens(except: ['webhooks/*'])` — **المسار ده بس**، مش wildcard أوسع.
- **`throttle:webhooks`** معرّف في `AppServiceProvider` بقيمة من `config/webhooks.php`.

### التوقيع

```php
final class HmacSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = (string) $request->header($config->signatureHeaderName);
        $timestamp = (int) $request->header((string) config('webhooks.timestamp_header'));

        if ($signature === '' || $timestamp === 0) {
            return false;
        }

        // منع إعادة الإرسال: توقيع صحيح بس قديم = مرفوض
        if (abs(now()->timestamp - $timestamp) > (int) config('webhooks.tolerance_seconds')) {
            return false;
        }

        // endpoint لكل مؤسسة = سر لكل مؤسسة؛ السر العام للمزوّدين الثابتين بس
        $secret = $request->route('endpoint')?->secret ?? $config->signingSecret;

        // على الـ body الخام — مش على JSON متعاد ترميزه
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $signature);     // مقارنة ثابتة الزمن — مش ===
    }
}
```

- **لو المزوّد عنده مكتبة تحقق رسمية** (Stripe مثلاً) — استخدمها جوه الـ
  validator. ماتعيدش كتابة مخطط توقيع حد تاني.
- **السر في `config/`** اللي بيقرا `env()` — أو مشفّر على `WebhookEndpoint`
  للـ endpoints اللي لكل مؤسسة. مفيش سر في الكود.
- **الرفض بـ 403 من غير تفاصيل.** ماتقولش «التوقيع غلط» ولا «الطابع قديم».

### منع التكرار

```php
// ميجريشن جدول webhook_calls (بتاع الباكدج) + عمود معرّف المزوّد
$table->string('external_id')->nullable();
$table->unique(['name', 'external_id']);
```

- الـ `WebhookProfile` بيرجّع `false` لو `external_id` اتسجّل قبل كده —
  الطلب بياخد `200` (المرسل يبطّل يعيد) ومايتعالجش تاني.
- **الـ unique constraint هو الضمان**، مش الفحص قبل الإدخال. طلبين في نفس
  اللحظة هيعدّوا الفحص الاتنين.

### المعالجة

```php
final class ProcessInboundWebhookJob extends ProcessWebhookJob
{
    public function handle(TenantContext $context): void
    {
        // الاستعلام الوحيد من غير مؤسسة — عشان نعرف المؤسسة نفسها
        $endpoint = $context->withoutScope(fn (): WebhookEndpoint => WebhookEndpoint::query()
            ->whereKey($this->webhookCall->endpoint_id)
            ->firstOrFail());

        // المؤسسة من الـ endpoint — أي tenant_id جوه الـ payload مالوش قيمة
        $context->forTenant($endpoint->tenant_id, function () use ($endpoint): void {
            event(new InboundWebhookReceived(
                WebhookEnvelope::fromCall($this->webhookCall, $endpoint),
            ));
        });
    }
}
```

> ⚠️ الـ jobs مابتشيلش سياق المؤسسة (`ContextAwareJob`). الـ job ده بيبدأ
> من غير مؤسسة **عن قصد** — ويحددها من الـ endpoint قبل أي استعلام تاني.
> `endpoint_id` عمود بيتضاف لجدول `webhook_calls` في الـ Profile وقت الاستقبال.

- **الـ Action اللي بيتنادى ماعندوش مستخدم.** التفويض هنا = التوقيع + ربط
  الـ endpoint بالمؤسسة. الـ Action لازم يتحقق من قواعد الأعمال بنفسه
  (الطلب موجود؟ المبلغ مطابق؟) — **ماتثقش في أي قيمة من الـ payload كحقيقة**.
  لو فيه مبلغ أو حالة، اسأل الـ API بتاع المزوّد وخد القيمة منه.
- **الفشل بيتعاد من الطابور**، مش من المرسل. الـ job عليه `tries` و`backoff`.

---

## ٤. الإرسال — لمؤسسات بتستقبل أحداث من النظام

### المصدر

```
Domain Event ← Listener (Infrastructure) ← Job لكل endpoint مشترك ← HTTP
```

**مش من Filament Resource ولا Action مباشرةً.** الـ Action بيطلق حدث،
والمستمع بيقرر مين مشترك. كده حذف ميزة الـ webhooks مابيلمسش منطق الأعمال.

### الشكل

```json
{
  "id": "evt_01J9...",
  "type": "announcement.published",
  "version": 1,
  "occurred_at": "2026-09-13T10:15:00Z",
  "data": { "id": 812, "title": "..." }
}
```

- **`data` من DTO**، مش `$model->toArray()` — عمود جديد في الجدول مايتسرّبش
  لطرف تالت من غير قرار.
- **`version`** — تغيير الشكل = نسخة جديدة، والقديمة بتفضل لحد ما المشتركين يتنقلوا.
- **أقل بيانات شخصية ممكنة.** المستقبِل يقدر يطلب التفاصيل بالـ API لو محتاجها.

### الإرسال

```php
WebhookCall::create()
    ->url($endpoint->url)
    ->payload($envelope->toArray())
    ->useSecret($endpoint->secret)                 // مشفّر على الموديل
    ->withHeaders(['X-FC-Event-Id' => $envelope->id])
    ->timeoutInSeconds((int) config('webhooks.outbound.timeout_seconds'))
    ->maximumTries((int) config('webhooks.outbound.max_tries'))
    ->onQueue((string) config('webhooks.outbound.queue'))
    ->meta(['endpoint_id' => $endpoint->getKey()])
    ->dispatch();
```

- **Backoff أُسّي** (الباكدج فيه `ExponentialBackoffStrategy`) — مش إعادة كل دقيقة.
- **طابور منفصل** — endpoint بطيء عند عميل مايأخّرش إشعارات النظام.
- **تعطيل تلقائي** بعد عدد فشل متتالي من الكونفيج + إشعار لمدير المؤسسة.
- **كل محاولة في `WebhookDelivery`**: الحالة، كود الرد، المدة — **مش** body
  الرد كامل (ممكن يكون ضخم أو فيه بيانات). وبيتمسح بجدولة `model:prune`.

### حماية SSRF — إلزامية

المستخدم بيكتب الرابط، والسيرفر بيطلبه من جوه الشبكة. من غير حماية، الرابط
`http://169.254.169.254/latest/meta-data/` بيرجّع مفاتيح AWS في سجل الإرسال.

```php
final class SafeUrlGuard
{
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' && ! app()->isLocal()) {
            throw new UnsafeWebhookUrl('scheme');
        }

        foreach (gethostbynamel((string) ($parts['host'] ?? '')) ?: [] as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new UnsafeWebhookUrl('private_address');
            }
        }
    }
}
```

- **وقت الحفظ ووقت الإرسال** — الاتنين. DNS ممكن يتغيّر بعد الحفظ (DNS rebinding).
- **IPv6 كمان** — `gethostbynamel()` بيرجّع IPv4 بس؛ استخدم `dns_get_record()` للـ AAAA.
- **مفيش متابعة redirects** — redirect لعنوان داخلي بيتخطّى الفحص.
- **منافذ مسموحة** (443، و80 في التطوير) من الكونفيج.

### السر

- بيتولّد بـ `Str::random(64)` جوه `RegisterEndpointAction` — **مش حقل في الفورم**.
- **بيظهر مرة واحدة** بعد الإنشاء، وبعدها مخفي للأبد.
- **التدوير بفترة تداخل:** بعد `RotateSecretAction`، الإرسال بيوقّع بالسرّين
  لمدة من الكونفيج — عشان المستقبِل يلحق يحدّث من غير ما يرفض أحداث.

---

## ٥. الصلاحيات والواجهة

```php
// config/authorization.php
'webhook_endpoints' => [
    'group' => 'system',
    'actions' => ['view_any', 'view', 'create', 'update', 'delete'],
    'extra' => ['rotate_secret', 'send_test', 'replay'],
],
'webhook_deliveries' => [
    'group' => 'system',
    'actions' => ['view_any', 'view'],
],
```

- ترجمة كل صلاحية `ar` و`en`، و`authorization:sync`.
- `WebhookEndpointPolicy`: الصلاحية + `ruleOrNotFound` على المؤسسة +
  قاعدة «الـ endpoint المعطّل مايتبعتلوش test».
- `replay` قاعدة أعمال: الإعادة مسموحة لـ `failed` بس — إعادة حدث نجح
  بتعمل تكرار عند المستقبِل.
- كل أزرار الشاشة `->authorize()` — «إرسال تجريبي» و«تدوير السر» و«إعادة» كلهم.

---

## ٦. التسجيل والأسرار

- **`logging.redact`** يتضاف له: هيدر التوقيع، `secret`، وأي هيدر `Authorization`.
- **الـ payload مايدخلش اللوج العادي.** مكانه جدول `webhook_calls` بمدة احتفاظ.
- `delete_after_days` في `config/webhook-client.php` + `model:prune` في
  `routes/console.php` — **بـ `--model` صريح**، لأن موديلات الباكدجات مش
  بتتكتشف تلقائياً.
- كل تدوير سر وكل تعطيل تلقائي بيتسجّل في سجل النشاط تحت `security`.

---

## ٧. الاختبارات

| الاختبار | بيثبت إيه |
|---|---|
| توقيع غلط ← 403 ومفيش صف في `webhook_calls` | التحقق قبل التخزين |
| توقيع صحيح بطابع أقدم من الحد ← 403 | منع إعادة الإرسال |
| نفس `external_id` مرتين ← Job واحد | منع التكرار |
| `tenant_id` مزوّر في الـ payload ← الحدث بمؤسسة الـ endpoint | المؤسسة من الـ endpoint |
| `Queue::fake()` ← الرد `200` قبل المعالجة | الاستقبال ≠ المعالجة |
| رابط `http://127.0.0.1` و`10.0.0.5` و`169.254.169.254` ← مرفوض عند الحفظ والإرسال | SSRF |
| `Http::fake()` ← الهيدرز فيها توقيع صحيح بيتحقق بالسر | الإرسال موقّع |
| مصفوفة أدوار لـ `WebhookEndpointResource` | `docs/17` |
| السر مش موجود في HTML صفحة التعديل | السر بيظهر مرة واحدة |

---

## ٨. معايير القبول

- [ ] مسار الاستقبال بره ستاك اللوحة، ومستثنى من CSRF بمساره بس
- [ ] التوقيع بيتحقق على الـ body الخام بـ `hash_equals` قبل أي تخزين أو لوج
- [ ] طابع زمني بحد مسموح من الكونفيج
- [ ] unique constraint على معرّف الحدث — مش فحص قبل الإدخال بس
- [ ] الرد `200` فوري والمعالجة في الطابور
- [ ] المؤسسة من الـ endpoint، واختبار بيثبت إن `tenant_id` في الـ payload متجاهَل
- [ ] منطق الأعمال في Action بيتنادى من مستمع حدث — مش في الـ job أو الـ controller
- [ ] الإرسال من مستمع لحدث دومين، والـ payload من DTO بـ `version`
- [ ] حماية SSRF وقت الحفظ ووقت الإرسال، IPv4 وIPv6، من غير redirects
- [ ] السر مولّد في Action، بيظهر مرة واحدة، مشفّر، وتدويره بفترة تداخل
- [ ] إعادة بـ backoff أُسّي، طابور منفصل، وتعطيل تلقائي بعد فشل متكرر
- [ ] كل الصلاحيات في `config/authorization.php` بترجمتها، وكل زرار بـ `->authorize()`
- [ ] هيدرز التوقيع والأسرار في `logging.redact`، والـ payloads بتتمسح بجدولة
