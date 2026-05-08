<?php

namespace Tibras\ExpandableTable\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Tibras\ExpandableTable\Contracts\HasExpandedTable;

class ExpandedTableDetail extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public string $recordClass;

    public string|int $recordKey;

    public string $detailComponent;

    public function mount(
        string $recordClass,
        string|int $recordKey,
        string $detailComponent
    ): void {
        $this->recordClass     = $recordClass;
        $this->recordKey       = $recordKey;
        $this->detailComponent = $detailComponent;
    }

    public function table(Table $table): Table
    {
        $record = ($this->recordClass)::findOrFail($this->recordKey);

        /** @var HasExpandedTable $instance */
        $instance = app($this->detailComponent);

        return $instance->table($table, $record);
    }

    public function render(): \Illuminate\View\View
    {
        return view('expandable-table::livewire.expanded-table-detail');
    }
}
