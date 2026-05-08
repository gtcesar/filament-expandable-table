# SPEC: tibras/filament-expandable-table

**Versão:** 1.0.0-draft  
**Data:** 2026-05-08  
**Status:** Pronto para implementação

---

## Visão Geral

Pacote Composer standalone para Filament 5 / Livewire 4 que adiciona suporte a
**linhas expansíveis com sub-table** em qualquer `ListRecords` ou componente
Livewire que use `InteractsWithTable`. Ao clicar em uma ação configurável na
linha, ela se expande e exibe uma tabela Filament independente (com paginação e
busca global) diretamente abaixo da linha pai, sem redirecionar ou abrir modal.

### Comportamento Esperado

1. A lista principal renderiza normalmente.
2. Cada linha tem um botão de expansão (chevron) na coluna de ações.
3. Ao clicar, o estado `expandedRows[key]` alterna via evento Livewire.
4. A view renderiza condicionalmente um componente `@livewire` abaixo da linha.
5. O componente filho busca seus próprios dados e renderiza uma tabela Filament completa.
6. Estado de expansão persiste durante paginação da tabela pai (state no componente pai).

---

## Requisitos de Ambiente

| Dependência | Versão mínima |
|---|---|
| PHP | 8.3+ |
| Laravel | 11 ou 12 |
| Filament | 5.x |
| Livewire | 4.x |
| Spatie Laravel Package Tools | ^1.16 |
| Node.js / npm | 20+ (apenas para build do CSS) |

---

## Nome do Pacote

```
tibras/filament-expandable-table
```

Namespace PHP raiz: `Tibras\ExpandableTable`

---

## Estrutura de Diretórios

```
tibras/filament-expandable-table/
│
├── composer.json
├── package.json
├── postcss.config.js
│
├── src/
│   ├── ExpandableTableServiceProvider.php
│   ├── Actions/
│   │   └── ExpandAction.php
│   ├── Concerns/
│   │   └── HasExpandableRows.php
│   ├── Livewire/
│   │   └── ExpandedTableDetail.php
│   └── Contracts/
│       └── HasExpandedTable.php
│
├── resources/
│   ├── views/livewire/
│   │   └── expanded-table-detail.blade.php
│   ├── lang/
│   │   ├── en/expandable-table.php
│   │   └── pt_BR/expandable-table.php
│   ├── css/expandable-table.css      ← fonte
│   └── dist/expandable-table.css     ← compilado (npm run build)
│
├── tests/
│   ├── Pest.php
│   ├── TestCase.php
│   └── Feature/
│       ├── ExpandActionTest.php
│       ├── HasExpandableRowsTest.php
│       └── ExpandedTableDetailTest.php
│
└── README.md
```

---

## composer.json

```json
{
    "name": "tibras/filament-expandable-table",
    "description": "Expandable master-detail rows with nested Filament tables",
    "type": "library",
    "license": "MIT",
    "authors": [{ "name": "TIBRAS", "email": "contato@tibras.com.br" }],
    "require": {
        "php": "^8.3",
        "filament/filament": "^5.0",
        "spatie/laravel-package-tools": "^1.16"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-livewire": "^2.0"
    },
    "autoload": {
        "psr-4": { "Tibras\\ExpandableTable\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "Tibras\\ExpandableTable\\Tests\\": "tests/" }
    },
    "extra": {
        "laravel": {
            "providers": ["Tibras\\ExpandableTable\\ExpandableTableServiceProvider"]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

---

## package.json

```json
{
    "private": true,
    "scripts": {
        "build": "postcss resources/css/expandable-table.css -o resources/dist/expandable-table.css",
        "dev": "postcss resources/css/expandable-table.css -o resources/dist/expandable-table.css --watch"
    },
    "devDependencies": {
        "cssnano": "^6.0.1",
        "postcss": "^8.4.27",
        "postcss-cli": "^10.1.0",
        "postcss-nesting": "^13.0.0"
    }
}
```

---

## postcss.config.js

```js
module.exports = {
    plugins: [
        require('postcss-nesting')(),
        require('cssnano')({ preset: 'default' }),
    ],
};
```

---

## ExpandableTableServiceProvider

Estende `Spatie\LaravelPackageTools\PackageServiceProvider`.
Segue o padrão do skeleton oficial (`filamentphp/plugin-skeleton`, branch `5.x`):
assets via `getAssets()` / `getAssetPackageName()`, `packageRegistered()` explícito
e `$viewNamespace` estático.

```php
<?php

