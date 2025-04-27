<?php

namespace Mabrouk\Translatable\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validation rule to ensure a unique value for a specific locale.
 */
class UniqueForLocale implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param Model $modelObject The model object to validate against.
     * @param string $databaseColumn The corresponding attribute's database column to check (optional).
     */
    public function __construct(public Model $modelObject, public string $databaseColumn = '')
    {
        //
    }

    /**
     * Validates the given attribute value to ensure it is unique for the specified locale.
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $databaseColumn = $this->databaseColumn ?: $attribute;

        $valueExistsForLocale = $this->modelObject->translationModelClass::where($databaseColumn, $value)
            ->where('locale', request()->input('locale'))
            ->where('id', '!=', translation_id($this->modelObject))
            ->exists();

        if ($valueExistsForLocale) {
            $fail('validation.unique')->translate();
        }
    }
}
