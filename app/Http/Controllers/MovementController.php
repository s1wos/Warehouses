<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementRequest;
use App\Models\Movements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    /**
     * Display a listing of the movements with optional filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Получаем запросы с фильтрацией
        $query = Movements::query();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        // Возвращаем результаты с пагинацией
        $movements = $query->paginate(10);

        return response()->json($movements);
    }

    /**
     * Store created movement in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreMovementRequest $request): JsonResponse
    {
        $movement = Movements::create($request->validated()); // Создание истории движения

        return response()->json($movement, 201);
    }
}
