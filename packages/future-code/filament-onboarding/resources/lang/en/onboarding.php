<?php

declare(strict_types=1);

return [
    'wizard' => [
        'title' => 'Setup wizard',
        'heading' => 'Let us get the system ready',
        'subheading' => 'A few quick steps to make the panel ready for your team. You can come back any time.',
        'progress' => 'Step :current of :total',
        'open' => 'Open the screen',
        'skip' => 'Skip',
        'finish' => 'Finish',
        'step_done' => 'This step is complete.',
        'step_required' => 'This step is required before you can continue.',
        'congrats_heading' => 'All set — the system is ready',
        'congrats_body' => 'Start by adding your team, then write your first announcement and check the activity log.',
    ],

    'checklist' => [
        'heading' => 'Start here',
        'description' => 'These steps update themselves from the real state of the system.',
        'complete_heading' => 'Every step is complete',
        'complete_description' => 'Nothing left to do. This list will disappear on its own.',
        'progress' => 'Progress',
        'start' => 'Start',
        'required' => 'Required',
        'dismiss' => 'Hide this list',
    ],

    'tours' => [
        'replay' => 'Replay the guided tour',
        'next' => 'Next',
        'prev' => 'Back',
        'done' => 'Done',
        'progress' => '{{current}} of {{total}}',
    ],

    'analytics' => [
        'title' => 'Onboarding analytics',
        'summary' => 'Summary',
        'users' => 'Users who started',
        'finished_rate' => 'Wizard completion rate',
        'dismissed' => 'Dismissed the list',
        'dismissed_early' => 'Dismissed before finishing',
        'steps' => 'Completion rate per step',
        'steps_hint' => 'The lowest step is the drop-off point — it is usually unclear, not the users.',
        'drop_off' => 'drop-off point',
    ],

    'steps' => [
        'organization' => [
            'label' => 'Set up the organization',
            'description' => 'Name, slug and primary colour — every user sees these in the panel.',
        ],
        'branding' => [
            'label' => 'Upload a logo and pick a colour',
            'description' => 'The panel carries your organization identity from the first sign-in.',
        ],
        'team' => [
            'label' => 'Invite your team',
            'description' => 'Add at least one user besides yourself and set their role.',
        ],
        'mail' => [
            'label' => 'Configure mail',
            'description' => 'Without it, invitations and security alerts never arrive.',
        ],
        'storage' => [
            'label' => 'Configure storage',
            'description' => 'Choose the default disk and the disk for private files.',
        ],
    ],

    'exempt' => [
        'wizard' => 'The setup wizard is itself the onboarding for the panel.',
        'analytics' => 'A report about onboarding itself — there is no workflow to teach.',
        'dashboard' => 'The dashboard carries the getting-started checklist for each role — that is its onboarding.',
        'profile' => 'Filament\'s built-in account page — name, email and password, nothing to learn.',
    ],
];
