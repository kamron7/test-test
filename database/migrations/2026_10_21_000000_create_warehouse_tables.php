<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories');
            $table->foreignId('provider_id')->nullable()->constrained();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained();
            $table->foreignId('storage_id')->constrained();
            $table->timestamps();
        });

        Schema::create('batch_products', function (Blueprint $table) {
            $table->foreignId('batch_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('price', 12, 2);
            $table->primary(['batch_id', 'product_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id');
            $table->foreignId('product_id');
            $table->foreignId('order_id')->nullable()->constrained();
            $table->integer('qty');
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamp('created_at');
            $table->foreign(['batch_id', 'product_id'])->references(['batch_id', 'product_id'])->on('batch_products');
            $table->index(['product_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('batch_products');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('storages');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('providers');
    }
};