namespace Tibras\ExpandableTable;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tibras\ExpandableTable\Livewire\ExpandedTableDetail;

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

        Livewire::component(
            'expandable-table::expanded-table-detail',
            ExpandedTableDetail::class
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
            Css::make('filament-expandable-table', __DIR__ . '/../resources/dist/expandable-table.css')
                ->loadedOnRequest(),
        ];
    }
}
```

---

## Contrato: HasExpandedTable

Expõe a API completa do Filament `Table` — colunas, filtros, ações, bulk
actions, ordenação, busca e paginação ficam a cargo do desenvolvedor.

```php
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
```

---

## ExpandAction

Estado gerenciado via `Alpine.store('expandableTable')` — sem trait Livewire no pai.
Ícone, tooltip e visibilidade controlados client-side via `extraAttributes()`.

```php
<?php

namespace Tibras\ExpandableTable\Actions;

use Closure;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ExpandAction extends Action
{
    protected string|Closure|null $detailComponent = null;

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
            ->icon('heroicon-o-chevron-down')
            ->extraAttributes(function (Model $record): array {
                $key      = $record->getKey();
                $expand   = __('expandable-table::expandable-table.expand');
                $collapse = __('expandable-table::expandable-table.collapse');

                return [
                    'x-on:click.stop' => "\$store.expandableTable.toggle('{$key}')",
                    'x-bind:class'    => "\$store.expandableTable.isExpanded('{$key}') ? 'rotate-180 transition-transform duration-200' : 'transition-transform duration-200'",
                    'x-bind:title'    => "\$store.expandableTable.isExpanded('{$key}') ? '{$collapse}' : '{$expand}'",
                ];
            })
            // Toggle é client-side; action vazia evita erros do Filament.
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
}
```

---

## ExpandedTableDetail (Livewire Component)

```php
<?php

namespace Tibras\ExpandableTable\Livewire;

use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Tibras\ExpandableTable\Contracts\HasExpandedTable;

class ExpandedTableDetail extends Component implements HasTable
{
    use InteractsWithTable;

    /** FQCN do Model pai — serializar apenas a classe + PK, não o objeto */
    public string $recordClass;
    public string|int $recordKey;
    public string $detailComponent;

    protected Model $record;

    public function mount(
        string $recordClass,
        string|int $recordKey,
        string $detailComponent
    ): void {
        $this->recordClass     = $recordClass;
        $this->recordKey       = $recordKey;
        $this->detailComponent = $detailComponent;

        // Reidratar o model — evita serialização pesada de props públicas
        $this->record = $recordClass::findOrFail($recordKey);
    }

    public function table(Table $table): Table
    {
        /** @var HasExpandedTable $instance */
        $instance = app($this->detailComponent);

        return $instance->table($table, $this->record);
    }

    public function render(): \Illuminate\View\View
    {
        return view('expandable-table::livewire.expanded-table-detail');
    }
}
```

**Regra crítica de serialização:**
Nunca passar o Model Eloquent como propriedade pública do Livewire. Passar
apenas `$recordClass` (FQCN string) e `$recordKey` (PK), e reidratar com
`findOrFail()` no `mount()`.

---

## View: expanded-table-detail.blade.php

```blade
<div
    x-data
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref(
        'filament-expandable-table',
        package: 'tibras/filament-expandable-table'
    ))]"
    class="expandable-table-detail"
>
    {{ $this->table }}
</div>
```

---

## Integração no Componente Pai — renderizar a sub-table

### Contexto: Render Hooks no Filament 5

**Confirmado (verificado em 2026-05-08):** O Filament 5 não possui render hook
de nível de linha (`TABLE_ROW_AFTER` ou similar) que forneça o `$record` no
escopo. Os hooks disponíveis em `TablesRenderHook` são limitados a:
header, toolbar, filtros e indicadores de seleção.

**Consequência:** A única abordagem suportada é a **Opção B** descrita abaixo.

---

### Opção B — View Customizada (abordagem adotada)

O desenvolvedor publica a view da tabela e adiciona o snippet abaixo após cada
linha. Esta é a abordagem documentada e suportada pelo pacote.

**Passo 1 — Publicar a view da tabela Filament** (se necessário):

```bash
php artisan vendor:publish --tag="filament-tables-views"
```

**Passo 2 — Inserir o snippet na view de cada linha:**

O `x-show` usa o Alpine store para mostrar/ocultar sem round-trip ao servidor.

```blade
{{-- resources/views/vendor/filament-tables/components/row.blade.php --}}
{{-- ...conteúdo original da row... --}}

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

> **`key('expand-' . $record->getKey())` é obrigatório** — garante componente
> Livewire isolado por linha e evita conflitos de estado entre linhas.

---

## CSS (resources/css/expandable-table.css)

```css
.expandable-table-detail {
    padding: 1rem 1.5rem;
    background-color: var(--color-gray-50);
    border-top: 1px solid var(--color-gray-200);
    border-bottom: 1px solid var(--color-gray-200);

    & .fi-ta-wrp {
        border-radius: 0.5rem;
        box-shadow: none;
        border: 1px solid var(--color-gray-200);
    }
}

.dark .expandable-table-detail {
    background-color: var(--color-gray-900);
    border-color: var(--color-gray-700);

    & .fi-ta-wrp {
        border-color: var(--color-gray-700);
    }
}

.expandable-row-container td {
    padding: 0 !important;
}
```

