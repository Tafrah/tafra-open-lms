<?php

declare(strict_types=1);

return [
    'enums' => [
        'payment_method_type' => [
            'vodafone_cash' => 'فودافون كاش',
            'instapay' => 'إنستاباي',
            'bank_transfer' => 'تحويل بنكي',
            'other' => 'أخرى',
        ],
        'invoice_status' => [
            'pending' => 'مستنية مراجعة',
            'paid' => 'مدفوعة',
            'rejected' => 'مرفوضة',
            'canceled' => 'ملغية',
        ],
        'receipt_status' => [
            'pending' => 'مستني مراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
        ],
        'payment_gateway' => [
            'manual_transfer' => 'تحويل بإيصال',
            'wallet' => 'المحفظة',
            'recharge_card' => 'كود شحن',
            'free' => 'مجاني',
        ],
        'coupon_type' => [
            'percentage' => 'نسبة مئوية',
            'fixed' => 'مبلغ ثابت',
        ],
        'coupon_scope' => [
            'all' => 'كل الكورسات',
            'specific' => 'كورسات محددة',
        ],
        'recharge_card_type' => [
            'wallet' => 'رصيد محفظة',
            'bootcamp' => 'كارت محاضرة',
        ],
        'transaction_type' => [
            'deposit' => 'إيداع',
            'withdraw' => 'سحب',
        ],
        'transaction_source' => [
            'recharge_card' => 'كود شحن',
            'purchase' => 'شراء كورس',
            'admin' => 'من الإدارة',
            'refund' => 'استرداد',
        ],
        'enrollment_source' => [
            'receipt' => 'إيصال تحويل',
            'wallet' => 'المحفظة',
            'recharge_card' => 'كود شحن',
            'admin' => 'من الإدارة',
            'free' => 'مجاني',
        ],
        'enrollment_type' => [
            'enroll' => 'اشتراك',
            'unenroll' => 'إلغاء اشتراك',
        ],
    ],
    'errors' => [
        'invalid_amount' => 'المبلغ لازم يكون أكبر من صفر.',
        'insufficient_balance' => 'رصيدك مش كفاية (رصيدك :balance جنيه).',
        'coupon_invalid' => 'الكوبون ده مش صحيح.',
        'coupon_not_started' => 'الكوبون ده لسه مابدأش.',
        'coupon_expired' => 'الكوبون ده انتهى.',
        'coupon_exhausted' => 'الكوبون ده خلص عدد استخداماته.',
        'coupon_already_used' => 'إنت استخدمت الكوبون ده قبل كده.',
        'coupon_not_for_course' => 'الكوبون ده مش على الكورس ده.',
        'course_unavailable' => 'الكورس ده مش متاح للشراء دلوقتي.',
        'student_blocked' => 'حسابك موقوف — كلّم المدرس.',
        'already_enrolled' => 'إنت مشترك في الكورس ده بالفعل.',
        'use_redeem_for_cards' => 'أكواد الشحن بتتكتب من صفحة المحفظة.',
        'invoice_not_yours' => 'الفاتورة دي مش بتاعتك.',
        'invoice_closed' => 'الفاتورة دي اتراجعت خلاص.',
        'invoice_not_manual' => 'الفاتورة دي مش دفع بالتحويل.',
        'method_inactive' => 'طريقة الدفع دي مش متاحة.',
        'invoice_broken' => 'الفاتورة دي ناقصها بيانات (الطالب أو الكورس اتحذف).',
        'batch_size' => 'عدد الأكواد لازم يكون من ١ لـ :max.',
        'card_needs_course' => 'اختار المحاضرة اللي الكارت هيفتحها.',
        'card_invalid' => 'الكود ده مش صحيح.',
        'card_used' => 'الكود ده اتستخدم قبل كده.',
        'card_expired' => 'الكود ده انتهت صلاحيته.',
        'too_many_attempts' => 'محاولات كتير غلط. استنى :seconds ثانية وجرّب تاني.',
    ],
];
