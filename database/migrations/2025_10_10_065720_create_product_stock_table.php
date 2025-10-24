<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
    public function up(): void {
        Schema::create('product_stock', function (Blueprint $t) {
        $t->bigIncrements('id'); // id_stock -> id
        $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
        $t->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

        $t->decimal('initial_stock', 18, 6)->default(0);
        $t->decimal('stock_in', 18, 6)->default(0);
        $t->decimal('stock_out', 18, 6)->default(0);
        $t->decimal('final_stock', 18, 6)->default(0);

        $t->timestamp('last_update')->nullable();
        $t->timestamps();

        $t->unique(['product_id','warehouse_id']);
        $t->index(['warehouse_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('product_stock');
    }
    };
