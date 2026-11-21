<?php

declare(strict_types=1);

return [
    'item' => [
        'singular' => 'File',
        'plural' => 'Media',

        'fields' => [
            'name' => 'Name',
            'collection' => 'Collection',
            'mime' => 'Type',
            'size' => 'Size',
            'disk' => 'Disk',
        ],

        'empty' => [
            'heading' => 'No files',
            'description' => 'Files uploaded from any screen show up here.',
        ],
    ],

    'help' => [
        'avatar' => 'A square image under 2 MB. JPG, PNG or WebP.',
    ],

    'errors' => [
        'not_found' => 'This file does not exist.',
    ],

    'tours' => [
        'library' => [
            'purpose' => [
                'title' => 'Media library',
                'body' => 'Every file uploaded in the organization, and where it is stored.',
            ],
            'table' => [
                'title' => 'Private files',
                'body' => 'Private files open through a temporary link that expires — they are never public.',
            ],
        ],
    ],
];
