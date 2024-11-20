<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock',
    ];

    /**
     * Get the product associated with the stock.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse associated with the stock.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
