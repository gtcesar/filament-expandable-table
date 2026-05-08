<?php

use Tibras\ExpandableTable\Actions\ExpandAction;

// ──────────────────────────────────────────────
// Nome padrão
// ──────────────────────────────────────────────

it('has the default name expand', function () {
    expect(ExpandAction::getDefaultName())->toBe('expand');
});

// ──────────────────────────────────────────────
// detailComponent — string
// ──────────────────────────────────────────────

it('stores a string detail component', function () {
    $action = ExpandAction::make()
        ->detailComponent('App\\Tables\\OrderItemsTable');

    expect($action->getDetailComponent())->toBe('App\\Tables\\OrderItemsTable');
});

it('stores a fully-qualified class name', function () {
    $action = ExpandAction::make()
        ->detailComponent(\Tibras\ExpandableTable\Tests\Fixtures\Tables\OrderItemsTable::class);

    expect($action->getDetailComponent())
        ->toBe(\Tibras\ExpandableTable\Tests\Fixtures\Tables\OrderItemsTable::class);
});

// ──────────────────────────────────────────────
// detailComponent — closure
// ──────────────────────────────────────────────

it('evaluates a closure for detail component', function () {
    $action = ExpandAction::make()
        ->detailComponent(fn () => 'App\\Tables\\OrderItemsTable');

    expect($action->getDetailComponent())->toBe('App\\Tables\\OrderItemsTable');
});

it('evaluates a closure returning a class constant', function () {
    $action = ExpandAction::make()
        ->detailComponent(fn () => \Tibras\ExpandableTable\Tests\Fixtures\Tables\OrderItemsTable::class);

    expect($action->getDetailComponent())
        ->toBe(\Tibras\ExpandableTable\Tests\Fixtures\Tables\OrderItemsTable::class);
});

// ──────────────────────────────────────────────
// Contrato HasExpandedTable
// ──────────────────────────────────────────────

it('fixture table implements HasExpandedTable contract', function () {
    expect(\Tibras\ExpandableTable\Tests\Fixtures\Tables\OrderItemsTable::class)
        ->toImplement(\Tibras\ExpandableTable\Contracts\HasExpandedTable::class);
});

it('HasExpandedTable contract requires table method', function () {
    $reflection = new ReflectionClass(\Tibras\ExpandableTable\Contracts\HasExpandedTable::class);
    $method     = $reflection->getMethod('table');

    expect($method->getParameters())->toHaveCount(2);

    [$tableParam, $recordParam] = $method->getParameters();

    expect($tableParam->getName())->toBe('table')
        ->and($recordParam->getName())->toBe('record');
});
