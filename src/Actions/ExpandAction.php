<?php

namespace Tibras\ExpandableTable\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ExpandAction extends Action
{
    protected string|Closure|null $detailComponent = null;

    protected bool|Closure $startExpanded = false;

    public static function getDefaultName(): ?string
    {
        return 'expand';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->iconButton()
            ->color('gray')
            ->icon('heroicon-o-plus')
            ->extraAttributes(function (Model $record): array {
                $componentId = $this->getLivewire()?->getId() ?? 'global';
                $scopedKey   = $componentId . '.' . $record->getKey();
                $expand      = __('expandable-table::expandable-table.expand');
                $collapse    = __('expandable-table::expandable-table.collapse');
                $recordKey   = $record->getKey();

                // onclick uses the global Alpine object — works even if Alpine
                // hasn't processed this specific element yet (e.g. last row).
                // Single quotes only: double quotes break the HTML onclick="..." attribute.
                $onclick = implode(';', [
                    'event.stopPropagation()',
                    "var k='{$scopedKey}'",
                    "var s=window.Alpine&&Alpine.store('expandableTable')",
                    's&&s.toggle(k)',
                    "this.dataset.expanded=s&&s.isExpanded(k)?'true':'false'",
                    "window.Livewire&&window.Livewire.dispatch('row-expanded',{key:'{$recordKey}',expanded:s&&s.isExpanded(k)})",
                ]);

                return [
                    'class'          => 'fi-expand-btn',
                    'data-expanded'  => 'false',
                    // x-bind keeps the attribute in sync when store changes externally
                    'x-bind:data-expanded' => "(\$store.expandableTable.isExpanded('{$scopedKey}') ? 'true' : 'false')",
                    'onclick'        => $onclick,
                    'title'          => $expand,
                    'x-bind:title'   => "\$store.expandableTable.isExpanded('{$scopedKey}') ? '{$collapse}' : '{$expand}'",
                ];
            })
            ->action(static function (): void {});
    }

    public function detailComponent(string|Closure $component): static
    {
        $this->detailComponent = $component;

        return $this;
    }

    public function getDetailComponent(): string
    {
        return $this->evaluate($this->detailComponent);
    }

    public function startExpanded(bool|Closure $condition = true): static
    {
        $this->startExpanded = $condition;

        return $this;
    }

    public function evaluateStartExpanded(Model $record): bool
    {
        return (bool) $this->evaluate(
            $this->startExpanded,
            namedInjections: ['record' => $record],
            typedInjections: [Model::class => $record, $record::class => $record],
        );
    }
}
