# Upgrading

## From v2 to v3

`TranslatableTabs` now hydrates and dehydrates translations through the schema lifecycle itself:

- **The `Wotz\TranslatableTabs\Resources\Traits\HasTranslations` page trait has been removed.** Delete the `use` statement from your Create and Edit pages; the default form actions (including cancel) and `unsavedChangesAlerts()` now work out of the box. (The trait's only remaining purpose was a cancel-button workaround for the false unsaved-changes alert, which is fixed at the source now.)
- **Form state no longer holds both shapes.** Previously the state contained the record's field-first attributes (`title => ['en' => ...]`) *alongside* the locale-first tab state (`en => ['title' => ...]`). Now only the locale-first shape exists while editing, and `getState()` returns only the field-first shape when saving. If you read translatable values in `mutateFormDataBeforeSave()` or similar hooks, read them as `$data['title']['en']` instead of `$data['en']['title']`.
- **Every field inside `translatableFields()` dehydrates field-first**, whether or not it is listed in the model's `$translatable` array. A field you forgot to add to `$translatable` now surfaces under its own name in the saved data (and fails loudly on save), instead of hiding inside a stray `en`/`nl` key.

## From v1 to v2

- Install `wotz/filament-translatable-tabs` instead of `codedor/filament-translatable-tabs`
- Replace all occurrences of `Codedor\TranslatableTabs` namespace with new `Wotz\TranslatableTabs` namespace

### TranslatableEntry

- Instead of an array, we expect a closure now.

```php
use Codedor\TranslatableTabs\InfoLists\TranslatableEntry;

TranslatableEntry::make(fn (Locale $locale) => [
    TextEntry::make("description.{$locale->locale()}")
        ->label('Description')
        ->placeholder('-'),
]),
```
