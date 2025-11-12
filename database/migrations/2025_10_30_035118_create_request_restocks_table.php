<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('request_restocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->integer('quantity_requested');
            $t->integer('quantity_received')->nullable();
            $t->integer('cost_per_item');
            $t->integer('total_cost');
            $t->enum('status', ['pending','approved','ordered','received','cancelled'])->default('pending');
            $t->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('request_restocks');
    }
};