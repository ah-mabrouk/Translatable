<?php

if (! function_exists('translation_id')) {
    function translation_id($model) {
        $locale = request()->locale ?? config('translatable.fallback_locale');
        return $model->translations->where('locale', $locale)->first()?->id;
    }
}
