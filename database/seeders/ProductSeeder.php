<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => 1,
                'name' => 'Hardware Materials',
                'slug' => 'hard-materials',
            ],
            [
                'id' => 2,
                'name' => 'Electrical Materials',
                'slug' => 'electrical-materials',
            ],
            [
                'id' => 3,
                'name' => 'Plumbing Materials',
                'slug' => 'plumbing-materials',
            ],
            [
                'id' => 4,
                'name' => 'Finishing Materials',
                'slug' => 'finishing-materials',
            ],

        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate(['id' => $category['id']], $category);
        }
        $products=[
            [
                'id' => 1,
                'name' => 'Cement',
                'category_id' => 1,
                'unit' => 'bag',
            ],
            [
                'id' => 2,
                'name' => 'Sand',
                'category_id' => 1,
                'unit' => 'm3',
            ],
            [
                'id' => 3,
                'name' => 'Gravel',
                'category_id' => 1,
                'unit' => 'm3',
            ],
            [
                'id' => 4,
                'name' => 'Steel Rods',
                'category_id' => 1,
                'unit' => 'kg',
            ],
            [
                'id' => 5,
                'name' => 'Bricks',
                'category_id' => 1,
                'unit' => 'piece',
            ],
            [
                'id' => 6,
                'name' => 'PVC Pipes',
                'category_id' => 3,
                'unit' => 'm',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['id' => $product['id']], $product);
        }

    }
}