**Regras de CSS:**
- Filament 5 usa Tailwind v4 — variáveis de cor têm prefixo `--color-` (ex: `--color-gray-50`).
- Usar apenas variáveis `--color-gray-*` (estáveis entre versões 5.x).
- Testar tema claro e escuro antes de release.
- Nunca usar `!important` exceto para resetar estilos de `<td>` de tabelas.

---

## Arquivos de Tradução

### resources/lang/pt_BR/expandable-table.php
```php
<?php
return [
    'expand'   => 'Expandir detalhes',
    'collapse' => 'Recolher detalhes',
];
```

### resources/lang/en/expandable-table.php
```php
<?php
return [
    'expand'   => 'Expand details',
    'collapse' => 'Collapse details',
];
```

---

## Estratégia de Testes

### Configuração (tests/TestCase.php)

```php
<?php

namespace Tibras\ExpandableTable\Tests;

use Filament\FilamentServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tibras\ExpandableTable\ExpandableTableServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            ExpandableTableServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
```

### tests/Pest.php

```php
<?php

use Tibras\ExpandableTable\Tests\TestCase;

uses(TestCase::class)->in('Feature');
```

### Casos de Teste Mínimos

#### Feature/ExpandActionTest.php

```php
<?php

use Tibras\ExpandableTable\Actions\ExpandAction;

it('has default name expand', function () {
    expect(ExpandAction::getDefaultName())->toBe('expand');
});

it('stores the detail component class', function () {
    $action = ExpandAction::make()
        ->detailComponent('App\\Tables\\OrderItemsTable');

    expect($action->getDetailComponent())->toBe('App\\Tables\\OrderItemsTable');
});

it('accepts a closure for detail component', function () {
    $action = ExpandAction::make()
        ->detailComponent(fn () => 'App\\Tables\\OrderItemsTable');

    expect($action->getDetailComponent())->toBe('App\\Tables\\OrderItemsTable');
});

it('generates alpine click handler in extra attributes', function () {
    $record = new class {
        public function getKey(): int { return 42; }
    };

    $attrs = ExpandAction::make()->getExtraAttributes($record);

    expect($attrs)->toHaveKey('x-on:click.stop')
        ->and($attrs['x-on:click.stop'])->toContain("expandableTable.toggle('42')");
});

it('generates alpine class binding in extra attributes', function () {
    $record = new class {
        public function getKey(): int { return 7; }
    };

    $attrs = ExpandAction::make()->getExtraAttributes($record);

    expect($attrs)->toHaveKey('x-bind:class')
        ->and($attrs['x-bind:class'])->toContain("expandableTable.isExpanded('7')");
});
```

#### Feature/ExpandedTableDetailTest.php

```php
<?php

use Tibras\ExpandableTable\Livewire\ExpandedTableDetail;

it('renders expanded table detail component', function () {
    // Requer Livewire test utilities — configurar fixtures no TestCase
    Livewire::test(ExpandedTableDetail::class, [
        'recordClass'     => \App\Models\Order::class,
        'recordKey'       => 1,
        'detailComponent' => \App\Tables\OrderItemsTable::class,
    ])->assertSuccessful();
});
```

---

## GitHub Actions CI (.github/workflows/tests.yml)

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php: [8.3, 8.4]
        laravel: [11.*, 12.*]

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pdo, sqlite, pdo_sqlite

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" --no-interaction --no-update
          composer update --prefer-dist --no-interaction

      - name: Build CSS
        run: |
          npm ci
          npm run build

      - name: Run tests
        run: ./vendor/bin/pest
```

---

## Instalação em Desenvolvimento (path repository)

No `composer.json` do projeto Laravel:

```json
{
    "repositories": [
        { "type": "path", "url": "../packages/filament-expandable-table" }
    ],
    "require": {
        "tibras/filament-expandable-table": "@dev"
    }
}
```

```bash
composer require tibras/filament-expandable-table
php artisan filament:assets
```

---

## Exemplo de Uso Completo

```php
// 1. ListRecords — usar o trait e a action
use Tibras\ExpandableTable\Concerns\HasExpandableRows;
use Tibras\ExpandableTable\Actions\ExpandAction;

class ListOrders extends ListRecords
{
    use HasExpandableRows;

    protected static string $resource = OrderResource::class;

    protected function getTableActions(): array
    {
        return [
            ExpandAction::make()
                ->detailComponent(OrderItemsTable::class)
                ->startExpanded(false),
        ];
    }
}

