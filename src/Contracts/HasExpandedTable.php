<?php

namespace Tibras\ExpandableTable\Contracts;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

interface HasExpandedTable
{
    /**
     * Configure the full Filament table for the expanded row.
     * All Table methods are available: columns, filters, actions, sorting, etc.
     */
    public function table(Table $table, Model $record): Table;
}
