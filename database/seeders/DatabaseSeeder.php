<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('providers')->insert([
            ['id' => 1, 'name' => 'Ahmad Tea'],
            ['id' => 2, 'name' => 'Lipton'],
        ]);

        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'provider_id' => 1, 'name' => 'Ahmad Tea'],
            ['id' => 2, 'parent_id' => 1, 'provider_id' => null, 'name' => 'Black Tea'],
            ['id' => 3, 'parent_id' => 1, 'provider_id' => null, 'name' => 'Green Tea'],
            ['id' => 4, 'parent_id' => 1, 'provider_id' => null, 'name' => 'White Tea'],
            ['id' => 5, 'parent_id' => null, 'provider_id' => 2, 'name' => 'Lipton'],
            ['id' => 6, 'parent_id' => 5, 'provider_id' => null, 'name' => 'Black Tea'],
        ]);

        DB::table('products')->insert([
            ['category_id' => 2, 'name' => 'Ahmad Tea Earl Grey, 500g', 'price' => 60000],
            ['category_id' => 3, 'name' => 'Ahmad Tea Green Tea, 100g', 'price' => 25000],
            ['category_id' => 4, 'name' => 'Ahmad Tea White Tea, 50g', 'price' => 40000],
            ['category_id' => 6, 'name' => 'Lipton Yellow Label, 100g', 'price' => 20000],
        ]);

        DB::table('storages')->insert([['name' => 'Main storage'], ['name' => 'Second storage']]);

        DB::table('clients')->insert([['name' => 'City Supermarket'], ['name' => 'Corner Shop']]);
    }
}
