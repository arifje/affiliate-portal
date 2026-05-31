<?php

return [
    'navigation' => [
        'administration' => 'Administration',
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
];
