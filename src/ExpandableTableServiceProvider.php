<?php

namespace Tibras\ExpandableTable;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExpandableTableServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-expandable-table';

    public static string $viewNamespace = 'expandable-table';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$viewNamespace)
            ->hasTranslations();
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        Livewire::addNamespace(
            'expandable-table',
            classNamespace: 'Tibras\\ExpandableTable\\Livewire'
        );
    }

    protected function getAssetPackageName(): string
    {
        return 'tibras/filament-expandable-table';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // JS carrega em toda página Filament para registrar o Alpine store antes de qualquer interação.
            Js::make('filament-expandable-table', __DIR__ . '/../resources/dist/expandable-table.js'),

            // CSS carregado sob demanda pelo x-load-css na view da sub-table.
            Css::make('filament-expandable-table', __DIR__ . '/../resources/dist/expandable-table.css')
                ->loadedOnRequest(),
        ];
    }
}
