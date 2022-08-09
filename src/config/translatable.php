<?php

return [

    /**
     * The default locale to insert a new translation in case the request don't include locale input
     */
    'locale' => 'en',

    /**
     * The array of locales that represent your project languages
     */
    'locales' => [
        'en',
        'ar',
    ],

    /**
     * The array of locales that the package depend on to retrieve translation accordingly
     */
    'locale_codes' => [
        'en' => env('LOCALIZATION_CODE_EN', 'en_US.utf8'),
        'ar' => env('LOCALIZATION_CODE_AR', 'ar_EG.utf8'),
    ],

    /**
     * The default locale which must be always available in order to avoid any weird issues
     * You should set this at the beginning of the project just one time and don't
     * reset it again unless you are sure there is no translated data.
    */
    'fallback_locale' => 'en',

    /**
     * The default path for translation models is the same as our usual models
     * feel free to change the pass according to your preferences but don't
     * forget to make sure that all translation models are under
     * the same path.
    */
    'translation_models_path' => 'App\Models',

    /**
     * This array represent the request methods that you keen to make the TranslatableMiddleware to deal with
     * and expect to receive translated data using these methods.
     * By default it set to only put method which is working very well in most cases
     * However you may face a specific need to accept additional request type.
    */
    'translatable_request_methods' => [
        'put',
    ],

    /**
     * This array represent the base segments that you expect to add translation data under it.
     * If you have several base urls may accept new translations under it feel free
     * to add more base segments according to your project needs.
     */
    'translatable_request_segments' => [
        'admin-panel',
    ],

    /**
     * This array contains any request segment that should be ignored while checking if the current
     * request should be checked to be translated or not.
     * Any request uri include any segment included below will not ask for locale input while storing
     * or updating process and will not make any magic translation process as will.
     */
    'non_translatable_request_segments' => [
        'media',
    ],
];
