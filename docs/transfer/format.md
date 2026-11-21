# صيغة نقل الأكاديمية — `tafra-academy-export` (الإصدار 1)

العقد بين **المنصة المجانية** و**طفرة** (Tafra-ESS-Laravel). أي تغيير في الصيغة = رقم إصدار جديد،
والطرفين بيرفضوا ملف أحدث من اللي بيفهموه.

الكود: `src/Contexts/Transfer` — الخريطة كلها في `Application/Schema/TafraSchemaMap.php`،
واختبار الذهاب والعودة في `Tests/Feature/RoundTripTest.php`.

## الأوامر

```bash
php artisan tafra:export [--path=export.zip]       # تصدير الأكاديمية كاملة
php artisan tafra:import export.zip --dry-run      # تجربة: تقرير بالأعداد والمشاكل، مفيش حاجة بتتحفظ
php artisan tafra:import export.zip                # استيراد فعلي
```

## شكل الملف

```
tafra-export-2026-11-21.zip
├── manifest.json          # format, format_version, source, app_version, exported_at, counts, checksums (sha256 لكل ملف data)
├── settings.json          # إعدادات الأكاديمية: { group: { key: value } }  (site, appearance, …)
├── data/<table>.ndjson    # صف JSON في كل سطر — اسم الملف = اسم جدول طفرة
└── media/<table>/<id>/<collection>/<file>   # + data/media_index.ndjson بيوصف كل ملف
```

## الجداول

نفس أسماء جداول وأعمدة طفرة (tenant). `tenant_id` مابيتصدّرش. الـ IDs الأصلية موجودة والمستورد بيعيد ترقيمها.

| الملف | ملاحظات |
|---|---|
| `users` | مستخدمي اللوحة (`user_type=moderator`, **من غير كلمة سر**) + الطلبة (`user_type=student`, كلمة السر bcrypt زي ما هي). `id` الطالب = `students.id + 1000000` |
| `students` | `user_id` بيشاور على `users`. المحافظة ونوع التعليم والرصيد في `extra_info` |
| `educational_categories`, `materials`, `material_parts`, `bootcamp_categories`, `exam_categories` | صف واحد صناعي (المجاني مافيهوش الحاجات دي) — طفرة محتاجاها كمفاتيح |
| `educational_levels` | + `educational_category_id = 1` |
| `online_lessons` | النوع: `YoutubeVideo` · `Pdf` · `Text`. + `material_id = 1`. ملف الـ PDF في `media/online_lessons/<id>/documents/` |
| `bootcamps` · `bootcamp_sections` · `bootcamp_section_items` | زي طفرة. + `material_id`, `bootcamp_category_id`. الصورة في `media/bootcamps/<id>/thumbnail/` |
| `online_exams` | + `material_id`, `exam_category_id` |
| `questions` | `questionable_type` = `multiple_choices` أو `essay_question`، `questionable_id` = id السؤال. **زيادة:** `online_exam_id`, `order`, `type`, `mark` |
| `online_exam_blocks` · `online_exam_block_items` | بلوك واحد لكل امتحان (id البلوك = id الامتحان)، والعنصر `linkable_item_type = question` |
| `multiple_choices` · `choices` · `essay_question` | زي طفرة |
| `online_passed_exams` · `student_exam_answers` · `section_item_views` | `user_id` = مستخدم الطالب |
| `bootcamp_enrolled_students_histories` | `source`: `receipt` · `wallet` · `recharge_card` · `admin` · `free` |
| `payment_invoices` | `payment_gateway`: `manual_transfer` · `wallet` · `recharge_card` · `free`. `invoice_id = invoice_key = number` |
| `money_transactions` | `transactionable_type`: `recharge_card` أو `payment_invoice` (أسماء محايدة — مش كلاسات PHP) |
| `recharge_card_groups` · `recharge_cards` | **زيادة:** `type` (`wallet`/`bootcamp`) و`bootcamp_id` (كارت المحاضرة) |
| `coupons` · `coupon_items` · `coupon_redemptions` | زي طفرة (`coupon_redemptions.invoice_id`) |
| `payment_methods` · `payment_receipts` | **خاصين بالمجاني** (الدفع بالإيصال). صورة الإيصال في `media/payment_receipts/<id>/receipt/` |

مابيتصدّرش: التوكنز، الجلسات، الأجهزة، كلمات سر مستخدمي اللوحة، سجل النشاط.

## قواعد الاستيراد (عندنا — ونفس المطلوب من طفرة)

1. **التحقق قبل أي كتابة:** `format` صح، `format_version` مش أحدث من المدعوم، الـ checksums مطابقة، ومفيش مسار في الـ zip فيه `..` أو بيبدأ بـ `/`.
2. **إعادة ترقيم:** كل جدول «القديم ← الجديد»، والمفاتيح الأجنبية بتترجم بالترتيب (الأب قبل الابن).
3. **الدمج مش التكرار:** طالب بنفس الموبايل = نفس الطالب. صف بنفس الكود = نفس الصف. الرابط ورقم الفاتورة المكرر بياخدوا لاحقة. كوبون أو كود شحن مكرر بيتساب.
4. **مستخدمي اللوحة مابيتنقلوش** — أي إشارة ليهم بتروح لليوزر اللي بيستورد (أو `null`).
5. **اللي مش مدعوم بيتساب ويتقال:** نوع درس مش مدعوم (Bunny, Scorm…)، جداول مش معروفة — كل ده في تقرير التجربة.
6. **التجربة** = نفس الاستيراد جوه transaction بيترجع — التقرير حقيقي.

## المطلوب في طفرة (Tafra-ESS-Laravel)

- `AcademyExporter` / `AcademyImporter` جوه الـ tenant بنفس الصيغة (endpoints تحت `/v1/academy/admin`، أمر artisan، أداة MCP).
- شاشة في `LMS-Admin-ReactJS`: رفع ← تجربة ← تقرير ← استيراد.
- الحاجات اللي المجاني بيصدّرها ومش موجودة في طفرة لسه: الدفع بالإيصال (`payment_methods`, `payment_receipts`) وكارت المحاضرة (`recharge_cards.bootcamp_id`). لحد ما تتعمل: الفواتير المقبولة تتستورد `paid` والإيصالات مرفقات.
- اختبار ذهاب وعودة في الريبو التاني بنفس الملف ده.
