<?php

namespace App\Services;

use App\Models\Movements;
use App\Models\Order;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Service for managing orders and related stock operations.
 */
class OrderService
{
    /**
     * Create a new order along with its items and update stock.
     *
     * @param array $data Order data including customer, warehouse_id, and items.
     * @return Order The created order.
     * @throws \Exception If there is an error during the transaction.
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Создаём заказ
            $order = Order::create([
                'customer' => $data['customer'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'active',
            ]);

            // Добавляем позиции заказа
            $order->items()->createMany($data['items']);

            // Обновляем остатки для каждого товара
            foreach ($data['items'] as $item) {
                $this->deductStock($item['product_id'], $data['warehouse_id'], $item['count']);
            }

            return $order;
        });
    }

    /**
     * Update an existing order and adjust stock based on new items.
     *
     * @param Order $order The order to update.
     * @param array $data Updated order data including customer and items.
     * @return void
     * @throws \Exception If there is an error during the transaction.
     */
    public function updateOrder(Order $order, array $data): void
    {
        DB::transaction(function () use ($order, $data) {
            // Обновляем данные клиента, если они есть
            if (isset($data['customer'])) {
                $order->update(['customer' => $data['customer']]);
            }

            // Обрабатываем изменения в позициях заказа
            if (isset($data['items'])) {
                // Восстанавливаем остатки для старых позиций
                $this->restoreStock($order->items);

                // Удаляем старые позиции
                $order->items()->delete();

                // Добавляем новые позиции
                $order->items()->createMany($data['items']);

                // Списываем остатки для новых позиций
                foreach ($data['items'] as $item) {
                    $this->deductStock($item['product_id'], $order->warehouse_id, $item['count']);
                }
            }
        });
    }

    /**
     * Change the status of an order and adjust stock if necessary.
     *
     * @param Order $order The order to change the status for.
     * @param string $status The new status ("active", "completed", "canceled").
     * @return void
     * @throws \Exception If the status change is invalid.
     */
    public function changeStatus(Order $order, string $status): void
    {
        if ($status === 'active') {
            // Восстанавливаем остатки для отменённого заказа
            $this->restoreStock($order->items);
        } elseif (in_array($status, ['completed', 'canceled'])) {
            // Если заказ восстанавливается из статуса "canceled"
            if ($order->status === 'canceled' && $status === 'active') {
                $this->deductStockForOrder($order);
            }
        } else {
            throw new \InvalidArgumentException('Invalid status provided.');
        }

        // Обновляем статус заказа
        $order->update(['status' => $status]);
    }

    /**
     * Deduct stock for a specific product in a warehouse.
     *
     * @param int $productId The ID of the product.
     * @param int $warehouseId The ID of the warehouse.
     * @param int $quantity The quantity to deduct.
     * @return void
     * @throws \Exception If there is insufficient stock.
     */
    public function deductStock(int $productId, int $warehouseId, int $quantity): void
    {
        // Получаем текущий остаток
        $currentStock = Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity');

        if ($currentStock < $quantity) {
            throw new \Exception('Недостаточно товара для списания. ID товара: ' . $productId);
        }

        // Записываем движение
        Movements::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => -$quantity,
            'type' => 'decrease',
            'previous_stock' => $currentStock,
        ]);

        // Обновляем остаток
        Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->decrement('quantity', $quantity);
    }

    /**
     * Restore stock for a collection of order items.
     *
     * @param Collection $items The items to restore stock for.
     * @return void
     */
    private function restoreStock(Collection $items): void
    {
        // Восстанавливаем остатки по всем позициям
        foreach ($items as $item) {
            Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $item->order->warehouse_id)
                ->increment('stock', $item->count);
        }
    }

    /**
     * Deduct stock for all items in an order.
     *
     * @param Order $order The order whose items will be processed.
     * @return void
     * @throws \Exception If there is insufficient stock for any item.
     */
    private function deductStockForOrder(Order $order): void
    {
        // Списываем остатки по каждой позиции заказа
        foreach ($order->items as $item) {
            $this->deductStock($item->product_id, $order->warehouse_id, $item->count);
        }
    }

    /**
     * Processing of movements and write-off of goods when creating an order.
     */
    public function processOrderMovements(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $warehouseId = $order->warehouse_id;
            $count = $item['count'];

            // Получаем текущий остаток на складе
            $currentStock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->value('quantity');

            if ($currentStock < $count) {
                throw new \Exception("Недостаточно товара для списания. ID товара: $productId");
            }

            // Записываем движение
            Movement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => -$count,
                'type' => 'decrease',
                'previous_stock' => $currentStock,
            ]);

            // Списываем остаток
            Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->decrement('quantity', $count);
        }
    }
}
