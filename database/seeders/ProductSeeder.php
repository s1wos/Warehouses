<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->count(20)->create();

        Product::create(['name' => 'Черный чай', 'price' => 150.50]);
        Product::create(['name' => 'Зеленый чай', 'price' => 22.8]);
    }
}
