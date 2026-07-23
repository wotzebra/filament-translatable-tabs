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

    public function mount(): void
    {
        // Mimics an edit page: `$record->attributesToArray()` returns translatable
        // attributes field-first (`title => [en => ..., nl => ...]`).
        $this->form->fill([...$this->recordAttributes, ...$this->translations]);
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
                        Checkbox::make('online'),
                    ]),
            ])
            ->statePath('data');
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
