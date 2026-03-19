<?php

namespace Wotz\TranslatableTabs\InfoLists;

use Closure;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Wotz\LocaleCollection\Facades\LocaleCollection;
use Wotz\LocaleCollection\Locale;

class TranslatableEntry
{
    public static function make(
        Closure $schema,
        string $localeCollectionClass = LocaleCollection::class
    ): Section {
        $tabs = $localeCollectionClass::map(
            fn (Locale $locale) => Tab::make($locale->locale())
                ->schema($schema($locale))
        )->toArray();

        return Section::make([
            Tabs::make()
                ->tabs($tabs)
                ->contained(false),
        ]);
    }
}
