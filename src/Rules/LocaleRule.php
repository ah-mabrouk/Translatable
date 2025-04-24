<?php

namespace Mabrouk\Translatable\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule to ensure that the given value
 * is one of the allowed locales defined in the application's configuration.
 */
class LocaleRule implements ValidationRule
{
    /**
     * Validates the given attribute to ensure it matches one of the allowed locales.
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!\in_array($value, config('translatable.locales'))) {
            $fail(":attribute must be one of [" . \implode(', ', config('translatable.locales')) . "]");
        }
    }
}