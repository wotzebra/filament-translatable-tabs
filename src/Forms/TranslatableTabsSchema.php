<?php

namespace Wotz\TranslatableTabs\Forms;

use Filament\Schemas\Schema;

/**
 * The child schema of {@see TranslatableTabs}. The locale tabs' state is
 * transposed back to field-first translations here, after every field inside
 * the tabs has run its own `mutateDehydratedStateUsing()` callback — a parent
 * component has no lifecycle hook of its own at that point.
 *
 * @internal
 */
class TranslatableTabsSchema extends Schema
{
    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function mutateDehydratedState(array &$state = []): array
    {
        parent::mutateDehydratedState($state);

        $parentComponent = $this->getParentComponent();

        if ($parentComponent instanceof TranslatableTabs) {
            $parentComponent->transposeDehydratedTranslations($state);
        }

        return $state;
    }
}
