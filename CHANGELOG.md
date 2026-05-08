# Changelog

## [Unreleased]

### Added
- `HasExpandableRows` trait com toggle de estado por linha via evento Livewire 4
- `ExpandAction` estendendo `Filament\Tables\Actions\Action` com ícone dinâmico e tooltip i18n
- `ExpandedTableDetail` componente Livewire que renderiza sub-table Filament independente
- `HasExpandedTable` contrato para definição de query e colunas da sub-table
- `ExpandableTableServiceProvider` com registro de CSS via `FilamentAsset` e `Blade::directive('@expandableRow')`
- CSS com suporte a tema claro e escuro usando variáveis nativas do Filament
- Traduções em `pt_BR` e `en`
- CI via GitHub Actions (PHP 8.3/8.4 × Laravel 11/12)
