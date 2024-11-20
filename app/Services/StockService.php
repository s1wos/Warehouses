<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Movements;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing stock and recording movements.
 */
class StockService
{
    /**
     * Adjusts stock for a specific product in a warehouse and records the movement.
     *
     * @param int $productId The ID of the product.
     * @param int $warehouseId The ID of the warehouse.
     * @param int $quantity The quantity to adjust.
     * @param string $type The type of adjustment: "increase" or "decrease".
     * @return void
     * @throws \Exception If there is insufficient stock during a "decrease".
     * @throws \InvalidArgumentException If the adjustment type is invalid.
     */
    public function adjustStock(int $productId, int $warehouseId, int $quantity, string $type): void
    {
        // Проверяем корректность типа операции
        if (!in_array($type, ['increase', 'decrease'])) {
            throw new \InvalidArgumentException('Invalid stock adjustment type.');
        }

        DB::transaction(function () use ($productId, $warehouseId, $quantity, $type) {
            // Получаем или создаём запись об остатках
            $stock = Stock::firstOrCreate([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ]);

            // Обрабатываем изменение остатков
            if ($type === 'increase') {
                $stock->increment('stock', $quantity);
            } elseif ($type === 'decrease') {
                if ($stock->stock < $quantity) {
                    throw new \Exception('Not enough stock available.');
                }
                $stock->decrement('stock', $quantity);
            }

            // Записываем движение остатков
            $this->recordMovement($productId, $warehouseId, $quantity, $type);
        });
    }

    /**
     * Records a stock movement.
     *
     * @param int $productId The ID of the product.
     * @param int $warehouseId The ID of the warehouse.
     * @param int $quantity The quantity involved in the movement.
     * @param string $type The type of movement: "increase" or "decrease".
     * @return void
     */
    private function recordMovement(int $productId, int $warehouseId, int $quantity, string $type): void
    {
        // Записываем информацию в таблицу movements
        Movements::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'type' => $type,
        ]);
    }

    /**
     * Gets the current stock for a specific product in a warehouse.
     *
     * @param int $productId The ID of the product.
     * @param int $warehouseId The ID of the warehouse.
     * @return int The current stock quantity.
     */
    public function getStock(int $productId, int $warehouseId): int
    {
        // Получаем текущий остаток товара на складе
        $stock = Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? $stock->stock : 0;
    }
}
