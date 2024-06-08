<?php

return [
    'plugin' => [
        'name' => 'Converter',
        'description' => 'Enables multi-currency websites.',
    ],
    'currency_picker' => [
        'component_name' => 'Currency Picker',
        'component_description' => 'Shows a dropdown to select a front-end currency.',
    ],
    'currency' => [
        'title' => 'Manage Currencies',
        'update_title' => 'Update currency',
        'create_title' => 'Create currency',
        'select_label' => 'Select currency',
        'default_suffix' => 'default',
        'unset_default' => '":currency" is already default and cannot be unset as default.',
        'disabled_default' => '":currency" is disabled and cannot be set as default.',
        'name' => 'Name',
        'code' => 'Code',
        'is_default' => 'Default',
        'is_default_help' => 'The default currency represents the content before convert.',
        'is_enabled' => 'Enabled',
        'is_enabled_help' => 'Disabled currencies will not be available in the front-end.',
        'not_available_help' => 'There are no other currencies set up.',
        'hint_locales' => 'Create new currency here for convert front-end values. The default currency represents the value before it has been convert.',
    ],
    'finances' => [
        'title' => 'Manage Finances Services',
        'update_title' => 'Update Service',
        'create_title' => 'Create Service',
        'select_label' => 'Select Service',
        'default_suffix' => 'default',
        'unset_default' => '":service" is already default and cannot be unset as default.',
        'disabled_default' => '":service" is disabled and cannot be set as default.',
        'name' => 'Name',
        'code' => 'Code',
        'url' => 'Url',
        'is_default' => 'Default',
        'is_default_help' => '',
        'is_enabled' => 'Enabled',
        'is_enabled_help' => 'Disabled services will not be available in the front-end.',
        'not_available_help' => 'There are no other services set up.',
        'hint_locales' => 'Create new service here for convert front-end values.',
    ]
];