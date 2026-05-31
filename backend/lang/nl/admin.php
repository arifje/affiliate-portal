<?php

return [
    'navigation' => [
        'administration' => 'Beheer',
        'catalog' => 'Catalogus',
        'feed_imports' => 'Feed-imports',
        'platform' => 'Platform',
    ],

    'pages' => [
        'dashboard' => [
            'navigation_label' => 'Overzicht',
            'title' => 'Overzicht',
            'filters' => [
                'site' => 'Site',
                'all_sites' => 'Alle sites',
            ],
        ],

        'logs' => [
            'navigation_label' => 'Logs',
            'title' => 'Logs',
        ],

        'settings' => [
            'navigation_label' => 'Instellingen',
            'title' => 'Instellingen',
            'sections' => [
                'website_status' => 'Websitestatus',
            ],
            'descriptions' => [
                'website_status' => 'Bepaal of de publieke website beschikbaar is. Het beheerpaneel blijft beschikbaar wanneer de website offline is.',
            ],
            'fields' => [
                'website_online' => 'Website online',
            ],
            'help' => [
                'website_online' => 'Wanneer dit is uitgeschakeld, geven publieke website-API-verzoeken een offline reactie terug.',
            ],
            'notifications' => [
                'saved' => 'Instellingen opgeslagen',
            ],
            'actions' => [
                'save' => 'Instellingen opslaan',
            ],
        ],

        'system' => [
            'navigation_label' => 'Systeem',
            'title' => 'Systeem',
        ],
    ],

    'resources' => [
        'canonical_fields' => [
            'model_label' => 'Canoniek veld',
            'plural_label' => 'Canonieke velden',
            'navigation_label' => 'Canonieke velden',
        ],

        'feed_field_mappings' => [
            'model_label' => 'Feed-veldmapping',
            'plural_label' => 'Feed-veldmappings',
            'navigation_label' => 'Feed-veldmappings',
        ],

        'feed_import_batches' => [
            'model_label' => 'Feed-importbatch',
            'plural_label' => 'Feed-importbatches',
            'navigation_label' => 'Feed-importbatches',
        ],

        'feed_mapping_profiles' => [
            'model_label' => 'Feed-mappingprofiel',
            'plural_label' => 'Feed-mappingprofielen',
            'navigation_label' => 'Feed-mappingprofielen',
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
            'plural_label' => 'Producten',
            'navigation_label' => 'Producten',
        ],

        'sites' => [
            'model_label' => 'Site',
            'plural_label' => 'Sites',
            'navigation_label' => 'Sites',
        ],

        'users' => [
            'model_label' => 'Gebruiker',
            'plural_label' => 'Gebruikers',
            'navigation_label' => 'Gebruikers',

            'sections' => [
                'user' => 'Gebruiker',
                'password' => 'Wachtwoord',
            ],

            'fields' => [
                'name' => 'Naam',
                'email' => 'E-mailadres',
                'admin_locale' => 'Beheertaal',
                'email_verified_at' => 'E-mail geverifieerd op',
                'is_active' => 'Actief',
                'password' => 'Wachtwoord',
                'created_at' => 'Aangemaakt op',
                'updated_at' => 'Bijgewerkt op',
            ],

            'filters' => [
                'email_verified' => 'E-mail geverifieerd',
            ],

            'placeholders' => [
                'not_verified' => 'Niet geverifieerd',
            ],
        ],
    ],
];
