<?php

declare(strict_types=1);

return [
    'announcement' => [
        'singular' => 'Announcement',
        'plural' => 'Announcements',

        'sections' => [
            'content' => 'Announcement content',
            'settings' => 'Publishing settings',
        ],

        'fields' => [
            'title' => 'Title',
            'body' => 'Body',
            'severity' => 'Severity',
            'pinned' => 'Pinned',
            'author' => 'Author',
            'published_at' => 'Published at',
            'expires_at' => 'Expires at',
            'notify_users' => 'Notify users',
            'attachments' => 'Attachments',
        ],

        'help' => [
            'body' => 'Links and images are allowed. Any script or unsafe tag is stripped automatically.',
        ],

        'status' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ],

        'severity' => [
            'info' => 'Info',
            'warning' => 'Warning',
            'urgent' => 'Urgent',
        ],

        'actions' => [
            'publish' => 'Publish',
            'pin' => 'Pin / unpin',
        ],

        'confirm' => [
            'publish_heading' => 'Publish this announcement?',
            'publish_body' => 'It will reach every user in the organization according to their preferences. This cannot be undone.',
        ],

        'notifications' => [
            'published' => 'Announcement published',
            'mail_subject' => 'New announcement',
            'mail_body' => 'Open the panel to read the full announcement.',
        ],

        'badges' => [
            'drafts' => 'Draft announcements not published yet',
        ],

        'errors' => [
            'already_published' => 'This announcement is already published.',
        ],

        'empty' => [
            'heading' => 'No announcements yet',
            'description' => 'Announcements reach every user in the organization. Start with your first one.',
            'cta' => 'Write the first announcement',
        ],

        'export' => [
            'completed' => '{0} No rows exported|{1} One row exported|[2,*] :count rows exported',
        ],
    ],

    'mcp' => [
        'hints' => [
            'status' => 'Changes only through the publish tool — it cannot be set directly.',
        ],
        'actions' => [
            'publish' => 'Publishes a draft so everyone in the organization sees it, and notifies them unless notify_users is false. Only drafts can be published, the fallback-language title is required, and an expired announcement cannot be published. Published announcements cannot be deleted — only archived.',
            'pin' => 'Pins a published announcement to the top, or unpins it if it is already pinned. There is a maximum number of pinned announcements.',
        ],
    ],

    'tours' => [
        'announcements' => [
            'purpose' => [
                'title' => 'Announcements',
                'body' => 'Share news with the whole organization. Drafts stay private until they are published.',
            ],
            'table' => [
                'title' => 'Status and pinning',
                'body' => 'Filter by status, and pin the important ones so they stay on top.',
            ],
            'create' => [
                'title' => 'Write an announcement',
                'body' => 'Write it in both languages — publishing needs the title in the fallback language.',
            ],
        ],
    ],
];
