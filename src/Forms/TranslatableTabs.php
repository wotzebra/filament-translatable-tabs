<?php

namespace Wotz\TranslatableTabs\Forms;

use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Support\Arrayable;
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

        $this->afterStateHydrated(static function (TranslatableTabs $component, string|array|null $state, Livewire $livewire): void {
            if (blank($state)) {
                $component->state([]);

                return;
            }

            $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

            if (! $record || ! method_exists($record, 'getTranslatableAttributes')) {
                return;
            }

            foreach ($record->getTranslatableAttributes() as $field) {
                foreach ($record->getTranslatedLocales($field) as $locale) {
                    $value = $record->getTranslation($field, $locale);

                    if ($value instanceof Arrayable) {
                        $value = $value->toArray();
                    }

                    // RichEditor hack
                    if (isset($state[$locale][$field]) && is_array($state[$locale][$field])) {
                        if (isset($state[$locale][$field]['type']) && $state[$locale][$field]['type'] === 'doc' && isset($state[$locale][$field]['content'])) {
                            $components = $livewire->form->getFlatComponents(withActions: false, withHidden: true);

                            if (isset($components["{$locale}.{$field}"]) && $components["{$locale}.{$field}"] instanceof RichEditor) {
                                $value = $components["{$locale}.{$field}"]->getTipTapEditor()
                                    ->setContent($value)
                                    ->getDocument();
                            }
                        }
                    }

                    $state[$locale][$field] = $value;
                }
            }

            $component->state($state);
        });

        $this->tabs([]);
    }

    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        parent::dehydrateState($state, $isDehydrated);

        $model = app($this->getModel());

        if (
            ! $model
            || ! method_exists($model, 'getFillable')
            || ! method_exists($model, 'getTranslatableAttributes')
        ) {
            return;
        }

        foreach (Arr::except($state['data'] ?? [], $model->getFillable()) as $locale => $values) {
            if (! is_array($values)) {
                continue;
            }

            foreach (Arr::only($values, $model->getTranslatableAttributes()) as $key => $value) {
                $state['data'][$key][$locale] = $value;
            }
        }
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
