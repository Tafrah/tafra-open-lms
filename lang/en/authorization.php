<?php

declare(strict_types=1);

return [

    'actions' => [
        'view_any' => 'View list',
        'view' => 'View details',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'delete_any' => 'Bulk delete',
        'restore' => 'Restore',
        'force_delete' => 'Permanently delete',
        'export' => 'Export',
        'impersonate' => 'Impersonate',
        'reset_password' => 'Reset password',
        'force_logout' => 'Terminate sessions',
        'manage_roles' => 'Manage roles',
        'download' => 'Download',
        'change_disk' => 'Change storage disk',
        'prune' => 'Prune records',
        'publish' => 'Publish',
        'pin' => 'Pin',
        'suspend' => 'Suspend',
        'manage_storage' => 'Manage storage',
        'manage_mail' => 'Manage mail',
        'manage_appearance' => 'Manage appearance',
        'manage_security' => 'Manage security',
        'manage_notifications' => 'Manage notifications',
        'manage_ai' => 'Manage AI access',
        'grade' => 'Grade',
        'adjust_wallet' => 'Adjust wallet balance',
        'enroll' => 'Enroll in a course',
        'block' => 'Block',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'print' => 'Print',
    ],

    'resources' => [
        'users' => 'Users',
        'roles' => 'Roles',
        'tenants' => 'Organizations',
        'announcements' => 'Announcements',
        'media' => 'Media',
        'settings' => 'Settings',
        'activity_logs' => 'Activity log',
        'ai_connections' => 'Connected AI apps',
        'educational_levels' => 'Grades',
        'online_lessons' => 'Lessons',
        'bootcamps' => 'Courses',
        'online_exams' => 'Exams',
        'exam_attempts' => 'Exam attempts',
        'students' => 'Students',
        'enrollments' => 'Enrollments',
        'payment_methods' => 'Payment methods',
        'payment_invoices' => 'Invoices & receipts',
        'money_transactions' => 'Wallet transactions',
        'recharge_cards' => 'Recharge cards',
        'coupons' => 'Coupons',
    ],

    'groups' => [
        'identity' => 'Identity & access',
        'tenancy' => 'Organizations',
        'central' => 'Central panel',
        'content' => 'Content',
        'system' => 'System',
        'dashboard' => 'Dashboard',
        'ai' => 'AI access',
        'academy' => 'Learning content',
        'exams' => 'Exams',
        'students' => 'Students',
        'billing' => 'Finance',
    ],

    'pages' => [
        'access.panel.admin' => 'Access the admin panel',
        'access.panel.central' => 'Access the central panel',
        'access.dashboard' => 'Access the dashboard',
        'access.horizon' => 'Access Horizon',
        'access.pulse' => 'Access Pulse',
        'access.health' => 'System health page',
        'access.log_viewer' => 'Log viewer',
        'access.backups' => 'Backups',
        'access.system' => 'System area',
        'access.tenancy' => 'Organizations area',
        'access.organization_profile' => 'Organization profile',
        'access.onboarding_analytics' => 'Onboarding analytics',
        'access.notification_preferences' => 'Notification preferences',
        'access.my_devices' => 'My devices',
        'access.two_factor' => 'Two-factor authentication',
        'access.ai' => 'Connect AI apps (Claude, ChatGPT)',
        'access.data_transfer' => 'Data transfer with Tafra',
        'access.site_settings' => 'Site settings',
    ],

    'widgets' => [
        'widget.stats_overview' => 'Stats overview widget',
        'widget.users_chart' => 'Users chart widget',
        'widget.recent_activity' => 'Recent activity widget',
        'widget.onboarding_checklist' => 'Getting-started checklist widget',
        'widget.lms_overview' => 'Learning platform overview',
    ],

    'roles' => [
        'super_admin' => 'Super admin',
        'support' => 'Support',
        'admin' => 'Admin',
        'editor' => 'Editor',
        'viewer' => 'Viewer',
        'assistant' => 'Assistant',
    ],

    'sync' => [
        'operation' => 'Operation',
        'count' => 'Count',
        'to_create' => 'Create',
        'to_prune' => 'Prunable',
        'done' => 'Synced and cache cleared.',
    ],

    'denied' => [
        // General
        'missing_permission' => 'You do not have the “:permission” permission.',
        'record_trashed' => 'This record is deleted. Restore it first.',
        'record_not_trashed' => 'This record is not deleted.',
        'record_not_found' => 'This record does not exist.',
        'maintenance_mode' => 'The system is in maintenance mode right now.',
        'self_target' => 'You cannot do that to your own account.',
        'last_admin' => 'This is the last admin in the organization — you cannot remove them.',
        'while_impersonating' => 'This operation is blocked while impersonating.',

        // Users
        'user' => [
            'super_admin_only' => 'Only a super admin can act on a super admin account.',
            'cannot_impersonate_super_admin' => 'You cannot impersonate a super admin.',
            'cannot_impersonate_staff' => 'You cannot impersonate a member of the operations team.',
            'inactive_target' => 'This account is suspended — activate it first.',
        ],

        // Roles & permissions
        'role' => [
            'protected' => 'This role comes from the config and is shared by every organization — it cannot be edited or deleted here.',
            'still_assigned' => 'This role is still assigned to users. Unassign it first.',
            'other_tenant' => 'This role does not exist.',
        ],
        'permission' => [
            'config_owned' => 'Permissions come from the config file — edit it there and run authorization:sync.',
        ],

        'settings' => [
            'global_group' => 'This settings group is global — only a super admin manages it.',
            'tenant_required' => 'Organization settings can only be changed from inside an organization.',
        ],

        // Organizations
        'tenancy' => [
            'last_active_tenant' => 'This is the last active organization — it cannot be closed.',
            'already_inactive' => 'This organization is already inactive.',
            'already_suspended' => 'This organization is already suspended.',
            'not_suspended' => 'This organization is not suspended.',
            'not_provisioned' => 'This organization is still being provisioned.',
            'no_admin_to_impersonate' => 'This organization has no active admin to open it as.',
        ],

        // AI access (MCP)
        'ai' => [
            'inactive_account' => 'This account is not active.',
            'not_a_member' => 'You are not a member of this organization.',
            'tenant_unavailable' => 'This organization is inactive or suspended.',
            'disabled' => 'AI access is turned off for this organization.',
            'already_revoked' => 'This app is already disconnected.',
        ],

        // Activity log
        'audit' => [
            'append_only' => 'The audit log cannot be edited or deleted — that is what makes it worth keeping.',
        ],

        // Announcements
        'announcement' => [
            'not_draft' => 'This announcement is already published.',
            'published_must_archive' => 'A published announcement is archived, not deleted.',
            'missing_fallback_title' => 'You must write the title in :locale before publishing.',
            'already_expired' => 'The announcement expiry date has passed — update it first.',
            'pin_requires_published' => 'You cannot pin an unpublished announcement.',
            'pin_limit_reached' => 'You have reached the maximum of :limit pinned announcements.',
            'published_needs_publish_permission' => 'This announcement is published — editing it requires the publish permission.',
        ],
        // Learning platform
        'bootcamp' => [
            'already_published' => 'This course is already published.',
        ],
        'exam' => [
            'already_published' => 'This exam is already published.',
            'no_questions' => 'Add at least one question before publishing the exam.',
        ],
        'attempt' => [
            'not_finished' => 'The student has not finished this attempt yet.',
        ],
        'answer' => [
            'auto_graded' => 'This question is graded automatically.',
        ],
        'invoice' => [
            'not_pending' => 'This invoice has already been reviewed.',
        ],
        'recharge_card' => [
            'used' => 'This code has been used — it cannot be edited or deleted.',
        ],
    ],
];
