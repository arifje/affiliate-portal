<?php

return [
    'navigation' => [
        'administration' => 'Administration',
        'catalog' => 'Catalog',
        'feed_imports' => 'Feed imports',
        'platform' => 'Platform',
    ],

    'pages' => [
        'dashboard' => [
            'navigation_label' => 'Dashboard',
            'title' => 'Dashboard',
            'filters' => [
                'site' => 'Site',
                'all_sites' => 'All sites',
            ],
        ],

        'logs' => [
            'navigation_label' => 'Logs',
            'title' => 'Logs',
        ],

        'settings' => [
            'navigation_label' => 'Settings',
            'title' => 'Settings',
            'sections' => [
                'website_status' => 'Website status',
            ],
            'descriptions' => [
                'website_status' => 'Control whether the public storefront is available. The admin panel remains available while the website is offline.',
            ],
            'fields' => [
                'website_online' => 'Website online',
            ],
            'help' => [
                'website_online' => 'When disabled, public website API requests return an offline response.',
            ],
            'notifications' => [
                'saved' => 'Settings saved',
            ],
            'actions' => [
                'save' => 'Save settings',
            ],
        ],

        'system' => [
            'navigation_label' => 'System',
            'title' => 'System',
        ],
    ],

    'resources' => [
        'canonical_fields' => [
            'model_label' => 'Canonical field',
            'plural_label' => 'Canonical fields',
            'navigation_label' => 'Canonical fields',
        ],

        'feed_field_mappings' => [
            'model_label' => 'Feed field mapping',
            'plural_label' => 'Feed field mappings',
            'navigation_label' => 'Feed field mappings',
        ],

        'feed_import_batches' => [
            'model_label' => 'Feed import batch',
            'plural_label' => 'Feed import batches',
            'navigation_label' => 'Feed import batches',
        ],

        'feed_mapping_profiles' => [
            'model_label' => 'Feed mapping profile',
            'plural_label' => 'Feed mapping profiles',
            'navigation_label' => 'Feed mapping profiles',
        ],

        'feeds' => [
            'model_label' => 'Feed',
            'plural_label' => 'Feeds',
            'navigation_label' => 'Feeds',
        ],

        'partners' => [
            'model_label' => 'Partner',
            'plural_label' => 'Partners',
            'navigation_label' => 'Partners',
        ],

        'products' => [
            'model_label' => 'Product',
            'plural_label' => 'Products',
            'navigation_label' => 'Products',
        ],

        'sites' => [
            'model_label' => 'Site',
            'plural_label' => 'Sites',
            'navigation_label' => 'Sites',
        ],

        'users' => [
            'model_label' => 'User',
            'plural_label' => 'Users',
            'navigation_label' => 'Users',

            'sections' => [
                'user' => 'User',
                'password' => 'Password',
            ],

            'fields' => [
                'name' => 'Name',
                'email' => 'Email address',
                'admin_locale' => 'Admin language',
                'email_verified_at' => 'Email verified at',
                'is_active' => 'Active',
                'password' => 'Password',
                'created_at' => 'Created at',
                'updated_at' => 'Updated at',
            ],

            'filters' => [
                'email_verified' => 'Email verified',
            ],

            'placeholders' => [
                'not_verified' => 'Not verified',
            ],
        ],
    ],
];
