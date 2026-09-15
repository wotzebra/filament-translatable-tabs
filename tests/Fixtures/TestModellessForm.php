<?php

namespace Wotz\TranslatableTabs\Tests\Fixtures;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Wotz\TranslatableTabs\Forms\TranslatableTabs;

/**
 * Translations stored outside a translatable model (a JSON column, an action's
 * data): there is no record or model to ask which attributes are translatable.
 */
class TestModellessForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public array $fillData = [];

    public function mount(): void
    {
        $this->form->fill($this->fillData);
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
                        Grid::make(1)->schema([
                            TextInput::make('subtitle'),
                        ]),
                        Checkbox::make('online'),
                    ]),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->form }}
        </div>
        HTML;
    }
}
