<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Application\Schema;

use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Exams\Domain\Models\Choice;
use Src\Contexts\Exams\Domain\Models\Question;

/**
 * عقد التصدير والاستيراد مع طفرة (docs/transfer/format.md).
 *
 * كل جدول عندنا ← ملف NDJSON باسم جدول طفرة. المفاتيح الأجنبية متعرّفة هنا
 * عشان الاستيراد يعيد ترقيم الـ IDs (مهم: الاستيراد ممكن يكون لأكاديمية فيها بيانات).
 *
 * الطالب عندنا صف واحد في `students`؛ في طفرة صف في `users` + صف في `students`.
 * التصدير بيقسمه: `users.id` للطالب = `student.id + STUDENT_USER_OFFSET`،
 * فمفيش تصادم مع مستخدمي اللوحة.
 */
final class TafraSchemaMap
{
    public const FORMAT = 'tafra-academy-export';

    public const FORMAT_VERSION = 1;

    public const STUDENT_USER_OFFSET = 1_000_000;

    /**
     * ترتيب الجداول = ترتيب الاستيراد (الأب قبل الابن).
     * source: جدولنا · file: اسم ملف طفرة · fk: عمود ← الجدول (بأسماء ملفات طفرة)
     *
     * @return array<string, array{source: string, fk: array<string, string>, soft?: bool}>
     */
    public static function tables(): array
    {
        return [
            'educational_levels' => ['source' => 'educational_levels', 'fk' => [], 'soft' => true],
            'online_lessons' => ['source' => 'online_lessons', 'fk' => ['user_id' => 'users'], 'soft' => true],
            'online_exams' => ['source' => 'online_exams', 'fk' => ['user_id' => 'users', 'educational_level_id' => 'educational_levels'], 'soft' => true],
            'bootcamps' => ['source' => 'bootcamps', 'fk' => ['user_id' => 'users', 'educational_level_id' => 'educational_levels'], 'soft' => true],
            'bootcamp_sections' => ['source' => 'bootcamp_sections', 'fk' => ['bootcamp_id' => 'bootcamps', 'prerequisite_exam_id' => 'online_exams']],
            'bootcamp_section_items' => ['source' => 'bootcamp_section_items', 'fk' => [
                'bootcamp_section_id' => 'bootcamp_sections', 'online_lesson_id' => 'online_lessons',
                'online_exam_id' => 'online_exams', 'prerequisite_exam_id' => 'online_exams',
            ]],
            'questions' => ['source' => 'questions', 'fk' => ['user_id' => 'users', 'online_exam_id' => 'online_exams']],
            'choices' => ['source' => 'choices', 'fk' => ['question_id' => 'questions']],
            'essay_question' => ['source' => 'essay_question', 'fk' => ['question_id' => 'questions']],
            'students' => ['source' => 'students', 'fk' => ['educational_level_id' => 'educational_levels'], 'soft' => true],
            'payment_methods' => ['source' => 'payment_methods', 'fk' => [], 'soft' => true],
            'coupons' => ['source' => 'coupons', 'fk' => ['created_by' => 'users'], 'soft' => true],
            'coupon_items' => ['source' => 'coupon_items', 'fk' => ['coupon_id' => 'coupons', 'item_id' => 'bootcamps']],
            'payment_invoices' => ['source' => 'payment_invoices', 'fk' => [
                'user_id' => 'students', 'bootcamp_id' => 'bootcamps', 'coupon_id' => 'coupons', 'reviewed_by' => 'users',
            ], 'soft' => true],
            'payment_receipts' => ['source' => 'payment_receipts', 'fk' => [
                'payment_invoice_id' => 'payment_invoices', 'user_id' => 'students', 'payment_method_id' => 'payment_methods',
            ]],
            'recharge_card_groups' => ['source' => 'recharge_card_groups', 'fk' => ['bootcamp_id' => 'bootcamps', 'creator_id' => 'users']],
            'recharge_cards' => ['source' => 'recharge_cards', 'fk' => [
                'recharge_card_group_id' => 'recharge_card_groups', 'bootcamp_id' => 'bootcamps', 'used_by' => 'students',
            ]],
            'money_transactions' => ['source' => 'money_transactions', 'fk' => ['user_id' => 'students', 'created_by' => 'users']],
            'coupon_redemptions' => ['source' => 'coupon_redemptions', 'fk' => [
                'coupon_id' => 'coupons', 'user_id' => 'students', 'invoice_id' => 'payment_invoices',
            ]],
            'bootcamp_enrolled_students_histories' => ['source' => 'bootcamp_enrolled_students_histories', 'fk' => [
                'bootcamp_id' => 'bootcamps', 'user_id' => 'students', 'payment_invoice_id' => 'payment_invoices',
                'recharge_card_id' => 'recharge_cards', 'enrolled_by' => 'users',
            ]],
            'section_item_views' => ['source' => 'section_item_views', 'fk' => ['bootcamp_section_item_id' => 'bootcamp_section_items', 'user_id' => 'students']],
            'online_passed_exams' => ['source' => 'online_passed_exams', 'fk' => [
                'user_id' => 'students', 'online_exam_id' => 'online_exams', 'bootcamp_section_item_id' => 'bootcamp_section_items',
            ]],
            'student_exam_answers' => ['source' => 'student_exam_answers', 'fk' => [
                'online_passed_exam_id' => 'online_passed_exams', 'user_id' => 'students', 'question_id' => 'questions', 'graded_by' => 'users',
            ]],
        ];
    }

    /**
     * أعمدة بأسماء مختلفة: عمودنا ← عمود طفرة (لكل جدول).
     *
     * @return array<string, array<string, string>>
     */
    public static function renames(): array
    {
        return [
            'payment_invoices' => ['student_id' => 'user_id'],
            'payment_receipts' => ['student_id' => 'user_id'],
            'money_transactions' => ['student_id' => 'user_id'],
            'coupon_redemptions' => ['student_id' => 'user_id', 'payment_invoice_id' => 'invoice_id'],
            'bootcamp_enrolled_students_histories' => ['student_id' => 'user_id'],
            'section_item_views' => ['student_id' => 'user_id'],
            'online_passed_exams' => ['student_id' => 'user_id'],
            'student_exam_answers' => ['student_id' => 'user_id'],
        ];
    }

    /**
     * أنواع الـ morph بأسماء محايدة (مش أسماء كلاسات PHP) — طفرة بتترجمها لأسمائها.
     *
     * @return array<string, class-string>
     */
    public static function morphAliases(): array
    {
        return [
            'recharge_card' => RechargeCard::class,
            'payment_invoice' => PaymentInvoice::class,
        ];
    }

    /**
     * الملفات (spatie media) اللي بتتصدّر: جدول ← [موديل، مجموعات].
     *
     * @return array<string, array{model: class-string, collections: list<string>}>
     */
    public static function media(): array
    {
        return [
            'bootcamps' => ['model' => Bootcamp::class, 'collections' => ['thumbnail']],
            'online_lessons' => ['model' => OnlineLesson::class, 'collections' => ['documents']],
            'questions' => ['model' => Question::class, 'collections' => ['image', 'explanation_image']],
            'choices' => ['model' => Choice::class, 'collections' => ['choice_image']],
            'payment_receipts' => ['model' => PaymentReceipt::class, 'collections' => ['receipt']],
        ];
    }
}
