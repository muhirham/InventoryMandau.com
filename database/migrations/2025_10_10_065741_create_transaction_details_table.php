<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('transaction_details', function (Blueprint $t) {
      $t->bigIncrements('id'); // id_detail -> id
      $t->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
      $t->foreignId('product_id')->constrained('products');

      $t->decimal('quantity', 18, 6)->default(0);
      $t->decimal('price', 18, 2)->default(0);
      $t->decimal('subtotal', 18, 2)->default(0);

      $t->timestamps();

      $t->index(['product_id']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('transaction_details');
  }
};