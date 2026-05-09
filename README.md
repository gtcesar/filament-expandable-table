<div align="center">

# filament-expandable-table

**Expandable master-detail rows with nested Filament tables**

[![Packagist](https://img.shields.io/packagist/v/gtcesar/filament-expandable-table)](https://packagist.org/packages/gtcesar/filament-expandable-table)
[![License](https://img.shields.io/github/license/gtcesar/filament-expandable-table)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net)
[![Filament](https://img.shields.io/badge/Filament-5.x-orange)](https://filamentphp.com)

---

🇺🇸 [English](#english) &nbsp;|&nbsp; 🇧🇷 [Português](#português)

</div>

---

<a name="english"></a>
# 🇺🇸 English

## What does this plugin do?

It adds **expandable rows** to Filament 5 tables. When the user clicks the expand button on a row, a full Filament table appears directly below it — no modal, no page redirect.

```
┌─────────────────────────────────────────────────────────┐
│  # │ Customer       │ Total    │ Status   │      [+]    │
├─────────────────────────────────────────────────────────┤
│  1 │ John Smith     │ $120.00  │ Paid     │      [+]    │
├─────────────────────────────────────────────────────────┤
│    ↳ Items for Order #1                                  │
│    ┌──────────────────┬──────┬────────────────────────┐ │
│    │ Product          │ Qty  │ Unit Price             │ │
│    ├──────────────────┼──────┼────────────────────────┤ │
│    │ Blue T-Shirt (M) │  2   │ $ 30.00                │ │
│    │ Slim Jeans       │  1   │ $ 60.00                │ │
│    └──────────────────┴──────┴────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│  2 │ Mary Johnson    │ $200.00  │ Pending  │      [+]    │
└─────────────────────────────────────────────────────────┘
```

The expand/collapse toggle is **instant** (no server round-trip). Sub-table data is loaded on demand by Livewire.

The sub-table is a full Filament table: **columns, filters, actions, bulk actions, sorting, search, and pagination** all work out of the box.

## Requirements

You need a Laravel project with Filament 5 already installed and working.

| Dependency | Min version |
|---|---|
| PHP | 8.3+ |
| Laravel | 11 or 12 |
| Filament | 5.x |
| Livewire | 4.x (bundled with Filament 5) |

## Installation

Run these two commands from your project root:

```bash
composer require gtcesar/filament-expandable-table
php artisan filament:assets
```

The first installs the package. The second publishes the CSS and JS assets the plugin needs for styling and animations.

> **No extra configuration needed.** Laravel auto-discovers the service provider. You do not need to touch `AppServiceProvider`, `PanelProvider`, or any config file.

## Step-by-step setup

We'll build an **Orders** table where each row expands to show its **Order Items**.

### Step 1 — Create the sub-table class

Create a PHP file anywhere in your project (we suggest `app/Filament/Tables/`). This class describes the sub-table: which columns to show, which filters to apply, and how to fetch the related records.

```php
<?php

// app/Filament/Tables/OrderItemsTable.php

namespace App\Filament\Tables;

use App\Models\OrderItem;
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
        // $record is the parent row (the Order).
        // Use $record->id to fetch only the items belonging to that order.
        return $table
            ->query(
                OrderItem::query()->where('order_id', $record->id)
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'shipped'   => 'Shipped',
                        'delivered' => 'Delivered',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated([5, 10, 25])
            ->defaultSort('product_name');
    }
}
```

> **What does `implements HasExpandedTable` mean?** It's a PHP contract that guarantees your class has the `table()` method the plugin needs to call. Think of it as a promise: the plugin knows it can ask any class that implements `HasExpandedTable` to build a table.

### Step 2 — Add the expand button to the parent table

In your `ListRecords` page, add `ExpandAction` to the table actions array:

```php
<?php

// app/Filament/Resources/OrderResource/Pages/ListOrders.php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Tables\OrderItemsTable;
use Filament\Resources\Pages\ListRecords;
use Tibras\ExpandableTable\Actions\ExpandAction;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getTableActions(): array
    {
        return [
            ExpandAction::make()
                ->detailComponent(OrderItemsTable::class),
            // Other actions (EditAction, DeleteAction, etc.) can go here too.
        ];
    }
}
```

This adds a **[+]** button to each row. Clicking it instantly toggles the row open or closed via Alpine.js — no server call needed.

### Step 3 — Render the sub-table in the view

This step "closes the loop": you tell Filament where to render the sub-table when a row is expanded.

**Finding the right view file:**

Publish Filament's views if you haven't already:

```bash
php artisan filament:publish --views
```

The file to edit is:

```
resources/views/vendor/filament-tables/index.blade.php
```

Inside that file, find the `<tr>` that renders each data row (look for a `@foreach` over the records). Add the following block **right after** that `<tr>`:

```blade
{{-- Expansion row — hidden until the [+] button is clicked --}}
<tr x-show="$store.expandableTable.isExpanded('{{ $this->getId() }}.{{ $record->getKey() }}')">
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

> **Why `colspan="99"`?** So the sub-table cell spans the full width of the parent table, regardless of how many columns it has.
>
> **Why `key('expand-' . $record->getKey())`?** Livewire uses this key to keep each row's sub-table state isolated. Without it, sub-tables from different rows interfere with each other.

## Optional: start a row expanded

To have certain rows open by default when the page loads (e.g., the most recent order), use `->startExpanded()`:

```php
ExpandAction::make()
    ->detailComponent(OrderItemsTable::class)
    ->startExpanded(fn (Order $record): bool => $record->is_latest),
```

Or pass `true` to expand all rows by default:

```php
->startExpanded()  // equivalent to ->startExpanded(true)
```

## API reference

### `ExpandAction`

| Method | Description |
|---|---|
| `->detailComponent(string\|Closure)` | The fully-qualified class name of the class implementing `HasExpandedTable`. Required. |
| `->startExpanded(bool\|Closure)` | If `true` (or if the Closure returns `true`), the row starts expanded. Default: `false`. |

### `HasExpandedTable` (interface)

```php
interface HasExpandedTable
{
    public function table(Table $table, Model $record): Table;
}
```

| Parameter | Description |
|---|---|
| `$table` | The Filament `Table` instance — use all the standard fluent methods: `->query()`, `->columns()`, `->filters()`, `->actions()`, `->bulkActions()`, `->defaultSort()`, etc. |
| `$record` | The parent row's Eloquent model (e.g., the Order). Use it to scope the sub-table query. |

## How it works

```
User clicks [+]
      │
      ▼
Alpine.js toggles store (no server round-trip)
      │
      ▼
<tr x-show="isExpanded(...)"> becomes visible via CSS
      │
      ▼
Livewire mounts ExpandedTableDetail component
      │
      ▼
ExpandedTableDetail calls OrderItemsTable::table()
      │
      ▼
Full Filament table rendered inside the row
```

## Troubleshooting

**Sub-table doesn't appear after clicking the button**

Check that:
1. `key('expand-' . $record->getKey())` is present in the `@livewire` call.
2. `x-show` is on the `<tr>` element itself, not on a wrapper inside it.

**Sub-table has no styling (CSS missing)**

Run `php artisan filament:assets` and verify `expandable-table.css` was published to `public/vendor/filament/`.

**Button exists but doesn't react to clicks**

The plugin's JavaScript may not be loaded. Run `php artisan filament:assets` again and hard-refresh your browser (`Ctrl+Shift+R`).

**Page is slow with many rows**

Livewire mounts one component per open row. Keep the parent table paginated to **25 records or fewer**.

**Can I use different sub-tables depending on the record?**

Yes. Use a Closure in `->detailComponent()`:

```php
ExpandAction::make()
    ->detailComponent(fn (Order $record): string => match ($record->type) {
        'wholesale' => WholesaleItemsTable::class,
        default     => OrderItemsTable::class,
    }),
```

## License

MIT — [Augusto César (gtcesar)](https://github.com/gtcesar)

---

<a name="português"></a>
# 🇧🇷 Português

## O que esse plugin faz?

Adiciona **linhas expansíveis** às tabelas do Filament 5. Ao clicar no botão de expansão, a linha abre uma tabela Filament completa diretamente abaixo dela — sem modal e sem recarregar a página.

```
┌──────────────────────────────────────────────────────────┐
│  # │ Cliente         │ Total    │ Status    │      [+]   │
├──────────────────────────────────────────────────────────┤
│  1 │ João Silva      │ R$120,00 │ Pago      │      [+]   │
├──────────────────────────────────────────────────────────┤
│    ↳ Itens do Pedido #1                                  │
│    ┌──────────────────┬──────┬──────────────────────┐   │
│    │ Produto          │ Qtd  │ Preço Un.            │   │
│    ├──────────────────┼──────┼──────────────────────┤   │
│    │ Camiseta Azul M  │  2   │ R$ 30,00             │   │
│    │ Calça Slim       │  1   │ R$ 60,00             │   │
│    └──────────────────┴──────┴──────────────────────┘   │
├──────────────────────────────────────────────────────────┤
│  2 │ Maria Souza     │ R$200,00 │ Pendente  │      [+]   │
└──────────────────────────────────────────────────────────┘
```

A expansão é **instantânea** (sem chamada ao servidor para abrir/fechar). Os dados da sub-tabela são carregados sob demanda pelo Livewire.

A sub-tabela é uma tabela Filament completa: **colunas, filtros, ações, bulk actions, ordenação, busca e paginação** funcionam normalmente.

## Requisitos

Você precisa ter um projeto Laravel com o Filament 5 já instalado e funcionando.

| Dependência | Versão mínima |
|---|---|
| PHP | 8.3+ |
| Laravel | 11 ou 12 |
| Filament | 5.x |
| Livewire | 4.x (vem com o Filament 5) |

## Instalação

Execute os dois comandos abaixo no terminal, dentro da pasta do seu projeto:

```bash
composer require gtcesar/filament-expandable-table
php artisan filament:assets
```

O primeiro instala o pacote. O segundo publica os arquivos de CSS e JS que o plugin usa para a animação e o estilo das linhas expansíveis.

> **Nenhuma configuração adicional.** O Laravel registra o plugin automaticamente. Não é preciso editar `AppServiceProvider`, `PanelProvider` nem nenhum outro arquivo de configuração.

## Configuração passo a passo

Vamos usar o exemplo de uma tabela de **Pedidos** onde cada linha expande para mostrar seus **Itens**.

### Passo 1 — Criar a classe que define a sub-tabela

Crie um arquivo PHP em qualquer lugar do seu projeto (sugerimos `app/Filament/Tables/`). Esse arquivo descreve a sub-tabela: quais colunas mostrar, quais filtros aplicar e como buscar os registros relacionados.

```php
<?php

// app/Filament/Tables/OrderItemsTable.php

namespace App\Filament\Tables;

use App\Models\OrderItem;
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
        // $record é a linha da tabela PAI (o Pedido).
        // Use $record->id para buscar apenas os itens daquele pedido.
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
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pendente',
                        'shipped'   => 'Enviado',
                        'delivered' => 'Entregue',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated([5, 10, 25])
            ->defaultSort('product_name');
    }
}
```

> **O que é `implements HasExpandedTable`?** É uma forma do PHP garantir que a sua classe tem o método `table()` que o plugin precisa chamar. Pense como um contrato: o plugin sabe que pode pedir a qualquer classe que implemente `HasExpandedTable` para montar uma tabela.

### Passo 2 — Adicionar o botão de expansão na tabela pai

No seu `ListRecords` (o arquivo da página de listagem do recurso), adicione o `ExpandAction` ao array de ações da tabela:

```php
<?php

// app/Filament/Resources/OrderResource/Pages/ListOrders.php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Tables\OrderItemsTable;
use Filament\Resources\Pages\ListRecords;
use Tibras\ExpandableTable\Actions\ExpandAction;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getTableActions(): array
    {
        return [
            ExpandAction::make()
                ->detailComponent(OrderItemsTable::class),
            // Outras ações (EditAction, DeleteAction, etc.) podem ficar aqui também.
        ];
    }
}
```

Isso adiciona um botão **[+]** em cada linha da tabela de pedidos. Ao clicar, ele abre ou fecha a linha instantaneamente via Alpine.js — sem chamada ao servidor.

### Passo 3 — Exibir a sub-tabela na view

Este é o passo que "fecha o circuito": você diz ao Filament onde renderizar a sub-tabela quando uma linha for expandida.

**Como encontrar o arquivo de view correto:**

Publique as views do Filament (se ainda não fez isso):

```bash
php artisan filament:publish --views
```

O arquivo a editar está em:

```
resources/views/vendor/filament-tables/index.blade.php
```

Dentro desse arquivo, localize o `<tr>` que renderiza cada linha de dado (geralmente há um `@foreach` sobre os registros). Logo **após** esse `<tr>`, adicione o bloco abaixo:

```blade
{{-- Linha de expansão — invisível até o botão [+] ser clicado --}}
<tr x-show="$store.expandableTable.isExpanded('{{ $this->getId() }}.{{ $record->getKey() }}')">
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

> **Por que `colspan="99"`?** Para a célula da sub-tabela ocupar toda a largura da tabela, independente de quantas colunas a tabela pai tiver.
>
> **Por que `key('expand-' . $record->getKey())`?** O Livewire usa essa chave para manter o estado de cada sub-tabela isolado. Sem ela, as sub-tabelas de linhas diferentes interferem entre si.

## Opção: expandir uma linha automaticamente

Para que certas linhas já abram expandidas quando a página carrega (por exemplo, o pedido mais recente), use `->startExpanded()`:

```php
ExpandAction::make()
    ->detailComponent(OrderItemsTable::class)
    ->startExpanded(fn (Order $record): bool => $record->is_latest),
```

Ou passe `true` para expandir todas as linhas por padrão:

```php
->startExpanded()  // equivalente a ->startExpanded(true)
```

## Referência da API

### `ExpandAction`

| Método | O que faz |
|---|---|
| `->detailComponent(string\|Closure)` | Informa qual classe PHP monta a sub-tabela. Obrigatório. |
| `->startExpanded(bool\|Closure)` | Se `true` (ou se a Closure retornar `true`), a linha começa expandida. Padrão: `false`. |

### `HasExpandedTable` (interface)

```php
interface HasExpandedTable
{
    public function table(Table $table, Model $record): Table;
}
```

| Parâmetro | Descrição |
|---|---|
| `$table` | Instância da tabela Filament — use os métodos fluentes normais: `->query()`, `->columns()`, `->filters()`, `->actions()`, `->bulkActions()`, `->defaultSort()`, etc. |
| `$record` | O model da linha pai (ex.: o Pedido). Use para filtrar os registros da sub-tabela. |

## Como funciona por baixo dos panos

```
Usuário clica no botão [+]
        │
        ▼
Alpine.js atualiza o store local (sem chamada ao servidor)
        │
        ▼
<tr x-show="isExpanded(...)"> torna-se visível via CSS
        │
        ▼
Livewire monta o componente ExpandedTableDetail
        │
        ▼
ExpandedTableDetail chama OrderItemsTable::table()
        │
        ▼
Tabela Filament completa renderizada dentro da linha
```

## Perguntas frequentes e problemas comuns

**A sub-tabela não aparece quando clico no botão**

Verifique duas coisas:
1. O `key('expand-' . $record->getKey())` está presente na diretiva `@livewire`.
2. O `x-show` está exatamente no elemento `<tr>` (não dentro de outro elemento).

**O visual da sub-tabela está sem estilo (sem CSS)**

Execute `php artisan filament:assets` e verifique que o arquivo `expandable-table.css` foi publicado em `public/vendor/filament/`.

**O botão existe mas não reage ao clique**

O JavaScript do plugin pode não estar carregado. Execute `php artisan filament:assets` novamente e limpe o cache do navegador (`Ctrl+Shift+R`).

**A página fica lenta com muitas linhas**

O Livewire instancia um componente para cada linha aberta. Mantenha a paginação da tabela pai em **25 registros ou menos** para evitar sobrecarga.

**Posso usar sub-tabelas diferentes dependendo do registro?**

Sim. Use uma Closure em `->detailComponent()`:

```php
ExpandAction::make()
    ->detailComponent(fn (Order $record): string => match ($record->type) {
        'wholesale' => WholesaleItemsTable::class,
        default     => OrderItemsTable::class,
    }),
```

## Licença

MIT — [Augusto César (gtcesar)](https://github.com/gtcesar)
