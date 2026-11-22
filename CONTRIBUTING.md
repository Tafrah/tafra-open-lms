# المساهمة في Tafra Open LMS

شكراً إنك عايز تساعد! المنصة مجانية ومفتوحة المصدر (AGPL-3.0) لكل مدرّس.

## بلّغ عن مشكلة

- افتح [Issue](https://github.com/Tafrah/tafra-open-lms/issues) واكتب: إصدارك (`VERSION` أو الشريط في اللوحة)، نوع الاستضافة (مشتركة / VPS / Coolify)، الخطوات اللي عملتها، واللي حصل مقابل اللي كنت مستنيه.
- لو فيه رسالة خطأ: آخر سطور `storage/logs/*.log` (امسح منها أي كلمة سر أو رقم موبايل).
- **ثغرة أمنية؟ ماتفتحش Issue عام.** استخدم [Report a vulnerability](https://github.com/Tafrah/tafra-open-lms/security/advisories/new) في تبويب Security.

## ابعت تعديل (Pull Request)

1. اقرأ `CLAUDE.md` و`docs/18-conventions.md` و`docs/01-architecture-ddd.md` و`docs/19-policies.md` — البنية (DDD) والصلاحيات (Policies) ليها قواعد ثابتة.
2. `composer setup` بيجهّز كل حاجة محلياً على MySQL، و`composer dev` بيشغّل المنصة.
3. أي ميزة جديدة لازم يكون ليها:
   - اختبارات Pest (فيتشر + صلاحيات)، والنصوص عربي وإنجليزي في ملفات `Lang/` بتاعة الـ Context.
   - تشتغل على **MySQL** وعلى **استضافة مشتركة** (مفيش Redis، مفيش عامل طابور دايم، مفيش أوامر shell).
   - أي تغيير في القاعدة = migration جديدة (ماتعدّلش migration قديمة — التحديث التلقائي بيطبّق الجديد بس).
4. قبل الـ PR:
   ```bash
   vendor/bin/pint && vendor/bin/phpstan analyse && composer test && composer test:browser
   ```
5. الـ PR فيه وصف بالعربي أو الإنجليزي + لقطة شاشة لأي تغيير في الواجهة.

## الرخصة

بإرسالك لأي مساهمة، إنت موافق إنها تتنشر تحت نفس رخصة المشروع ([AGPL-3.0](LICENSE)). اسم «طفرة» واللوجو علامة تجارية ومش جزء من الرخصة.
