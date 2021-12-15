<?php

namespace Mabrouk\Translatable\Traits;

use ReflectionClass;

Trait Translatable
{
    private $locale;
    private $xLocale;

    public static function bootTranslatable()
    {
        if (! request()->isMethod('get')) {
            static::saved(function ($model) {
                if (! request()->dontTranslate) {
                    return $model->translate(request()->all());
                }
            });
        }
	}

    public function __get($property)
    {
        return \in_array($property, $this->translatedAttributes) ? $this->findTranslatedAttribute($property) : Parent::__get($property);
    }

    // * Define translation model relation
    public function translations()
    {
        return $this->hasMany($this->translationModelClass());
    }

    // save model translation
    public function translate($data, $locale = null)
    {
        $this->modifyOrMakeTranslation($data, $locale);
        request()->dontTranslate = true;
        return $this;
    }

    public function tr($attribute, $locale = null)
    {
        return optional($this->translations->where('locale', $this->locale($locale))->first())->$attribute;
    }

    public function fallbackTranslation()
    {
        return optional(optional(optional($this->translations)->where('locale', config('translatable.fallback_locale')))->first());
    }

    public function findTranslatedAttribute($attribute)
    {
        $translation = $this->translations->where('locale', $this->xLocale())->first();
        return $translation && optional($translation)->$attribute != null ? $translation[$attribute] : $this->fallbackTranslation()[$attribute];
    }

    public function deleteTranslations(string ...$params)
    {
        $this->translations()
            ->when(\count($params) > 0, function ($query) use ($params) {
                $query->whereIn('locale', $params);
            })
            ->delete();
        return $this;
    }

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

    private function getTranslatableFields($data)
    {
        $filteredData = collect($data)->filter(function ($field, $key) {
            return \in_array($key, $this->translatedAttributes);
        })->toArray();

        return $filteredData;
    }

    private function locale($locale = null)
    {
        $this->locale = $locale != null && \in_array($locale, config('translatable.locales')) ? $locale : request()->locale;
        $this->locale = $this->locale != null && \in_array($this->locale, config('translatable.locales')) ? $this->locale : config('translatable.fallback_locale');
        return $this->locale;
    }

    private function xLocale()
    {
        $this->xLocale = request()->header('X-locale') != null && \in_array(request()->header('X-locale'), config('translatable.locales')) ? request()->header('X-locale') : config('translatable.fallback_locale');
        return $this->xLocale;
    }

    private function translationModelClass()
    {
        $translationModelClass = config('translatable.translation_models_path') . '\\' . (new ReflectionClass($this))->getShortName() . 'Translation';
        if (! \class_exists($translationModelClass)) {
            $translationModelClass = (new ReflectionClass($this))->getName() . 'Translation';
        }
        return $this->hasMany($translationModelClass);
    }
}
