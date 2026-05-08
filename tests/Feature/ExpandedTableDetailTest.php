<?php

use Tibras\ExpandableTable\Livewire\ExpandedTableDetail;
use Tibras\ExpandableTable\Tests\Fixtures\Models\Order;
use Tibras\ExpandableTable\Tests\Fixtures\Models\OrderItem;
use Tibras\ExpandableTable\Tests\Fixtures\Tables\OrderItemsTable;

use function Pest\Livewire\livewire;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function makeOrder(string $customer = 'ACME'): Order
{
    return Order::create(['customer_name' => $customer]);
}

function makeItem(Order $order, array $attrs = []): OrderItem
{
    return OrderItem::create(array_merge([
        'order_id'     => $order->id,
        'product_name' => 'Widget',
        'quantity'     => 1,
        'unit_price'   => 9.99,
        'status'       => 'active',
    ], $attrs));
}

// ──────────────────────────────────────────────
// Renderização básica
// ──────────────────────────────────────────────

it('renders without exception', function () {
    $order = makeOrder();

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])->assertSuccessful();
});

it('renders the table column headers', function () {
    $order = makeOrder();

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->assertTableColumnExists('product_name')
        ->assertTableColumnExists('quantity')
        ->assertTableColumnExists('unit_price')
        ->assertTableColumnExists('status');
});

// ──────────────────────────────────────────────
// Isolamento de dados por registro pai
// ──────────────────────────────────────────────

it('shows only items belonging to the parent order', function () {
    $order      = makeOrder('Order A');
    $otherOrder = makeOrder('Order B');

    $myItem    = makeItem($order, ['product_name' => 'My Widget']);
    $otherItem = makeItem($otherOrder, ['product_name' => 'Other Widget']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->assertCanSeeTableRecords([$myItem])
        ->assertCanNotSeeTableRecords([$otherItem]);
});

it('shows the correct item count for the parent order', function () {
    $order = makeOrder();
    makeItem($order, ['product_name' => 'A']);
    makeItem($order, ['product_name' => 'B']);
    makeItem($order, ['product_name' => 'C']);

    $other = makeOrder('other');
    makeItem($other, ['product_name' => 'X']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])->assertCountTableRecords(3);
});

it('shows no records when the order has no items', function () {
    $order = makeOrder();

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])->assertCountTableRecords(0);
});

// ──────────────────────────────────────────────
// Hidratação do model (serialização segura)
// ──────────────────────────────────────────────

it('hydrates the parent record from database via recordClass and recordKey', function () {
    $order = makeOrder('Hydration Test');
    makeItem($order, ['product_name' => 'Hydrated Item']);

    // Passa FQCN + PK, nunca o Model serializado.
    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])->assertCanSeeTableRecords(
        OrderItem::where('order_id', $order->id)->get()
    );
});

it('throws ModelNotFoundException for an invalid record key', function () {
    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => 99999,
        'detailComponent' => OrderItemsTable::class,
    ]);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

// ──────────────────────────────────────────────
// Busca global (colunas searchable)
// ──────────────────────────────────────────────

it('filters records by global search on product_name', function () {
    $order = makeOrder();
    $alpha = makeItem($order, ['product_name' => 'Alpha Widget']);
    $beta  = makeItem($order, ['product_name' => 'Beta Widget']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$alpha])
        ->assertCanNotSeeTableRecords([$beta]);
});

it('returns all records when search is cleared', function () {
    $order = makeOrder();
    $alpha = makeItem($order, ['product_name' => 'Alpha']);
    $beta  = makeItem($order, ['product_name' => 'Beta']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->searchTable('Alpha')
        ->assertCountTableRecords(1)
        ->searchTable('')
        ->assertCountTableRecords(2);
});

// ──────────────────────────────────────────────
// Filtros
// ──────────────────────────────────────────────

it('filters by status active', function () {
    $order     = makeOrder();
    $active    = makeItem($order, ['product_name' => 'Active Item', 'status' => 'active']);
    $cancelled = makeItem($order, ['product_name' => 'Cancelled Item', 'status' => 'cancelled']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->filterTable('status', 'active')
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$cancelled]);
});

