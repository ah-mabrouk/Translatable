<?php

namespace Mabrouk\Translatable\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validation rule to ensure a required field for a specific locale.
 * This rule should not be used with the `required` or `sometimes` rule.
 */
class RequiredForLocale implements ValidationRule
{
    /**
     * Indicates that this rule is implicit, meaning it will be applied
     * even if the attribute is not present in the input data.
     *
     * @var bool
     */
    public bool $implicit = true;

    /**
     * The model instance used to check for existing translations.
     *
     * @param Model $modelObject The model object to validate against.
     */
    public function __construct(public Model $modelObject)
    {
        //
    }

    /**
     * Validates the given attribute to ensure it is required for the specified locale.
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->hasTranslatedAttributesInRequest() || !\is_null($value)) {
            return;
        }

        if ($this->isTranslationMissingForLocale()) {
            $fail('validation.required')->translate();
        }
    }

    /**
     * Check if any translated attributes are present in the request.
     */
    protected function hasTranslatedAttributesInRequest(): bool
    {
        return !empty(array_intersect($this->modelObject->translatedAttributes, array_keys(request()->all())));
    }

    /**
     * Check if the translation for the current locale does not exist.
     */
    protected function isTranslationMissingForLocale(): bool
    {
        return $this->modelObject->translations()->where('locale', request()->input('locale'))->doesntExist();
    }
}
