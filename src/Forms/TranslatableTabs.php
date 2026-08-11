<?php

namespace Wotz\TranslatableTabs\Forms;

use Closure;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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
     * Translations are stored field-first on the record (`title.en`) but are
     * edited locale-first in the form (`en.title`). Transpose the field-first
     * translations in the filled state into the locale tabs before the child
     * fields hydrate, so every field runs its own state casts (RichEditor,
     * Checkbox, ...) over the translated value. The filled state — not the
     * record — is the source, so changes made in `mutateFormDataBeforeFill()`
     * survive.
     */
    public function hydrateState(?array &$hydratedDefaultState, bool $shouldCallHydrationHooks = true, bool $shouldApplyStateCasts = true, array &$appliedStateCastPaths = []): void
    {
        if ($hydratedDefaultState === null) {
            $this->fillTranslationsIntoLocaleTabs();
        }

        parent::hydrateState($hydratedDefaultState, $shouldCallHydrationHooks, $shouldApplyStateCasts, $appliedStateCastPaths);
    }

    /**
     * `EditRecord::refreshFormData()` and `Schema::fillPartially()` write
     * refreshed attributes field-first and hydrate their field-first state
     * paths, both of which would bypass the locale tabs. Transpose the
     * refreshed translations into the locale tabs and rewrite the state paths
     * so the fields inside the tabs re-hydrate instead.
     *
     * @param  array<string>  $statePaths
     */
    public function hydrateStatePartially(array $statePaths, bool $shouldCallHydrationHooks = true): void
    {
        parent::hydrateStatePartially($this->transposePartiallyFilledTranslations($statePaths), $shouldCallHydrationHooks);
    }

    protected function makeChildSchema(string $key): Schema
    {
        return TranslatableTabsSchema::make($this->getLivewire())
            ->parentComponent($this);
    }

    /**
     * Transpose the locale tabs' dehydrated state back to the field-first
     * shape (`en.title` -> `title.en`) so `getState()` returns data that the
     * record accepts natively, and drop the locale-first copy so the
     * dehydrated state does not hold both shapes. Every field inside the
     * locale tabs is transposed, so a field missing from the model's
     * `$translatable` array fails loudly under its own name instead of
     * leaking a locale key into the state. Called by the child schema after
     * the fields' `mutateDehydratedStateUsing()` callbacks have run.
     *
     * @internal
     *
     * @param  array<string, mixed>  $state
     */
    public function transposeDehydratedTranslations(array &$state): void
    {
        $statePath = $this->getStatePath();
        $prefix = filled($statePath) ? "{$statePath}." : '';

        foreach ($this->getLocales() as $locale) {
            $localeState = Arr::get($state, "{$prefix}{$locale}");

            if (! is_array($localeState)) {
                continue;
            }

            foreach ($localeState as $field => $value) {
                Arr::set($state, "{$prefix}{$field}.{$locale}", $value); /** @phpstan-ignore parameterByRef.type */
            }

            Arr::forget($state, "{$prefix}{$locale}"); /** @phpstan-ignore parameterByRef.type */
        }
    }

    protected function fillTranslationsIntoLocaleTabs(): void
    {
        $translatableAttributes = $this->getTranslatableAttributeNames();

        if (blank($translatableAttributes)) {
            return;
        }

        $state = $this->getRawState();
        $state = is_array($state) ? $state : [];
        $locales = $this->getLocales();
        $stateChanged = false;

        foreach ($translatableAttributes as $field) {
            $translations = $state[$field] ?? null;

            if ($translations instanceof Arrayable) {
                $translations = $translations->toArray();
            }

            if (! is_array($translations)) {
                continue;
            }

            foreach ($translations as $locale => $value) {
                if (! in_array($locale, $locales, true)) {
                    continue;
                }

                $state[$locale][$field] = $value instanceof Arrayable ? $value->toArray() : $value;
            }

            // The locale tabs are now the single source of truth; keeping the
            // field-first attribute around would leave two copies in the form
            // state and trip Filament's unsaved-changes detection.
            unset($state[$field]);

            $stateChanged = true;
        }

        if ($stateChanged) {
            $this->rawState($state);
        }
    }

    /**
     * @param  array<string>  $statePaths
     * @return array<string>
     */
    protected function transposePartiallyFilledTranslations(array $statePaths): array
    {
        $translatableAttributes = $this->getTranslatableAttributeNames();

        if (blank($translatableAttributes)) {
            return $statePaths;
        }

        $statePath = $this->getStatePath();
        $prefix = filled($statePath) ? "{$statePath}." : '';
        $locales = $this->getLocales();

        $state = $this->getRawState();
        $state = is_array($state) ? $state : [];
        $stateChanged = false;

        $transposedStatePaths = [];

        foreach ($statePaths as $path) {
            if (filled($prefix) && ! str_starts_with($path, $prefix)) {
                $transposedStatePaths[] = $path;

                continue;
            }

            $segments = explode('.', substr($path, strlen($prefix)));
            $field = $segments[0];

            if (! in_array($field, $translatableAttributes, true)) {
                $transposedStatePaths[] = $path;

                continue;
            }

            // `title.en` -> `en.title`: move the value that `fillPartially()`
            // wrote field-first into the locale tab.
            if (isset($segments[1]) && in_array($segments[1], $locales, true)) {
                $fieldFirstPath = implode('.', $segments);

                [$segments[0], $segments[1]] = [$segments[1], $segments[0]];
                $localeFirstPath = implode('.', $segments);

                if (Arr::has($state, $fieldFirstPath)) {
                    Arr::set($state, $localeFirstPath, Arr::get($state, $fieldFirstPath));
                    Arr::forget($state, $fieldFirstPath);

                    if (blank(Arr::get($state, $field))) {
                        Arr::forget($state, $field);
                    }

                    $stateChanged = true;
                }

                $transposedStatePaths[] = "{$prefix}{$localeFirstPath}";

                continue;
            }

            if (isset($segments[1])) {
                $transposedStatePaths[] = $path;

                continue;
            }

            // A whole-attribute refresh (`refreshFormData(['title'])`): the
            // translations never reach the raw state, because `fillPartially()`
            // dot-flattens its data before filtering it by state path, so
            // re-read them from the record.
            if (is_array($state[$field] ?? null)) {
                $translations = $state[$field];

                unset($state[$field]);

                $stateChanged = true;
            } else {
                $translations = $this->getRecordTranslations($field);
            }

            foreach ($translations as $locale => $value) {
                if (! in_array($locale, $locales, true)) {
                    continue;
                }

                $state[$locale][$field] = $value instanceof Arrayable ? $value->toArray() : $value;
                $transposedStatePaths[] = "{$prefix}{$locale}.{$field}";
                $stateChanged = true;
            }
        }

        if ($stateChanged) {
            $this->rawState($state);
        }

        return $transposedStatePaths;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRecordTranslations(string $field): array
    {
        $record = $this->getTranslatableRecord();

        if (! $record || ! method_exists($record, 'getTranslatedLocales') || ! method_exists($record, 'getTranslation')) {
            return [];
        }

        $translations = [];

        foreach ($record->getTranslatedLocales($field) as $locale) {
            $translations[$locale] = $record->getTranslation($field, $locale);
        }

        return $translations;
    }

    protected function getTranslatableRecord(): ?Model
    {
        $record = $this->getRecord();

        if (! $record instanceof Model) {
            $livewire = $this->getLivewire();

            $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
        }

        return ($record instanceof Model && method_exists($record, 'getTranslatableAttributes')) ? $record : null;
    }

    /**
     * The record when there is one, otherwise a fresh instance of the
     * schema's model — enough to know which attributes are translatable.
     */
    protected function getTranslatableModel(): ?Model
    {
        if ($record = $this->getTranslatableRecord()) {
            return $record;
        }

        $model = $this->getModel();
        $model = $model ? app($model) : null;

        return ($model instanceof Model && method_exists($model, 'getTranslatableAttributes')) ? $model : null;
    }

    /**
     * @return array<string>
     */
    protected function getTranslatableAttributeNames(): array
    {
        return $this->getTranslatableModel()?->getTranslatableAttributes() ?? [];
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
