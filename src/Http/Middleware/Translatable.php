<?php

namespace Mabrouk\Translatable\Middleware;

use Closure;
use Mabrouk\Translatable\Rules\LocaleRule;

class Translatable
{
    protected $languageCodes;
    private $availableForTranslationSaving;

    public function __construct()
    {
        $this->languageCodes = config('translatable.locale_codes');

        $this->availableForTranslationSaving =
            \in_array(\strtolower(request()->method()), ['translatable.translatable_request_methods'])
            && (bool) \array_intersect(config('translatable.translatable_request_segments'), request()->segments())
            && ! (bool) \array_intersect(config('translatable.non_translatable_request_segments'), request()->segments());
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
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
