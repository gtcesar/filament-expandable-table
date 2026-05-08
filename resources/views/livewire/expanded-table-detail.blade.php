<div
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref(
        'filament-expandable-table',
        package: 'tibras/filament-expandable-table'
    ))]"
    class="expandable-table-detail"
>
    {{ $this->table }}
</div>
