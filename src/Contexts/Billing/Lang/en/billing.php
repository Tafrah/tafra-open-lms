<?php

declare(strict_types=1);

return [
    'enums' => [
        'payment_method_type' => [
            'vodafone_cash' => 'Vodafone Cash',
            'etisalat_cash' => 'e& cash (Etisalat Cash)',
            'orange_cash' => 'Orange Cash',
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
        'already_enrolled_review' => 'The student is already enrolled in this course (bought it another way). Reject this receipt and refund the transfer or add it to their wallet.',
        'too_many_phones' => 'At most :max numbers at a time. Split them into several runs.',
        'method_limit_reached' => 'This number reached its monthly limit. Pick another payment method.',
    ],
    'invoice' => [
        'singular' => 'Invoice',
        'plural' => 'Invoices & receipts',
        'badge' => 'Receipts waiting for your review',
        'no_receipt_yet' => 'The student has not uploaded the receipt yet',
        'fraud' => [
            'column' => 'Warning',
            'tooltip' => 'This receipt looks like a receipt on another invoice — open it and check.',
            'filter' => 'Has a duplicate receipt',
            'heading' => 'Careful: this receipt may be a duplicate',
            'description' => 'This is only a warning — check these invoices before approving:',
            'signals' => [
                'same_image' => 'The same receipt image was uploaded on another invoice',
                'same_reference' => 'The same transfer reference was used on another invoice',
                'same_sender_amount_day' => 'Same sender number and amount on the same day on another invoice',
            ],
        ],
        'student_empty' => [
            'heading' => 'This student has no invoices',
            'description' => 'Their invoices show up here as soon as they buy a course or send a transfer.',
        ],
        'discount_note' => 'After a :discount EGP discount',
        'sections' => [
            'receipts' => 'Uploaded receipts',
        ],
        'filters' => [
            'has_receipt' => 'Has a receipt',
        ],
        'fields' => [
            'number' => 'Invoice number',
            'student' => 'Student',
            'bootcamp' => 'Course',
            'amount' => 'Amount',
            'gateway' => 'Payment method',
            'reject_reason' => 'Rejection reason',
        ],
        'actions' => [
            'review' => 'Review',
            'approve' => 'Approve and open the course',
            'reject' => 'Reject',
        ],
        'confirm' => [
            'approve' => 'The course opens for the student right away. Did you confirm the money arrived?',
        ],
        'notifications' => [
            'approved' => 'Receipt approved and the course is open for the student.',
            'rejected' => 'Receipt rejected — the student will see the reason.',
        ],
        'empty' => [
            'heading' => 'No invoices yet',
            'description' => 'When a student buys a course the invoice shows here — transfers wait for your review.',
        ],
    ],
    'tours' => [
        'report' => [
            'purpose' => [
                'title' => 'Revenue report',
                'body' => 'Money that came in: transfers and recharge cards. Wallet purchases are shown apart so nothing is counted twice.',
            ],
            'range' => [
                'title' => 'Date range',
                'body' => 'Pick from and to — the default is the last 12 months.',
            ],
            'stats' => [
                'title' => 'Totals',
                'body' => '“Collected” = transfers + recharge cards.',
            ],
            'months' => [
                'title' => 'Month by month',
                'body' => 'Below it: revenue per course and per payment number.',
            ],
        ],
        'invoice' => [
            'purpose' => [
                'title' => 'Invoices & receipts',
                'body' => 'Every purchase: by wallet, by code or by transfer. Transfers wait for you to check the receipt image and approve or reject.',
            ],
            'table' => [
                'title' => 'Invoices',
                'body' => 'The badge in the menu = receipts waiting for your review.',
            ],
        ],
        'method' => [
            'purpose' => [
                'title' => 'Payment methods',
                'body' => 'The numbers students transfer to: Vodafone Cash, InstaPay or a bank account. Shown on the payment page.',
            ],
            'table' => [
                'title' => 'Your methods',
                'body' => 'Inactive ones are hidden from students.',
            ],
            'create' => [
                'title' => 'New method',
                'body' => 'Add your number, the account holder name and any instructions.',
            ],
        ],
        'card_group' => [
            'purpose' => [
                'title' => 'Recharge cards',
                'body' => 'Print codes and sell them at your centre or bookshops. A code either tops up the student wallet or unlocks one course.',
            ],
            'table' => [
                'title' => 'Batches',
                'body' => 'Each batch with how many codes were used. Disable a batch if it leaks.',
            ],
            'create' => [
                'title' => 'New batch',
                'body' => 'Pick the type, count and value — codes are generated in a second.',
            ],
        ],
        'coupon' => [
            'purpose' => [
                'title' => 'Coupons',
                'body' => 'A percentage or fixed discount, on all courses or selected ones, with a usage limit and dates.',
            ],
            'table' => [
                'title' => 'Coupons',
                'body' => 'See how many times each coupon was used.',
            ],
            'create' => [
                'title' => 'New coupon',
                'body' => 'Type the code you will share (e.g. ELMOGY20).',
            ],
        ],
        'transaction' => [
            'purpose' => [
                'title' => 'Wallet transactions',
                'body' => 'Every pound in or out of student wallets: recharge code, course purchase or your adjustment.',
            ],
            'table' => [
                'title' => 'Transactions',
                'body' => 'The balance after each transaction is shown — nothing is ever deleted.',
            ],
        ],
        'enrollment' => [
            'bulk' => [
                'title' => 'Bulk activation',
                'body' => 'Paste student phone numbers (one per line) and activate or cancel a course for all of them at once.',
            ],
            'purpose' => [
                'title' => 'Enrollments',
                'body' => 'Who enrolled in what, how (receipt, wallet, code or by you) and until when.',
            ],
            'table' => [
                'title' => 'Enrollments',
                'body' => 'The latest record for a student in a course is the current state.',
            ],
        ],
    ],
    'receipt' => [
        'fields' => [
            'image' => 'Receipt image',
            'method' => 'Sent to',
            'sender_phone' => 'Sender number',
            'reference' => 'Reference',
        ],
    ],
    'method' => [
        'singular' => 'Payment method',
        'plural' => 'Payment methods',
        'help' => [
            'monthly_limit' => 'When this month’s approved total on this number reaches the limit, it is hidden from checkout until next month. Leave empty for no limit.',
        ],
        'limit_reached' => 'Monthly limit reached — hidden from students until next month.',
        'usage_unlimited' => ':used EGP (no limit)',
        'usage_of' => ':used of :limit EGP',
        'fields' => [
            'name' => 'Display name',
            'type' => 'Type',
            'account_number' => 'Number / account',
            'account_name' => 'Account holder',
            'monthly_limit' => 'Monthly limit (optional)',
            'usage' => 'Approved this month',
            'instructions' => 'Instructions for students',
            'is_active' => 'Active',
        ],
        'empty' => [
            'heading' => 'No payment methods yet',
            'description' => 'Add a Vodafone Cash or InstaPay number so students can transfer and upload the receipt.',
        ],
    ],
    'card_group' => [
        'singular' => 'Card batch',
        'plural' => 'Recharge cards',
        'fields' => [
            'name' => 'Batch name',
            'type' => 'Type',
            'count' => 'Number of codes',
            'amount' => 'Code value',
            'bootcamp' => 'Course it unlocks',
            'expiry_date' => 'Expires on',
            'value' => 'Value',
            'used' => 'Used',
            'is_active' => 'Active',
        ],
        'help' => [
            'name' => 'E.g. “Maadi centre - November” — so you know where each code was sold.',
        ],
        'actions' => [
            'print' => 'Print cards',
            'csv' => 'Download Excel (CSV)',
            'disable' => 'Disable batch',
            'enable' => 'Enable batch',
        ],
        'empty' => [
            'heading' => 'No codes yet',
            'description' => 'Generate a batch and print it — students type the code on the wallet page.',
        ],
    ],
    'card' => [
        'plural' => 'Codes',
        'fields' => [
            'code' => 'Code',
            'used_by' => 'Used by',
            'used_at' => 'Used at',
            'is_active' => 'Active',
        ],
        'filters' => [
            'used' => 'Used',
        ],
        'empty' => [
            'heading' => 'This batch is empty',
            'description' => 'There are no codes in this batch.',
        ],
        'print' => [
            'serial' => 'Card serial number (batch-position)',
            'serial_short' => 'No.',
            'title' => 'Recharge cards — :name',
            'how' => 'Go to :url → Wallet → enter the code',
        ],
    ],
    'coupon' => [
        'singular' => 'Coupon',
        'plural' => 'Coupons',
        'redemptions' => [
            'title' => 'Redemptions',
            'student' => 'Student',
            'invoice' => 'Invoice',
            'original' => 'Original price',
            'discount' => 'Discount',
            'final' => 'Paid',
            'date' => 'Date',
            'empty_heading' => 'Nobody has used this coupon yet',
            'empty_description' => 'Every purchase with this coupon shows up here with its invoice and discount.',
        ],
        'fields' => [
            'code' => 'Code',
            'name' => 'Internal note',
            'type' => 'Discount type',
            'value' => 'Discount',
            'max_discount_amount' => 'Max discount (EGP)',
            'max_redemptions' => 'Total uses',
            'max_per_user' => 'Per student',
            'applicable_type' => 'Applies to',
            'bootcamps' => 'Courses',
            'starts_at' => 'Starts',
            'expires_at' => 'Ends',
            'is_active' => 'Active',
            'redemptions' => 'Used',
            'total_discount' => 'Total discount',
        ],
        'help' => [
            'unlimited' => 'Leave empty for no limit.',
        ],
        'empty' => [
            'heading' => 'No coupons yet',
            'description' => 'Create a discount coupon and share it — students type it on the course page.',
        ],
    ],
    'transaction' => [
        'singular' => 'Transaction',
        'plural' => 'Wallet transactions',
        'fields' => [
            'student' => 'Student',
            'type' => 'Type',
            'amount' => 'Amount',
            'source' => 'Source',
            'balance_after' => 'Balance after',
            'notes' => 'Note',
            'creator' => 'By',
        ],
        'empty' => [
            'heading' => 'No transactions yet',
            'description' => 'When a student tops up or buys with the wallet the transaction shows here.',
        ],
    ],
    'enrollment' => [
        'singular' => 'Enrollment',
        'plural' => 'Enrollments',
        'bulk' => [
            'activate' => 'Activate by phone numbers',
            'activate_help' => 'Pick the course and paste student phone numbers — one per line, any format (01…, +20…, Arabic digits).',
            'activate_submit' => 'Activate',
            'cancel' => 'Cancel by phone numbers',
            'cancel_help' => 'These students lose access to the course right away. Money is not refunded automatically.',
            'cancel_submit' => 'Cancel enrollment',
            'enroll_one' => 'Enroll a student',
            'phones' => 'Phone numbers',
            'phones_help' => 'One per line — up to :max numbers at a time.',
            'charge_wallet' => 'Charge the course price to the student’s wallet',
            'charge_wallet_help' => 'Off: the course opens for free. On: students without enough balance are not activated.',
            'activate_done' => 'Course activated for :count students',
            'cancel_done' => 'Enrollment canceled for :count students',
            'result' => [
                'activated' => 'Activated (:count)',
                'canceled' => 'Canceled (:count)',
                'already_enrolled' => 'Already enrolled (:count)',
                'not_enrolled' => 'Not enrolled (:count)',
                'not_found' => 'No student with this number (:count)',
                'insufficient_balance' => 'Insufficient balance (:count)',
                'failed' => ':phone: :reason',
                'empty' => 'No valid numbers.',
            ],
        ],
        'fields' => [
            'student' => 'Student',
            'bootcamp' => 'Course',
            'type' => 'Type',
            'source' => 'Via',
            'expires_at' => 'Expires',
        ],
        'empty' => [
            'heading' => 'No enrollments yet',
            'description' => 'Enrollments are created when a student buys, or when you enroll them from the student page.',
        ],
    ],
    'report' => [
        'title' => 'Revenue report',
        'description' => 'Money that came in, by month, by course and by payment number.',
        'range' => 'Date range',
        'from' => 'From',
        'to' => 'To',
        'last_12_months' => 'Last 12 months',
        'showing' => 'From :from to :to',
        'by_month' => 'By month',
        'by_course' => 'By course',
        'by_method' => 'By payment method / number',
        'empty' => 'No paid invoices in this period.',
        'unknown_method' => 'No specific number',
        'wallet_note' => 'Spent from balance topped up earlier — not new money',
        'lines' => [
            'collected' => 'Collected',
            'transfers' => 'Approved transfers',
            'card_redemptions' => 'Recharge cards',
            'wallet_purchases' => 'Wallet purchases',
        ],
        'hints' => [
            'collected' => 'Transfers + recharge cards',
            'transfers' => 'Approved receipts',
            'card_redemptions' => 'Codes redeemed into wallets',
            'wallet_purchases' => 'Spent from balance — not in the total',
        ],
        'columns' => [
            'month' => 'Month',
            'course' => 'Course',
            'count' => 'Count',
            'total' => 'Total',
            'method' => 'Method',
        ],
    ],
    'mcp' => [
        'omitted' => [
            'money' => 'Approving or rejecting a receipt is a money decision — the teacher checks the image in the panel.',
        ],
        'exempt' => [
            'cards' => 'Recharge codes are printed money — generating and printing is panel only.',
            'transactions' => 'Wallet transactions are a financial record — panel only.',
            'enrollments' => 'Enrollments change from the student page in the panel.',
        ],
    ],
];
