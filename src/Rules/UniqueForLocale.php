<?php

namespace Mabrouk\Translatable\Rules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\Rule;

class UniqueForLocale implements Rule
{
    public Model $modelObject;
    public string $databaseColumn;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Model $modelObject, string $databaseColumn = '')
    {
        $this->modelObject = $modelObject;
        $this->databaseColumn = $databaseColumn;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $databaseColumn = $this->databaseColumn ?? $attribute;

        return $this->modelObject->translationModelClass::where($databaseColumn, $value)
            ->where('locale', request()->locale)
            ->where('id', '!=', translation_id($this->modelObject))
            ->count() == 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.unique');
    }
}
