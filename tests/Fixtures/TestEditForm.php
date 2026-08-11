<?php

namespace Wotz\TranslatableTabs\Tests\Fixtures;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Wotz\TranslatableTabs\Forms\TranslatableTabs;

class TestEditForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public array $translations = [];

    public array $recordAttributes = [];

    /** Overrides the record's translations in `fill()`, like `mutateFormDataBeforeFill()` would. */
    public array $fillTranslations = [];

    /** When false, the record is only resolvable through `$livewire->getRecord()`. */
    public bool $hasSchemaModel = true;

    public function mount(): void
    {
        // Mimics an edit page: `$record->attributesToArray()` returns translatable
        // attributes field-first (`title => [en => ..., nl => ...]`).
        $this->form->fill([...$this->recordAttributes, ...$this->translations, ...$this->fillTranslations]);
    }

    public function refreshFormData(array $statePaths): void
    {
        // Mimics `EditRecord::refreshFormData()`.
        $this->form->fillPartially([...$this->recordAttributes, ...$this->translations], $statePaths);
    }

    public function getRecord(): TestRecord
    {
        $record = new TestRecord;
        $record->translations = $this->translations;

        return $record;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TranslatableTabs::make()
                    ->locales(['en', 'nl'])
                    ->defaultFields([
                        TextInput::make('working_title'),
                    ])
                    ->translatableFields(fn (string $locale) => [
                        TextInput::make('title'),
                        // Deliberately absent from the record's translatable attributes.
                        TextInput::make('subtitle')
                            ->mutateDehydratedStateUsing(fn (?string $state): ?string => is_string($state) ? strtoupper($state) : $state),
                        Checkbox::make('online'),
                    ]),
            ])
            ->statePath('data')
            ->model($this->hasSchemaModel ? $this->getRecord() : null);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <form wire:submit="create">
                {{ $this->form }}

                <button type="submit">
                    Submit
                </button>
            </form>

            <x-filament-actions::modals />
        </div>
        HTML;
    }
}
