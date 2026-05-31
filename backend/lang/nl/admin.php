<?php

return [
    'navigation' => [
        'administration' => 'Beheer',
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
];
