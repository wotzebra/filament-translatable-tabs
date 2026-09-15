<?php

use Livewire\Livewire;
use Wotz\TranslatableTabs\Forms\TranslatableTabs;
use Wotz\TranslatableTabs\Tests\Fixtures\TestForm;
use Wotz\TranslatableTabs\Tests\Fixtures\TestFormWithoutDefaults;

it('can render translatable tabs with default fields and translatable fields', function () {
    Livewire::test(TestForm::class)
        ->assertFormFieldExists('default.working_title')
        ->assertFormFieldExists('en.title')
        ->assertFormFieldExists('en.online')
        ->assertFormFieldExists('nl.title')
        ->assertFormFieldExists('nl.online');
});

it('leaves out the Default tab when nothing is shared between the languages', function () {
    /*
     * A record can genuinely have nothing that is the same in every language — a menu
     * item that builds its own navigation from the catalogue has a label per language
     * and no shared destination. Drawn anyway, the empty tab is the one the form opens
     * on, so an editor lands on a blank panel with the fields they came for behind a tab
     * they have to go and find.
     */
    expect(tabLabels(TestFormWithoutDefaults::class))->toBe(['en', 'nl']);
});

it('keeps the Default tab, first, when something is shared', function () {
    expect(tabLabels(TestForm::class))->toBe(['Default', 'en', 'nl']);
});

/**
 * @return array<int, string>
 */
function tabLabels(string $form): array
{
    $tabs = collect(Livewire::test($form)->instance()->form->getComponents())
        ->first(fn ($component): bool => $component instanceof TranslatableTabs);

    return array_map(
        fn ($tab): string => (string) $tab->getLabel(),
        $tabs->getDefaultChildComponents(),
    );
}
