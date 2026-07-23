<?php

namespace Wotz\TranslatableTabs\Forms;

use Closure;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Livewire\Component as Livewire;

class TranslatableTabs extends Tabs
{
    public array|Closure $defaultFields = [];

    public null|array|Closure $extraTabs = null;

    public Closure $translatableFields;

    public array|Closure $locales = [];

    public null|string|Closure $icon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpan(['lg' => 2]);

        $this->persistTabInQueryString('locale');

        $this->tabs([]);
    }

    /**
     * Translations live field-first on the record (`title.en`) but are edited
     * locale-first in the form (`en.title`). Transpose the record's translations
     * into the locale tabs before the child fields hydrate, so every field runs
     * its own state casts (RichEditor, Checkbox, ...) over the translated value.
     */
    public function hydrateState(?array &$hydratedDefaultState, bool $shouldCallHydrationHooks = true): void
    {
        if ($hydratedDefaultState === null) {
            $this->fillTranslationsIntoLocaleTabs();
        }

        parent::hydrateState($hydratedDefaultState, $shouldCallHydrationHooks);
    }

    /**
     * Transpose the locale tabs' state back to the field-first shape
     * (`en.title` -> `title.en`) so `getState()` returns data that the record
     * accepts natively, and drop the locale-first copy so the dehydrated state
     * does not hold both shapes.
     *
     * @param  array<string, mixed>  $state
     */
    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        parent::dehydrateState($state, $isDehydrated);

        if (! ($isDehydrated && $this->isDehydrated())) {
            return;
        }

        $translatableAttributes = $this->getTranslatableAttributeNames();

        if (blank($translatableAttributes)) {
            return;
        }

        $statePath = $this->getStatePath();
        $prefix = filled($statePath) ? "{$statePath}." : '';

        foreach ($this->getLocales() as $locale) {
            $localeState = Arr::get($state, "{$prefix}{$locale}");

            if (! is_array($localeState)) {
                continue;
            }

            foreach (Arr::only($localeState, $translatableAttributes) as $field => $value) {
                Arr::set($state, "{$prefix}{$field}.{$locale}", $value); /** @phpstan-ignore parameterByRef.type */
                Arr::forget($state, "{$prefix}{$locale}.{$field}"); /** @phpstan-ignore parameterByRef.type */
            }

            if (blank(Arr::get($state, "{$prefix}{$locale}"))) {
                Arr::forget($state, "{$prefix}{$locale}"); /** @phpstan-ignore parameterByRef.type */
            }
        }
    }

    protected function fillTranslationsIntoLocaleTabs(): void
    {
        $record = $this->getTranslatableRecord();

        if (! $record) {
            return;
        }

        $state = $this->getRawState();
        $state = is_array($state) ? $state : [];

        foreach ($record->getTranslatableAttributes() as $field) {
            foreach ($record->getTranslatedLocales($field) as $locale) {
                $value = $record->getTranslation($field, $locale);

                if ($value instanceof Arrayable) {
                    $value = $value->toArray();
                }

                $state[$locale][$field] = $value;
            }

            // The locale tabs are now the single source of truth; keeping the
            // field-first attribute around would leave two copies in the form
            // state and trip Filament's unsaved-changes detection.
            unset($state[$field]);
        }

        $this->rawState($state);
    }

    protected function getTranslatableRecord(): ?Model
    {
        $record = $this->getRecord();

        if (! $record instanceof Model) {
            $livewire = $this->getLivewire();

            $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
        }

        if ($record instanceof Model && method_exists($record, 'getTranslatableAttributes')) {
            return $record;
        }

        return null;
    }

    /**
     * @return array<string>
     */
    protected function getTranslatableAttributeNames(): array
    {
        if ($record = $this->getTranslatableRecord()) {
            return $record->getTranslatableAttributes();
        }

        $model = $this->getModel();
        $model = $model ? app($model) : null;

        if ($model && method_exists($model, 'getTranslatableAttributes')) {
            return $model->getTranslatableAttributes();
        }

        return [];
    }

    public function defaultFields(array|Closure $defaultFields): static
    {
        $this->defaultFields = $defaultFields;

        return $this;
    }

    public function extraTabs(null|array|Closure $extraTabs): static
    {
        $this->extraTabs = $extraTabs;

        return $this;
    }

    public function translatableFields(Closure $translatableFields): static
    {
        $this->translatableFields = $translatableFields;

        return $this;
    }

    public function locales(array|Closure $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function getLocales(): array
    {
        return $this->evaluate($this->locales);
    }

    public function icon(null|string|Closure $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(string $locale): ?string
    {
        return $this->evaluate($this->icon, [
            'locale' => $locale,
        ]);
    }

    public function getDefaultChildComponents(): array
    {
        $tabs = [
            Tab::make('Default')
                ->schema($this->evaluate($this->defaultFields))
                ->id('default')
                ->key('default'),
        ];

        if (! is_null($this->extraTabs)) {
            $tabs = array_merge($tabs, $this->evaluate($this->extraTabs));
        }

        foreach ($this->getLocales() as $locale) {
            $tabs[] = Tab::make($locale)
                ->schema($this->evaluate($this->translatableFields, [
                    'locale' => $locale,
                ]))
                ->key($locale)
                ->id($locale)
                ->statePath($locale)
                ->iconPosition('after')
                ->icon(fn (Get $get) => $this->getIcon($locale) ?? ($get("{$locale}.online") ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'))
                ->badge(function (Livewire $livewire) use ($locale) {
                    if ($livewire->getErrorBag()->has("data.{$locale}.*")) {
                        $count = count($livewire->getErrorBag()->get("data.{$locale}.*"));

                        return trans_choice('{1} :count error|[2,*] :count errors', $count, [
                            'count' => $count,
                        ]);
                    }

                    return null;
                });
        }

        return $tabs;
    }
}
