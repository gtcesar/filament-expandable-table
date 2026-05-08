<?php

use Filament\Support\Facades\FilamentAsset;
use Tibras\ExpandableTable\Livewire\ExpandedTableDetail;

// ──────────────────────────────────────────────
// Registro do componente Livewire
// ──────────────────────────────────────────────

it('registers the livewire component', function () {
    $component = Livewire\Livewire::getComponentClass('expandable-table::expanded-table-detail');

    expect($component)->toBe(ExpandedTableDetail::class);
});

// ──────────────────────────────────────────────
// Registro de assets CSS
// ──────────────────────────────────────────────

it('registers the css asset with loadedOnRequest', function () {
    $href = FilamentAsset::getStyleHref(
        'filament-expandable-table',
        package: 'tibras/filament-expandable-table',
    );

    expect($href)->not->toBeNull()
        ->and($href)->toBeString();
});

// ──────────────────────────────────────────────
// Registro de assets JS
// ──────────────────────────────────────────────

it('registers the js asset', function () {
    $src = FilamentAsset::getScriptSrc(
        'filament-expandable-table',
        package: 'tibras/filament-expandable-table',
    );

    expect($src)->not->toBeNull()
        ->and($src)->toBeString();
});

// ──────────────────────────────────────────────
// Views publicáveis
// ──────────────────────────────────────────────

it('registers the package views', function () {
    expect(view()->exists('expandable-table::livewire.expanded-table-detail'))->toBeTrue();
});

// ──────────────────────────────────────────────
// Traduções
// ──────────────────────────────────────────────

it('loads english translations', function () {
    app()->setLocale('en');

    expect(__('expandable-table::expandable-table.expand'))->toBe('Expand details')
        ->and(__('expandable-table::expandable-table.collapse'))->toBe('Collapse details');
});

it('loads pt_BR translations', function () {
    app()->setLocale('pt_BR');

    expect(__('expandable-table::expandable-table.expand'))->toBe('Expandir detalhes')
        ->and(__('expandable-table::expandable-table.collapse'))->toBe('Recolher detalhes');
});
