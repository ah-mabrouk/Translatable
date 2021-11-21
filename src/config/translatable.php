<?php

return [
    'locale' => 'en',

    'locales' => ['en', 'ar'],

    'locale_codes' => [
        'en' => env('LOCALIZATION_CODE_EN', 'en_US.utf8'),
        'ar' => env('LOCALIZATION_CODE_AR', 'ar_EG.utf8'),
    ],

    'fallback_locale' => 'en',

    'translation_models_path' => 'App\Models',

    'translatable_request_methods' => [
        'put',
    ],

    'translatable_request_segments' => [
        'admin-panel',
    ],

    'non_translatable_request_segments' => [
        'media',
    ],
];
