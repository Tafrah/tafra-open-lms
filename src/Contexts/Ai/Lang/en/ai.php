<?php

declare(strict_types=1);

return [
    'menu' => [
        'connections' => 'Connected AI apps',
    ],

    'access' => [
        'read' => [
            'label' => 'Read only',
            'description' => 'The app can look things up and explain them, but cannot change anything.',
        ],
        'write' => [
            'label' => 'Read & write',
            'description' => 'The app can also create, edit and delete — always within your own permissions, and it asks you before every change.',
        ],
    ],

    'consent' => [
        'title' => 'Connect :client to your account',
        'intro' => ':client is asking to work inside :app on your behalf.',
        'points' => [
            'as_you' => 'It acts as you, in the organization you pick below.',
            'never_more' => 'It can never do more than you can — every action goes through your permissions.',
            'logged' => 'Every action it takes is recorded in the activity log.',
            'revoke' => 'You can disconnect it at any time from “Connected AI apps” in your account menu.',
        ],
        'signed_in_as' => 'Signed in as :email',
        'redirects_to' => 'After you answer, you will be sent back to: :hosts',
        'organization' => 'Organization',
        'central' => 'Central operations panel',
        'read_only_org' => 'This organization allows read-only access.',
        'access' => 'What can it do?',
        'approve' => 'Allow',
        'deny' => 'Cancel',
        'no_targets' => 'AI access is not available for your account right now — your organization may have turned it off, or your role does not include it.',
    ],

    'connections' => [
        'title' => 'Connected AI apps',
        'description' => 'Claude, ChatGPT and other AI apps you have connected to your account. Each one acts as you, with your permissions.',
        'unknown_app' => 'Unnamed app',
        'never_used' => 'Not used yet',
        'fields' => [
            'app' => 'App',
            'member' => 'Member',
            'access' => 'Access',
            'last_used' => 'Last used',
            'connected_at' => 'Connected',
            'status' => 'Status',
        ],
        'status' => [
            'active' => 'Connected',
            'revoked' => 'Disconnected',
        ],
        'filters' => [
            'active' => 'Connected only',
            'scope' => 'Whose connections',
            'mine' => 'Mine',
            'organization' => 'Everyone in this organization',
        ],
        'actions' => [
            'revoke' => 'Disconnect',
        ],
        'confirm' => [
            'heading' => 'Disconnect this app?',
            'body' => 'It loses access immediately. To use it again, connect it again from the app.',
        ],
        'notifications' => [
            'revoked' => 'App disconnected',
        ],
        'connect' => [
            'heading' => 'Connect an AI app',
            'description' => 'Add this server as a connector in the app, then sign in and approve.',
            'url' => 'Server URL:',
            'steps' => [
                'claude' => 'Claude: Settings → Connectors → Add custom connector, and paste the URL.',
                'chatgpt' => 'ChatGPT: Settings → Apps & Connectors → Create (developer mode), paste the URL, and choose OAuth.',
                'approve' => 'Sign in here when asked, pick the organization and the access level, then press Allow.',
            ],
        ],
        'empty' => [
            'heading' => 'No AI apps connected yet',
            'description' => 'Connect Claude or ChatGPT to ask questions about your data and get work done in plain language — always with your own permissions.',
        ],
    ],

    'errors' => [
        'unauthenticated' => 'Sign in again to reconnect this app.',
        'connection_revoked' => 'This app was disconnected. Connect it again to continue.',
        'inactive_account' => 'This account is not active.',
        'access_closed' => 'AI access is turned off for this organization, or you are no longer a member.',
    ],

    'tours' => [
        'connections' => [
            'purpose' => [
                'title' => 'Your AI apps',
                'body' => 'Every AI app connected to your account, how much it can do, and when it was last used.',
            ],
            'connect' => [
                'title' => 'Connect Claude or ChatGPT',
                'body' => 'Paste this URL into the app\'s connector settings, then approve it here. You choose the organization and whether it can make changes.',
            ],
            'table' => [
                'title' => 'Disconnect anytime',
                'body' => 'Disconnecting cuts the app off immediately. Every action an app takes is in the activity log.',
            ],
        ],
    ],
];
