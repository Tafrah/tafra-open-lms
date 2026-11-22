<?php

declare(strict_types=1);

return [
    'enums' => [
        'student_status' => [
            'active' => 'Active',
            'blocked' => 'Blocked',
        ],
        'school_type' => [
            'general' => 'General',
            'azhar' => 'Azhar',
        ],
    ],
    'errors' => [
        'phone_taken' => 'This mobile number is already registered.',
    ],
    'student' => [
        'singular' => 'Student',
        'plural' => 'Students',
        'empty_related' => 'Nothing yet.',
        'sections' => [
            'info' => 'Student details',
        ],
        'fields' => [
            'name' => 'Name',
            'phone' => 'Mobile',
            'parent_phone' => 'Parent mobile',
            'email' => 'Email',
            'level' => 'Grade',
            'governorate' => 'Governorate',
            'school_type' => 'Education type',
            'password' => 'Password',
            'new_password' => 'New password',
            'code' => 'Code',
            'balance' => 'Balance',
            'bootcamp' => 'Course',
            'transaction_type' => 'Transaction type',
            'amount' => 'Amount',
            'notes' => 'Note',
            'enrolled_courses' => 'Enrolled courses',
        ],
        'relations' => [
            'enrollments' => 'Enrollments',
            'transactions' => 'Wallet transactions',
            'attempts' => 'Exams',
            'devices' => 'Devices',
        ],
        'actions' => [
            'enroll' => 'Enroll in a course',
            'unenroll' => 'Unenroll',
            'wallet' => 'Adjust balance',
            'reset_password' => 'Change password',
            'block' => 'Block account',
            'unblock' => 'Unblock account',
            'disconnect_devices' => 'Disconnect all devices',
            'export' => 'Export Excel / CSV',
        ],
        'notifications' => [
            'enrolled' => 'The student is enrolled.',
            'unenrolled' => 'The enrollment was removed.',
            'wallet' => 'The balance was updated.',
            'password' => 'The password was changed.',
            'devices_disconnected' => 'The student’s devices were disconnected — they are signed out everywhere, and the next device they sign in from takes the slot.',
        ],
        'confirm' => [
            'disconnect_devices' => 'The student will be signed out of every device they are using now, and can sign in again from one device (or the number set in “Site settings”).',
        ],
        'export' => [
            'completed' => '{0} No students were exported|{1} One student was exported|[2,*] :count students were exported',
        ],
        'empty' => [
            'heading' => 'No students yet',
            'description' => 'Share your platform link with students — they appear here once they sign up.',
        ],
        'validation' => [
            'phone' => 'The mobile number must be an 11-digit Egyptian number (e.g. 01012345678).',
        ],
    ],
    'device' => [
        'fields' => [
            'device' => 'Device',
            'ip' => 'IP address',
            'last_seen' => 'Last seen',
            'first_seen' => 'First sign-in',
        ],
        'empty' => [
            'heading' => 'No devices',
            'description' => 'Once the student signs in on the site, their device appears here.',
        ],
    ],
    'tours' => [
        'student' => [
            'purpose' => [
                'title' => 'Students',
                'body' => 'Everyone registered on your platform. From here you can enroll them manually, top up their wallet, reset the password or block them.',
            ],
            'table' => [
                'title' => 'Students',
                'body' => 'Search by name, phone or code, and filter by grade.',
            ],
            'create' => [
                'title' => 'New student',
                'body' => 'You can register a student yourself (e.g. a centre student).',
            ],
        ],
    ],
    'mcp' => [
        'omitted' => [
            'money' => 'Any money movement is the teacher’s decision in the panel — never an AI model’s.',
            'access' => 'Opening and closing courses changes who can access what — panel only.',
            'account' => 'Passwords and blocking are security actions — panel only.',
        ],
    ],
    'widget' => [
        'students' => 'Students',
        'new_students' => ':count new in the last 7 days',
        'revenue' => 'This month',
        'revenue_hint' => 'Paid invoices since the start of the month',
        'pending_receipts' => 'Receipts waiting',
        'pending_receipts_hint' => 'Review them so the course opens for the student',
        'pending_essays' => 'Essays to grade',
        'pending_essays_hint' => 'Attempts with essay questions',
    ],
];
