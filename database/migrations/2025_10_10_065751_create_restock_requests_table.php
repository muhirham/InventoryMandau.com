<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('restock_requests', function (Blueprint $t) {
      $t->bigIncrements('id'); // id_request -> id
      $t->foreignId('product_id')->constrained('products');
      $t->foreignId('supplier_id')->constrained('suppliers');
      $t->foreignId('warehouse_id')->constrained('warehouses');
      $t->foreignId('user_id')->constrained('users'); // requester

      $t->date('request_date');
      $t->decimal('quantity_requested', 18, 6)->default(0);
      $t->decimal('total_cost', 18, 2)->default(0);
      $t->string('description')->nullable();
      $t->enum('status', ['pending','approved','rejected'])->default('pending');

      $t->timestamps();

      $t->index(['status','request_date']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('restock_requests');
  }
};