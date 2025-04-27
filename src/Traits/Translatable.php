<?php

namespace Mabrouk\Translatable\Traits;

use ReflectionClass;

/**
 * Translatable Trait
 *
 * Provides functionality for handling translations in Eloquent models.
 * This trait allows models to manage translations for attributes,
 * retrieve translated values, and handle fallback locales.
 */
trait Translatable
{
    /**
     * @var string|null The current locale being used for translations.
     */
    private $locale;

    /**
     * @var string|null The locale extracted from the request header.
     */
    private $xLocale;

    /**
     * Boot the Translatable trait.
     *
     * Registers a model event listener to handle translations after the model is saved.
     *
     * @return void
     */
    public static function bootTranslatable()
    {
        static::saved(function ($model) {
            if (!request()->dontTranslate) {
                return $model->translate(request()->all());
            }
        });
    }

    /**
     * Magic method to retrieve translated attributes.
     *
     * @param string $property The property name being accessed.
     * @return mixed The translated value or the parent property value.
     */
    public function __get($property)
    {
        return \in_array($property, $this->translatedAttributes)
            ? $this->findTranslatedAttribute($property)
            : parent::__get($property);
    }

    /**
     * Define the relationship to the translation model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->translationModelClass);
    }

    /**
     * Save translations for the model.
     *
     * @param array $data The data to be translated.
     * @param string|null $locale The locale for the translation (optional).
     * @return $this
     */
    public function translate($data, $locale = null)
    {
        $this->modifyOrMakeTranslation($data, $locale);
        request()->dontTranslate = true;
        return $this;
    }

    /**
     * Retrieve a specific translated attribute for a given locale.
     *
     * @param string $attribute The attribute name.
     * @param string|null $locale The locale to retrieve the translation for (optional).
     * @return mixed The translated attribute value.
     */
    public function tr($attribute, $locale = null)
    {
        return $this->translations->where('locale', $this->locale($locale))->first()?->$attribute;
    }

    /**
     * Retrieve the fallback translation for the model.
     *
     * @return mixed The fallback translation.
     */
    public function fallbackTranslation()
    {
        return $this->translations?->where('locale', config('translatable.fallback_locale'))?->first();
    }

    /**
     * Find the translated value of an attribute.
     *
     * @param string $attribute The attribute name.
     * @return mixed The translated value or the fallback value.
     */
    public function findTranslatedAttribute($attribute)
    {
        $translation = $this->translations->where('locale', $this->xLocale())->first();
        return $translation && $translation?->$attribute != null
            ? $translation?->$attribute
            : ($this->fallbackTranslation())?->$attribute;
    }

    /**
     * Delete translations for specific locales.
     *
     * @param string ...$params The locales to delete translations for.
     * @return $this
     */
    public function deleteTranslations(string ...$params)
    {
        $this->translations()
            ->when(\count($params) > 0, function ($query) use ($params) {
                $query->whereIn('locale', $params);
            })->delete();

        return $this;
    }

    /**
     * Create or update a translation for the model.
     *
     * @param array $data The data to be translated.
     * @param string|null $locale The locale for the translation (optional).
     * @return $this
     */
    private function modifyOrMakeTranslation($data, $locale = null)
    {
        $locale = $this->locale($locale);
        $translationData = \array_merge($this->getTranslatableFields($data), ['locale' => $locale]);

        if (count($translationData) > 1) {
            $this->translations()->updateOrCreate(
                ['locale' => $locale],
                $translationData
            );
            return $this->refresh();
        }

        return $this;
    }

    /**
     * Filter the translatable fields from the given data.
     *
     * @param array $data The data to filter.
     * @return array The filtered translatable fields.
     */
    private function getTranslatableFields($data)
    {
        $filteredData = collect($data)->filter(function ($field, $key) {
            return \in_array($key, $this->translatedAttributes);
        })->toArray();

        return $filteredData;
    }

    /**
     * Determine the locale to use for translations.
     *
     * @param string|null $locale The locale to use (optional).
     * @return string The determined locale.
     */
    private function locale($locale = null)
    {
        if ($this->translations()->count() == 0) {
            $this->locale = config('translatable.fallback_locale');
            return $this->locale;
        }

        $this->locale = $locale != null && \in_array($locale, config('translatable.locales'))
            ? $locale
            : request()->locale;
        $this->locale = $this->locale != null && \in_array($this->locale, config('translatable.locales'))
            ? $this->locale
            : config('translatable.fallback_locale');
        return $this->locale;
    }

    /**
     * Retrieve the locale from the request header or fallback locale.
     *
     * @return string The determined locale.
     */
    private function xLocale()
    {
        $this->xLocale = request()->header('X-locale') != null && \in_array(request()->header('X-locale'), config('translatable.locales'))
            ? request()->header('X-locale')
            : config('translatable.fallback_locale');
        return $this->xLocale;
    }

    /**
     * Determine the translation model class for the current model.
     *
     * @return string The translation model class name.
     */
    private function translationModelClass()
    {
        $translationModelClass = config('translatable.translation_models_path') . '\\' . (new ReflectionClass($this))->getShortName() . 'Translation';
        if (!\class_exists($translationModelClass)) {
            $translationModelClass = (new ReflectionClass($this))->getName() . 'Translation';
        }
        return $translationModelClass;
    }

    /**
     * Accessor for the translation model class attribute.
     *
     * @return string The translation model class name.
     */
    public function getTranslationModelClassAttribute()
    {
        return $this->translationModelClass();
    }
}