<?php

use Livewire\Livewire;
use Wotz\TranslatableTabs\Tests\Fixtures\TestEditForm;

it('hydrates translations locale-first through the fields own state casts', function () {
    $component = Livewire::test(TestEditForm::class, [
        'translations' => [
            'title' => ['en' => 'Hello', 'nl' => 'Hallo'],
            'online' => ['en' => 1],
        ],
        'recordAttributes' => ['working_title' => 'Working title'],
    ]);

    $component
        ->assertSet('data.working_title', 'Working title')
        ->assertSet('data.en.title', 'Hello')
        ->assertSet('data.nl.title', 'Hallo')
        // The Checkbox state cast runs, so `1` hydrates as a strict boolean.
        ->assertSet('data.en.online', true, strict: true);

    // The field-first copies are gone: the state holds a single shape, so
    // Filament's unsaved-changes detection sees no phantom changes.
    expect($component->get('data'))
        ->not->toHaveKey('title')
        ->not->toHaveKey('online');
});

it('dehydrates the locale tabs back to field-first translations', function () {
    $component = Livewire::test(TestEditForm::class, [
        'translations' => [
            'title' => ['en' => 'Hello', 'nl' => 'Hallo'],
            'online' => ['en' => true],
        ],
        'recordAttributes' => ['working_title' => 'Working title'],
    ]);

    $state = $component->instance()->form->getState();

    expect($state['working_title'])->toBe('Working title')
        ->and($state['title'])->toBe(['en' => 'Hello', 'nl' => 'Hallo'])
        ->and($state['online'])->toBe(['en' => true, 'nl' => false])
        ->and($state)->not->toHaveKey('en')
        ->and($state)->not->toHaveKey('nl');
});

it('survives a fill and dehydrate round-trip unchanged', function () {
    $translations = [
        'title' => ['en' => 'Hello', 'nl' => 'Hallo'],
    ];

    $state = Livewire::test(TestEditForm::class, ['translations' => $translations])
        ->instance()
        ->form
        ->getState();

    expect($state['title'])->toBe($translations['title']);
});
