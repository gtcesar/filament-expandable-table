# filament-expandable-table

Linhas expansíveis com sub-table aninhada para Filament 5 / Livewire 4.

Ao clicar no botão de expansão de uma linha, ela exibe uma tabela Filament
independente diretamente abaixo — sem modal, sem redirecionamento.

A sub-table é uma tabela Filament completa: **colunas, filtros, ações, bulk
actions, ordenação, busca global e paginação** funcionam normalmente.

O estado de expansão é gerenciado **client-side via Alpine.js** — nenhum
round-trip ao servidor para abrir/fechar linhas.

## Requisitos

- PHP 8.3+
- Laravel 11 ou 12
- Filament 5.x
- Livewire 4.x

## Instalação

```bash
composer require gtcesar/filament-expandable-table
php artisan filament:assets
```

## Uso

### 1. Criar a classe de sub-table

Implemente o contrato `HasExpandedTable` com o método `table()` — idêntico ao
padrão do Filament. Todos os métodos da `Table` estão disponíveis:

```php
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Tibras\ExpandableTable\Contracts\HasExpandedTable;

class OrderItemsTable implements HasExpandedTable
{
    public function table(Table $table, Model $record): Table
    {
        return $table
            ->query(
                OrderItem::query()->where('order_id', $record->id)
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qtd')
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Preço Un.')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderItemStatus::class),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated([5, 10, 25])
            ->striped()
            ->defaultSort('product_name');
    }
}
```

### 2. Adicionar o `ExpandAction` na tabela pai

Nenhum trait necessário no `ListRecords`:

```php
use Tibras\ExpandableTable\Actions\ExpandAction;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getTableActions(): array
    {
        return [
            ExpandAction::make()
                ->detailComponent(OrderItemsTable::class),
        ];
    }
}
```

### 3. Exibir a sub-table na view

Adicione o snippet após cada linha na view da tabela:

```blade
<tr x-show="$store.expandableTable.isExpanded('{{ $record->getKey() }}')">
    <td colspan="99" class="p-0">
        @livewire(
            'expandable-table::expanded-table-detail',
            [
                'recordClass'     => get_class($record),
                'recordKey'       => $record->getKey(),
                'detailComponent' => \App\Filament\Tables\OrderItemsTable::class,
            ],
            key('expand-' . $record->getKey())
        )
    </td>
</tr>
```

> `key('expand-' . $record->getKey())` é obrigatório — isola o estado Livewire de cada linha.

## Como funciona

```
Alpine.store('expandableTable')      ← estado client-side (zero round-trip)
│
ExpandAction (botão chevron)         ← toggle instantâneo via $store
│
<tr x-show="isExpanded(key)">        ← visibilidade via CSS
    └── ExpandedTableDetail          ← Livewire component com tabela Filament completa
            └── HasExpandedTable     ← dev implementa table() com API completa do Filament
```

## API

### `ExpandAction`

| Método | Descrição |
|---|---|
| `->detailComponent(string\|Closure)` | FQCN da classe que implementa `HasExpandedTable` |

### `HasExpandedTable` (contrato)

```php
interface HasExpandedTable
{
    public function table(Table $table, Model $record): Table;
}
```

O `$table` é a instância do Filament `Table`. O `$record` é o model da linha pai.
Configure tudo via fluent API do Filament — `->query()`, `->columns()`,
`->filters()`, `->actions()`, `->bulkActions()`, `->groups()`, `->defaultSort()`, etc.

## Troubleshooting

**Sub-table não aparece:** verifique se o `key()` está presente no `@livewire` e se o `x-show` está na `<tr>`.

**CSS não carrega:** execute `php artisan filament:assets` e verifique que `x-load-css` está na view do componente.

**Botão não reage:** confirme que o JS do pacote está sendo carregado (`php artisan filament:assets`).

**Performance com muitas linhas:** os componentes Livewire são montados junto com a página. Mantenha a paginação do pai em ≤ 25 registros.

## Licença

MIT — TIBRAS
