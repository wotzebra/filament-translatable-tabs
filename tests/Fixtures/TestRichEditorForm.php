<?php

namespace Wotz\TranslatableTabs\Tests\Fixtures;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Wotz\TranslatableTabs\Forms\TranslatableTabs;

class TestRichEditorForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public array $translations = [];

    public function mount(): void
    {
        $this->form->fill($this->translations);
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
                        RichEditor::make('body'),
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
