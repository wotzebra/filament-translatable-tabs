<?php

use Livewire\Livewire;
use Wotz\TranslatableTabs\Tests\Fixtures\TestRichEditorForm;

it('hydrates a translatable rich editor through the editor state casts', function () {
    // Bare <li> text must be wrapped in a paragraph by Filament's RichEditorStateCast.
    // Converting through the TipTap editor directly skips that normalisation and hands
    // the JS editor a document its schema does not allow, which makes the caret jump
    // around while editing list items. See filamentphp/filament#19529.
    $translations = [
        'body' => [
            'en' => '<ul><li>First item</li><li>Second item</li></ul>',
            'nl' => '<ul><li>Eerste item</li></ul>',
        ],
    ];

    $state = Livewire::test(TestRichEditorForm::class, ['translations' => $translations])
        ->get('data');

    $bulletList = $state['en']['body']['content'][0];

    expect($bulletList['type'])->toBe('bulletList');

    foreach ($bulletList['content'] as $listItem) {
        expect($listItem['type'])->toBe('listItem')
            ->and($listItem['content'][0]['type'])->toBe('paragraph');
    }

    expect($bulletList['content'][0]['content'][0]['content'][0]['text'])->toBe('First item')
        ->and($bulletList['content'][1]['content'][0]['content'][0]['text'])->toBe('Second item');
});

it('hydrates every locale of a translatable rich editor', function () {
    $translations = [
        'body' => [
            'en' => '<p>English body</p>',
            'nl' => '<p>Nederlandse tekst</p>',
        ],
    ];

    $state = Livewire::test(TestRichEditorForm::class, ['translations' => $translations])
        ->get('data');

    expect($state['en']['body']['content'][0]['content'][0]['text'])->toBe('English body')
        ->and($state['nl']['body']['content'][0]['content'][0]['text'])->toBe('Nederlandse tekst');
});
