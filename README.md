# filament-expandable-table

Adiciona **linhas expansíveis** às tabelas do Filament 5. Ao clicar no botão de expansão, a linha abre uma sub-tabela completa — com colunas, filtros, ordenação e paginação — diretamente abaixo dela, sem modal e sem recarregar a página.

> **Caso de uso típico:** agenda de contatos onde cada pessoa expande para mostrar seus telefones, e-mails ou histórico de interações.

---

## O que esse plugin faz?

Sem ele, exibir registros relacionados numa tabela Filament exige abrir um modal ou navegar para outra página. Com o plugin, o usuário clica num botão (+) na linha e uma segunda tabela aparece logo abaixo, dentro da própria página.

```
┌──────────────────────────────────────────────────────────┐
│  # │ Nome            │ Empresa         │ Cidade   │ [+]  │
├──────────────────────────────────────────────────────────┤
│  1 │ João Silva      │ Acme Ltda       │ SP       │ [+]  │
├──────────────────────────────────────────────────────────┤
│    ↳ Telefones de João Silva                             │
│    ┌───────────────┬────────────┬──────────────────────┐ │
│    │ Tipo          │ Número     │ WhatsApp             │ │
│    ├───────────────┼────────────┼──────────────────────┤ │
│    │ Celular       │ 11 9xxxx   │ Sim                  │ │
│    │ Comercial     │ 11 3xxx    │ Não                  │ │
│    └───────────────┴────────────┴──────────────────────┘ │
├──────────────────────────────────────────────────────────┤
│  2 │ Maria Souza    │ Beta Corp       │ RJ       │ [+]  │
└──────────────────────────────────────────────────────────┘
```

A expansão é instantânea (sem chamada ao servidor para abrir/fechar). Os dados da sub-tabela são carregados sob demanda pelo Livewire.

---

## Requisitos

Você precisa ter um projeto Laravel com o Filament 5 já instalado e funcionando.

| Dependência | Versão mínima |
|---|---|
| PHP | 8.3+ |
| Laravel | 11 ou 12 |
| Filament | 5.x |
| Livewire | 4.x (vem com o Filament 5) |

---

## Instalação

Execute os dois comandos abaixo no terminal, dentro da pasta do seu projeto:

```bash
composer require gtcesar/filament-expandable-table
php artisan filament:assets
```

O primeiro instala o pacote. O segundo publica os arquivos de CSS e JS que o plugin usa para a animação e o estilo das linhas expansíveis.

> **Nenhuma configuração adicional.** O Laravel registra o plugin automaticamente. Não é preciso editar `AppServiceProvider`, `PanelProvider` nem nenhum outro arquivo de configuração.

---

## Configuração passo a passo

Vamos usar o exemplo de uma **agenda de contatos**: uma tabela de Contatos onde cada linha expande para mostrar os telefones cadastrados para aquela pessoa.

### Passo 1 — Criar a classe que define a sub-tabela

Crie um arquivo PHP em qualquer lugar do seu projeto (sugerimos `app/Filament/Tables/`). Esse arquivo descreve a sub-tabela: quais colunas mostrar, quais filtros aplicar e como buscar os registros relacionados.

```php
<?php

// app/Filament/Tables/ContactPhonesTable.php

namespace App\Filament\Tables;

use App\Models\Phone;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Tibras\ExpandableTable\Contracts\HasExpandedTable;

class ContactPhonesTable implements HasExpandedTable
{
    public function table(Table $table, Model $record): Table
    {
        // $record é a linha da tabela PAI (o Contato).
        // Use $record->id para buscar apenas os telefones daquele contato.
        return $table
            ->query(
                Phone::query()->where('contact_id', $record->id)
            )
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('number')
                    ->label('Número')
                    ->copyable()
                    ->sortable(),
                IconColumn::make('has_whatsapp')
                    ->label('WhatsApp')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'mobile'       => 'Celular',
                        'home'         => 'Residencial',
                        'commercial'   => 'Comercial',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated([5, 10])
            ->defaultSort('type');
    }
}
```

