<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Получаем список складов
        $warehouses = Warehouse::all();

        return response()->json($warehouses);
    }

    /**
     * Display the specified warehouse.
     *
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        // Возвращаем данные конкретного склада
        return response()->json($warehouse);
    }
}
