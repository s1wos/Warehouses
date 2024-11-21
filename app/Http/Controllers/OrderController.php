<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * Display a listing of orders.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Получаем заказы с пагинацией
        $orders = Order::with(['items.product', 'warehouse'])->paginate(10);

        // Возвращаем ресурсы
        return response()->json(OrderResource::collection($orders));
    }

    /**
     * Store a newly created order.
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        // Создаём заказ
        $order = $this->orderService->createOrder($request->validated());

        // Списываем товары со склада и записываем движения
        $this->orderService->processOrderMovements($order, $request->input('items'));

        // Возвращаем созданный заказ с использованием OrderResource
        return response()->json(new OrderResource($order), 201);
    }

    /**
     * Display a specific order.
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function show(Order $order): JsonResponse
    {
        // Загружаем связанные данные
        $order->load(['items.product', 'warehouse']);

        // Возвращаем заказ через OrderResource
        return response()->json(new OrderResource($order));
    }

    /**
     * Update an order.
     *
     * @param UpdateOrderRequest $request
     * @param Order $order
     * @return JsonResponse
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        // Обновляем заказ
        $this->orderService->updateOrder($order, $request->validated());

        // Возвращаем обновлённый заказ через OrderResource
        return response()->json(new OrderResource($order));
    }
}
