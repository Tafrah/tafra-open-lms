<?php

declare(strict_types=1);

return [
    'mode' => [
        'single' => 'Single database (filtered by tenant_id)',
        'multi' => 'Database per organization',
        'mismatch' => 'TENANCY_MODE is ":configured" but this installation was locked to ":locked" on first deploy. Changing the mode after launch would show empty organizations or leak data between them — restore TENANCY_MODE=:locked.',
        'cross_tenant_query' => 'Cannot query :model across organizations in database-per-organization mode. Use TenantContext::forEachTenant() instead.',
    ],

    'init' => [
        'inconsistent' => 'Cannot lock :mode mode — these organizations do not match it: :slugs. single must have no organization databases, and multi needs a database for every organization.',
        'invalid' => 'Unknown tenancy mode ":mode". Use single or multi.',
        'env_mismatch' => 'Asked to lock ":mode" but the application runs with TENANCY_MODE=":configured". Set TENANCY_MODE first, then run this command again.',
        'already' => 'Tenancy mode is already locked to ":mode".',
        'locked' => 'Tenancy mode locked to ":mode".',
    ],

    'central' => [
        'brand' => 'Central panel',
    ],

    'health' => [
        'mode' => [
            'ok' => 'Tenancy mode ":mode" is locked and matches the environment.',
            'unlocked' => 'Tenancy mode ":mode" is not locked yet — run php artisan tenancy:init --mode=:mode.',
        ],
        'databases' => [
            'ok' => 'All :count active organization databases exist and are fully migrated.',
            'failed' => ':count organization databases are missing or not fully migrated: :slugs',
            'not_applicable' => 'Single-database mode — there are no organization databases.',
        ],
    ],

    'tenant' => [
        'singular' => 'Organization',
        'plural' => 'Organizations',

        'sections' => [
            'identity' => 'Organization details',
            'brand' => 'Brand',
            'brand_help' => 'The primary colour is expanded into a full scale automatically. The darker shades must pass the contrast check so text stays readable.',
        ],

        'fields' => [
            'name' => 'Name',
            'description' => 'Description',
            'slug' => 'Slug',
            'domain' => 'Domain',
            'primary_color' => 'Primary colour',
            'is_active' => 'Organization is active',
            'trial_ends_at' => 'Trial ends',
            'users_count' => 'Users',
            'provisioning_status' => 'Provisioning',
            'suspended_at' => 'Suspended at',
            'suspension_reason' => 'Suspension reason',
            'confirmation' => 'Confirmation',
        ],

        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ],

        'provisioning' => [
            'pending' => 'Provisioning',
            'ready' => 'Ready',
            'failed' => 'Failed',
        ],

        'help' => [
            'slug' => 'Appears in the URL: /admin/t/{slug}. Letters, numbers and dashes only.',
            'domain' => 'A custom domain for this organization (optional).',
            'primary_color' => 'Shade 600 only — the rest is derived automatically.',
            'trial' => 'Leave empty if this organization is not on a trial.',
            'suspension_reason' => 'Members of the organization see this on the suspended page.',
        ],

        'validation' => [
            'low_contrast' => 'This colour has a contrast ratio below 4.5:1 on white — text on it will not be readable. Pick a darker shade.',
            'slug_mismatch' => 'The slug you typed does not match this organization.',
        ],

        'mcp' => [
            'suspend' => 'Suspends one organization: its members can no longer sign in to it, and nothing is deleted. A reason is required and shown to its members. The last active organization cannot be suspended.',
            'activate' => 'Reactivates a suspended organization so its members can sign in again.',
        ],

        'actions' => [
            'suspend' => 'Suspend',
            'activate' => 'Reactivate',
            'impersonate' => 'Open as organization admin',
            'force_delete_confirm' => 'Type ":slug" to confirm',
        ],

        'confirm' => [
            'suspend_heading' => 'Suspend this organization?',
            'suspend_body' => 'Members lose access immediately and the organization disappears from their switcher. Nothing is deleted.',
            'activate_heading' => 'Reactivate this organization?',
            'activate_body' => 'Members regain access straight away.',
            'impersonate_heading' => 'Open the organization as its admin?',
            'impersonate_body' => 'You will act as the organization admin. Sensitive operations stay locked and the session is logged and ends automatically.',
            'force_delete_body' => 'This permanently deletes the organization — in database-per-organization mode its whole database is dropped. This cannot be undone.',
        ],

        'notifications' => [
            'suspended' => 'Organization suspended',
            'activated' => 'Organization reactivated',
        ],

        'profile' => [
            'label' => 'Organization profile',
        ],

        'suspended_page' => [
            'title' => 'This organization is suspended',
            'body' => 'Access to :name is paused. Contact your account manager to restore it.',
            'reason' => 'Reason',
            'back' => 'Back to the home page',
        ],

        'menu' => [
            'settings' => 'Organization settings',
        ],

        'empty' => [
            'heading' => 'No organizations',
            'description' => 'Add the first organization to get started.',
        ],
    ],

    'tours' => [
        'organization_profile' => [
            'purpose' => [
                'title' => 'Organization profile',
                'body' => 'Your organization name and description, as its members see them.',
            ],
            'form' => [
                'title' => 'Name and colour',
                'body' => 'Enter the name in both languages and pick a brand colour with good contrast — the whole panel uses it.',
            ],
            'save' => [
                'title' => 'Save',
                'body' => 'Changes show for every member right away.',
            ],
        ],
        'tenants' => [
            'purpose' => [
                'title' => 'Organizations',
                'body' => 'Every organization on the platform, with its status and provisioning state.',
            ],
            'actions' => [
                'title' => 'Suspend, reactivate, open',
                'body' => 'Suspending hides the organization from its members without deleting anything. Open it as its admin to investigate a problem.',
            ],
            'create' => [
                'title' => 'Add an organization',
                'body' => 'In database-per-organization mode this creates and migrates its database.',
            ],
        ],
    ],

    'onboarding' => [
        'first_tenant' => [
            'label' => 'Create the first organization',
            'description' => 'Organizations are where your customers work. Create one, then open it as its admin to check it.',
        ],
    ],
];
