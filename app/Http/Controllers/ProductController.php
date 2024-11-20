<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $products = Product::with('stocks.warehouse')->get();

        return response()->json($products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stocks' => $product->stocks->map(function ($stock) {
                    return [
                        'warehouse_id' => $stock->warehouse_id,
                        'warehouse_name' => $stock->warehouse->name,
                        'quantity' => $stock->stock,
                    ];
                }),
            ];
        }));
    }

    /**
     * Store a newly created product in storage.
     *
     * @param ProductRequest $request
     * @return JsonResponse
     */
    public function store(ProductRequest $request): JsonResponse
    {
        // Создаём новый товар
        $product = Product::create($request->validated());

        return response()->json($product, 201);
    }

    /**
     * Display the specified product.
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function show(Product $product): JsonResponse
    {
        // Возвращаем данные конкретного товара
        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     *
     * @param ProductRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        // Обновляем данные товара
        $product->update($request->validated());

        return response()->json($product);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        // Удаляем товар
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