it('filters by status cancelled', function () {
    $order     = makeOrder();
    $active    = makeItem($order, ['product_name' => 'Active Item', 'status' => 'active']);
    $cancelled = makeItem($order, ['product_name' => 'Cancelled Item', 'status' => 'cancelled']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->filterTable('status', 'cancelled')
        ->assertCanSeeTableRecords([$cancelled])
        ->assertCanNotSeeTableRecords([$active]);
});

it('shows all records when filter is reset', function () {
    $order = makeOrder();
    makeItem($order, ['status' => 'active']);
    makeItem($order, ['status' => 'cancelled']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->filterTable('status', 'active')
        ->assertCountTableRecords(1)
        ->resetTableFilters()
        ->assertCountTableRecords(2);
});

// ──────────────────────────────────────────────
// Ordenação
// ──────────────────────────────────────────────

it('sorts by product_name ascending', function () {
    $order = makeOrder();
    $b     = makeItem($order, ['product_name' => 'Banana']);
    $a     = makeItem($order, ['product_name' => 'Apple']);
    $c     = makeItem($order, ['product_name' => 'Cherry']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->sortTable('product_name', 'asc')
        ->assertCanSeeTableRecords([$a, $b, $c], inOrder: true);
});

it('sorts by product_name descending', function () {
    $order = makeOrder();
    $b     = makeItem($order, ['product_name' => 'Banana']);
    $a     = makeItem($order, ['product_name' => 'Apple']);
    $c     = makeItem($order, ['product_name' => 'Cherry']);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->sortTable('product_name', 'desc')
        ->assertCanSeeTableRecords([$c, $b, $a], inOrder: true);
});

it('sorts by quantity ascending', function () {
    $order = makeOrder();
    $q3    = makeItem($order, ['product_name' => 'C', 'quantity' => 3]);
    $q1    = makeItem($order, ['product_name' => 'A', 'quantity' => 1]);
    $q2    = makeItem($order, ['product_name' => 'B', 'quantity' => 2]);

    livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ])
        ->sortTable('quantity', 'asc')
        ->assertCanSeeTableRecords([$q1, $q2, $q3], inOrder: true);
});

// ──────────────────────────────────────────────
// Paginação
// ──────────────────────────────────────────────

it('paginates results', function () {
    $order = makeOrder();

    foreach (range(1, 7) as $i) {
        makeItem($order, ['product_name' => "Item {$i}"]);
    }

    $component = livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $order->id,
        'detailComponent' => OrderItemsTable::class,
    ]);

    // Primeira página (padrão 5 por página)
    $component->assertCountTableRecords(5);

    // Segunda página
    $component->call('nextPage')
        ->assertCountTableRecords(2);
});

// ──────────────────────────────────────────────
// Múltiplos detail components no mesmo request
// ──────────────────────────────────────────────

it('is independent per order — different orders show different items', function () {
    $orderA = makeOrder('Order A');
    $orderB = makeOrder('Order B');

    $itemA = makeItem($orderA, ['product_name' => 'Widget A']);
    $itemB = makeItem($orderB, ['product_name' => 'Widget B']);

    $componentA = livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $orderA->id,
        'detailComponent' => OrderItemsTable::class,
    ]);

    $componentB = livewire(ExpandedTableDetail::class, [
        'recordClass'     => Order::class,
        'recordKey'       => $orderB->id,
        'detailComponent' => OrderItemsTable::class,
    ]);

    $componentA
        ->assertCanSeeTableRecords([$itemA])
        ->assertCanNotSeeTableRecords([$itemB]);

    $componentB
        ->assertCanSeeTableRecords([$itemB])
        ->assertCanNotSeeTableRecords([$itemA]);
});
