<div class="flex flex-wrap gap-1">
    @foreach ($getLocales() as $locale)
        @php
            $isOnline = ! in_array(
                $getRecord()->getTranslation($getName(), $locale),
                [false, null, 0, ''],
                true,
            );

            $label = $isOnline
                ? __('filament-translatable-tabs::locales-column.online', ['locale' => \Illuminate\Support\Str::upper($locale)])
                : __('filament-translatable-tabs::locales-column.offline', ['locale' => \Illuminate\Support\Str::upper($locale)]);
        @endphp

        <a
            href="{{ $getResourceUrl($locale) }}"
            @style([
                \Filament\Support\get_color_css_variables($isOnline ? 'success' : 'danger', shades: [500, 700]),
            ])
            {{-- The badge only shows the locale code, so spell the state out for
                 screen readers and on hover. --}}
            title="{{ $label }}"
            aria-label="{{ $label }}"
            class="
                text-custom-700 bg-custom-500/10 dark:text-custom-500
                rtl:space-x-reverse min-h-6 px-2 py-0.5 text-sm font-medium tracking-tight
                inline-flex items-center justify-center space-x-1
                rounded-xl whitespace-nowrap
                transition duration-75 hover:bg-custom-500/20
                focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600
                dark:focus-visible:ring-primary-500
            "
        >
            {{ $locale }}
        </a>
    @endforeach
</div>
