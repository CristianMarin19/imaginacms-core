<?php

namespace Imagina\Icore\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Validator;

abstract class CoreFormRequest extends FormRequest
{
    /**
     * Set the translation key prefix for attributes.
     *
     * @var string
     */
    protected string $translationsAttributesKey = 'validation.attributes.';

    /**
     * Current processed locale
     *
     * @var string
     */
    protected string $localeKey;

    /**
     * Return an array of rules for translatable fields
     *
     * @return array
     */
    public function translationRules(): array
    {
        return [];
    }

    /**
     * Return an array of messages for translatable fields
     *
     * @return array
     */
    public function translationMessages(): array
    {
        return [];
    }

    /**
     * @return Validator
     */
    protected function getValidatorInstance(): Validator
    {
        $factory = $this->container->make('Illuminate\Validation\Factory');
        if (method_exists($this, 'validator')) {
            return $this->container->call([$this, 'validator'], compact('factory'));
        }

        $rules = $this->container->call([$this, 'rules']);
        $attributes = $this->attributes();
        $messages = [];

        $translationsAttributesKey = $this->getTranslationsAttributesKey();

        foreach ($this->requiredLocales() as $localeKey => $locale) {
            $this->localeKey = $localeKey;
            foreach ($this->container->call([$this, 'translationRules']) as $attribute => $rule) {
                $key = $localeKey . '.' . $attribute;
                $rules[$key] = $rule;
                $attributes[$key] = trans($translationsAttributesKey . $attribute);
            }

            foreach ($this->container->call([$this, 'translationMessages']) as $attributeAndRule => $message) {
                $messages[$localeKey . '.' . $attributeAndRule] = $message;
            }
        }

        return $factory->make(
            $this->all(),
            $rules,
            array_merge($this->messages(), $messages),
            $attributes
        );
    }

    /**
     * @return array
     */
    public function withTranslations(): array
    {
        $results = $this->all();
        $translations = [];
        foreach ($this->requiredLocales() as $key => $locale) {
            $locales[] = $key;
            $translations[$key] = $this->get($key);
        }
        $results['translations'] = $translations;
        Arr::forget($results, $locales ?? []);

        return $results;
    }

    /**
     * @return array
     */
    public function requiredLocales(): array
    {
        //Now is a helper from Itranslation Module
        return getSupportedLocales();
    }

    /**
     * Get the validation for attributes key from the implementing class
     * or use a sensible default
     *
     * @return string
     */
    private function getTranslationsAttributesKey(): string
    {
        return rtrim($this->translationsAttributesKey, '.') . '.';
    }

    public function getValidator(): Validator
    {
        return $this->getValidatorInstance();
    }
}
