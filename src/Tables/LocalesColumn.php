<?php

namespace Wotz\TranslatableTabs\Tables;

use Closure;
use Exception;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\Column;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Str;

class LocalesColumn extends Column
{
    protected string $view = 'filament-translatable-tabs::tables.columns.locales-column';

    protected string $resourceAction = 'edit';

    protected array|Closure $locales;

    public function locales(array|Closure $locales)
    {
        $this->locales = $locales;

        return $this;
    }

    public function getLocales(): array
    {
        return $this->evaluate($this->locales);
    }

    public function resourceAction(string $resourceAction)
    {
        $this->resourceAction = $resourceAction;

        return $this;
    }

    public function getResourceUrl(string $locale): string
    {
        /** @var Page&HasTable $livewire */
        $livewire = $this->getLivewire();

        if (method_exists($livewire, 'getResource')) {
            $resource = $livewire->getResource();
        } elseif (method_exists($livewire, 'getRelatedResource')) {
            $resource = $livewire->getRelatedResource();
        } else {
            throw new Exception('Can not find a resource, make sure you are in a resource page or relation manager');
        }

        /** @var \Filament\Resources\Resource $resource */
        $url = $resource::getUrl($this->resourceAction, [
            'record' => $this->getRecord(),
            'locale' => $locale,
        ]);

        return Str::lower($url);
    }
}
