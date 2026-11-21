<?php

declare(strict_types=1);

return [
    'enums' => [
        'payment_method_type' => [
            'vodafone_cash' => 'Vodafone Cash',
            'instapay' => 'InstaPay',
            'bank_transfer' => 'Bank transfer',
            'other' => 'Other',
        ],
        'invoice_status' => [
            'pending' => 'Awaiting review',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
            'canceled' => 'Canceled',
        ],
        'receipt_status' => [
            'pending' => 'Awaiting review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'payment_gateway' => [
            'manual_transfer' => 'Transfer with receipt',
            'wallet' => 'Wallet',
            'recharge_card' => 'Recharge card',
            'free' => 'Free',
        ],
        'coupon_type' => [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed amount',
        ],
        'coupon_scope' => [
            'all' => 'All courses',
            'specific' => 'Specific courses',
        ],
        'recharge_card_type' => [
            'wallet' => 'Wallet credit',
            'bootcamp' => 'Course card',
        ],
        'transaction_type' => [
            'deposit' => 'Deposit',
            'withdraw' => 'Withdrawal',
        ],
        'transaction_source' => [
            'recharge_card' => 'Recharge card',
            'purchase' => 'Course purchase',
            'admin' => 'By admin',
            'refund' => 'Refund',
        ],
        'enrollment_source' => [
            'receipt' => 'Transfer receipt',
            'wallet' => 'Wallet',
            'recharge_card' => 'Recharge card',
            'admin' => 'By admin',
            'free' => 'Free',
        ],
        'enrollment_type' => [
            'enroll' => 'Enrolled',
            'unenroll' => 'Unenrolled',
        ],
    ],
    'errors' => [
        'invalid_amount' => 'The amount must be greater than zero.',
        'insufficient_balance' => 'Not enough balance (your balance is :balance EGP).',
        'coupon_invalid' => 'This coupon is not valid.',
        'coupon_not_started' => 'This coupon has not started yet.',
        'coupon_expired' => 'This coupon has expired.',
        'coupon_exhausted' => 'This coupon has no uses left.',
        'coupon_already_used' => 'You have already used this coupon.',
        'coupon_not_for_course' => 'This coupon does not apply to this course.',
        'course_unavailable' => 'This course is not available for purchase right now.',
        'student_blocked' => 'Your account is suspended — contact the teacher.',
        'already_enrolled' => 'You are already enrolled in this course.',
        'use_redeem_for_cards' => 'Recharge codes are entered from the wallet page.',
        'invoice_not_yours' => 'This invoice is not yours.',
        'invoice_closed' => 'This invoice has already been reviewed.',
        'invoice_not_manual' => 'This invoice is not a transfer payment.',
        'method_inactive' => 'This payment method is not available.',
        'invoice_broken' => 'This invoice is missing data (the student or course was deleted).',
        'batch_size' => 'The number of codes must be between 1 and :max.',
        'card_needs_course' => 'Choose the course this card unlocks.',
        'card_invalid' => 'This code is not valid.',
        'card_used' => 'This code has already been used.',
        'card_expired' => 'This code has expired.',
        'too_many_attempts' => 'Too many wrong attempts. Wait :seconds seconds and try again.',
    ],
];
