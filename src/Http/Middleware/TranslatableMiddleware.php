<?php

namespace Mabrouk\Translatable\Http\Middleware;

use Mabrouk\Translatable\Rules\LocaleRule;

/**
 * Middleware to handle translation-related functionality in HTTP requests.
 *
 * This middleware validates the `locale` parameter in requests and sets the application's
 * locale based on the `X-locale` header or falls back to the default locale.
 */
class TranslatableMiddleware
{
    /**
     * @var array|null The mapping of language codes for locale settings.
     */
    protected $languageCodes;

    /**
     * @var bool Indicates whether the request is eligible for translation saving.
     */
    private $availableForTranslationSaving;

    /**
     * Constructor to initialize middleware properties.
     *
     * Sets the language codes and determines if the request is eligible for translation saving
     * based on the request method and URL segments.
     */
    public function __construct()
    {
        $this->languageCodes = config('translatable.locale_codes');

        $this->availableForTranslationSaving =
            \in_array(\strtolower(request()->method()), config('translatable.translatable_request_methods'))
            && (bool) \array_intersect(config('translatable.translatable_request_segments'), request()->segments())
            && ! (bool) \array_intersect(config('translatable.non_translatable_request_segments'), request()->segments());
    }

    /**
     * Handle an incoming request.
     *
     * Validates the `locale` parameter if the request is eligible for translation saving.
     * Sets the application's locale based on the `X-locale` header or falls back to the default locale.
     *
     */
    public function handle($request, \Closure $next)
    {
        if ($this->availableForTranslationSaving) {
            $request->validate([
                'locale' => ['required', new LocaleRule],
            ]);
        }

        if ($request->hasHeader('X-locale') && \in_array($request->header('X-locale'), config('translatable.locales'))) {
            app()->setLocale($request->header('X-locale'));

            \setlocale(LC_TIME, $this->languageCodes[$request->header('X-locale')]);
            return $next($request);
        }

        app()->setLocale(config('translatable.fallback_locale'));

        \setlocale(LC_TIME, config('translatable.fallback_locale'));
        return $next($request);
    }
}