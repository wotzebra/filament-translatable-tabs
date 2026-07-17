<?php

namespace Wotz\TranslatableTabs\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A minimal stand-in for a spatie/laravel-translatable model: TranslatableTabs only
 * duck-types the translatable methods, so the tests do not need that dependency.
 */
class TestRecord extends Model
{
    protected $guarded = [];

    public array $translations = [];

    /**
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        return array_keys($this->translations);
    }

    /**
     * @return array<string>
     */
    public function getTranslatedLocales(string $field): array
    {
        return array_keys($this->translations[$field] ?? []);
    }

    public function getTranslation(string $field, string $locale): mixed
    {
        return $this->translations[$field][$locale] ?? null;
    }
}