// 2. Classe de sub-table — API completa do Filament Table
use Tibras\ExpandableTable\Contracts\HasExpandedTable;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderItemsTable implements HasExpandedTable
{
    public function table(Table $table, Model $record): Table
    {
        return $table
            ->query(OrderItem::query()->where('order_id', $record->id))
            ->columns([
                TextColumn::make('product_name')->label('Produto')->searchable()->sortable(),
                TextColumn::make('quantity')->label('Qtd')->sortable(),
                TextColumn::make('unit_price')->label('Preço Un.')->money('BRL')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderItemStatus::class),
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

---

## Troubleshooting

| Sintoma | Causa Provável | Solução |
|---|---|---|
| Sub-table não aparece | `key()` ausente no `@livewire` | Adicionar `key('expand-' . $record->getKey())` |
| Botão não reage | Alpine store não carregado | Executar `php artisan filament:assets` |
| CSS não carrega | `x-load-css` ausente na view | Verificar `x-load-css` no wrapper `expanded-table-detail` |
| Múltiplas sub-tables colidem | Falta `key()` único | `key('expand-' . $record->getKey())` obrigatório |
| Erro de serialização Livewire | Model passado como prop pública | Usar `$recordClass` + `$recordKey` + `findOrFail()` |
| Query N+1 na sub-table | Eager loading ausente | Eager load no `table()` do `HasExpandedTable` |
| Performance com muitas linhas | Livewires montados sem lazy | Manter paginação do pai ≤ 25 registros |

---

## Pontos Críticos / Riscos

| # | Risco | Status | Mitigação |
|---|---|---|---|
| 1 | Render hook `TABLE_ROW_AFTER` no Filament 5 | **Confirmado ausente** | Opção B (view customizada) adotada |
| 2 | Serialização pesada do Model como prop Livewire | Mitigado | Usar `$recordClass` + `$recordKey` + `findOrFail()` |
| 3 | Conflito de componentes Livewire com múltiplas linhas | Mitigado | `key('expand-' . $record->getKey())` obrigatório |
| 4 | Performance: Livewire monta todos os componentes da página | Documentado | Manter paginação ≤ 25; lazy loading planejado |
| 5 | Variáveis CSS do Filament mudarem entre versões 5.x | Baixo risco | Usar `--color-gray-*` (Tailwind v4); testar claro e escuro |
| 6 | Alpine store não inicializado antes do primeiro clique | Mitigado | JS registrado sem `loadedOnRequest()` — carrega em toda página Filament |

---

## Checklist de Entrega

### Infraestrutura
- [ ] `composer.json` com autoload PSR-4 e `extra.laravel.providers`
- [ ] `package.json` + `postcss.config.js` configurados
- [ ] `npm run build` gera `resources/dist/expandable-table.css`

### PHP
- [ ] `ExpandableTableServiceProvider` estendendo `PackageServiceProvider` do Spatie
- [ ] JS registrado via `Js::make()` (sem `loadedOnRequest`) — Alpine store
- [ ] CSS registrado com `loadedOnRequest()` e package `tibras/filament-expandable-table`
- [ ] Livewire component registrado como `expandable-table::expanded-table-detail`
- [ ] `HasExpandedTable` interface com método `table(Table $table, Model $record): Table`
- [ ] `ExpandAction` com `getDefaultName()`, `setUp()` e `extraAttributes()` Alpine
- [ ] `ExpandedTableDetail` usando `recordClass` + `recordKey` (sem serializar Model)
- [ ] `ExpandedTableDetail::table()` delega para `$instance->table($table, $this->record)`

### Views & Assets
- [ ] `resources/js/expandable-table.js` com Alpine store `expandableTable`
- [ ] `npm run build` copia JS para `resources/dist/`
- [ ] View com `x-load-css` e `FilamentAsset::getStyleHref()` correto
- [ ] CSS fonte + compilado (`dist/`) presentes no repositório
- [ ] Snippet de integração com `x-show` e `key()` documentado

### i18n
- [ ] Lang `pt_BR` criado com `expand` e `collapse`
- [ ] Lang `en` criado com `expand` e `collapse`

### Testes
- [ ] `TestCase.php` configurado com providers corretos
- [ ] `Pest.php` apontando para `Feature/`
- [ ] `HasExpandableRowsTest` cobrindo toggle, independência de linhas, estado inicial
- [ ] `ExpandActionTest` cobrindo nome padrão, `detailComponent`, `startExpanded`
- [ ] CI (GitHub Actions) verde em PHP 8.3 + 8.4 × Laravel 11 + 12

### Documentação
- [ ] README com: descrição, instalação, exemplo de uso mínimo, troubleshooting
- [ ] Seção de integração de view documentada (snippet do `@livewire` + `key()`)
- [ ] CHANGELOG.md com entrada `1.0.0`