> **O que é `implements HasExpandedTable`?** É uma forma do PHP garantir que a sua classe tem o método `table()` que o plugin precisa chamar. Pense como um contrato: o plugin sabe que pode pedir a qualquer classe que implemente `HasExpandedTable` para montar uma tabela.

---

### Passo 2 — Adicionar o botão de expansão na tabela pai

No seu `ListResource` (o arquivo da página de listagem do recurso), adicione o `ExpandAction` ao array de ações da tabela:

```php
<?php

// app/Filament/Resources/ContactResource/Pages/ListContacts.php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Filament\Tables\ContactPhonesTable;
use Filament\Resources\Pages\ListRecords;
use Tibras\ExpandableTable\Actions\ExpandAction;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getTableActions(): array
    {
        return [
            ExpandAction::make()
                ->detailComponent(ContactPhonesTable::class),
            // Outras ações (EditAction, DeleteAction, etc.) podem ficar aqui também.
        ];
    }
}
```

Isso adiciona um botão **[+]** em cada linha da tabela de contatos. Ao clicar, ele sinaliza via JavaScript que aquela linha deve ser expandida.

---

### Passo 3 — Adicionar a sub-tabela na view da tabela

Este é o passo que "fecha o circuito": você precisa dizer ao Filament onde renderizar a sub-tabela quando uma linha for expandida.

**Como encontrar o arquivo de view correto:**

Rode o comando abaixo para publicar as views do Filament (se ainda não fez isso):

```bash
php artisan filament:publish --views
```

A view que você precisa editar está em:

```
resources/views/vendor/filament-tables/index.blade.php
```

Dentro desse arquivo, localize o `<tr>` que renderiza cada linha de dado (geralmente há um `@foreach` iterando sobre os registros). Logo **após** o `<tr>` da linha, adicione o bloco abaixo:

```blade
{{-- Linha de expansão (fica invisível até o botão [+] ser clicado) --}}
<tr x-show="$store.expandableTable.isExpanded('{{ $this->getId() }}.{{ $record->getKey() }}')">
    <td colspan="99" class="p-0">
        @livewire(
            'expandable-table::expanded-table-detail',
            [
                'recordClass'     => get_class($record),
                'recordKey'       => $record->getKey(),
                'detailComponent' => \App\Filament\Tables\ContactPhonesTable::class,
            ],
            key('expand-' . $record->getKey())
        )
    </td>
</tr>
```

> **Por que `colspan="99"`?** Para a célula da sub-tabela ocupar toda a largura da tabela, independente de quantas colunas existam na tabela pai.
>
> **Por que `key('expand-' . $record->getKey())`?** O Livewire usa essa chave para manter o estado de cada sub-tabela separado. Sem ela, as sub-tabelas de linhas diferentes interferem entre si.

---

## Opção: expandir uma linha automaticamente ao carregar a página

Se você quiser que certas linhas já abram expandidas quando a página carrega (por exemplo, o contato marcado como favorito), use `->startExpanded()`:

```php
ExpandAction::make()
    ->detailComponent(ContactPhonesTable::class)
    ->startExpanded(fn (Contact $record): bool => $record->is_favorite),
```

Ou passe `true` para expandir todas as linhas por padrão:

```php
->startExpanded()  // equivalente a ->startExpanded(true)
```

---

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
| `$record` | O model da linha pai (ex.: o Contato). Use para filtrar os registros da sub-tabela. |

---

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
Livewire carrega o componente ExpandedTableDetail
        │
        ▼
ExpandedTableDetail chama ContactPhonesTable::table()
        │
        ▼
Sub-tabela Filament completa é renderizada dentro da linha
```

**Por que é rápido?** O botão de abrir/fechar não vai ao servidor — Alpine.js faz isso localmente no navegador. O Livewire só entra em cena para carregar os dados quando a linha ainda não foi aberta naquela sessão.

---

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
    ->detailComponent(fn (Contact $record): string => match ($record->category) {
        'company' => CompanyContactsTable::class,
        default   => ContactPhonesTable::class,
    }),
```

---

## Licença

MIT — [Augusto César (gtcesar)](https://github.com/gtcesar)
