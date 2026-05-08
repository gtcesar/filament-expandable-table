<?php

namespace Tibras\ExpandableTable\Tests\Fixtures\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Tibras\ExpandableTable\Contracts\HasExpandedTable;
use Tibras\ExpandableTable\Tests\Fixtures\Models\OrderItem;

class OrderItemsTable implements HasExpandedTable
{
    public function table(Table $table, Model $record): Table
    {
        return $table
            ->query(OrderItem::query()->where('order_id', $record->id))
            ->columns([
                TextColumn::make('product_name')->searchable()->sortable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('unit_price'),
                TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('product_name')
            ->paginated([5, 10]);
    }
}
