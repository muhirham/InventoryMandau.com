<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('products', function (Blueprint $t) {
      $t->bigIncrements('id'); // id_product -> id
      $t->string('product_code', 80)->unique();
      $t->string('product_name', 180);

      $t->foreignId('category_id')->constrained('categories'); // id_category
      $t->foreignId('supplier_id')->constrained('suppliers');  // id_supplier
      $t->foreignId('warehouse_id')->constrained('warehouses'); // id_warehouse (default/main warehouse)

      $t->decimal('purchase_price', 18, 2)->default(0);
      $t->decimal('selling_price', 18, 2)->default(0);

      // Kalau mau simpan stok total di produk, keep. (Saldo detail ada di product_stock)
      $t->decimal('stock', 18, 6)->default(0);

      $t->string('package_type', 100)->nullable();
      $t->string('product_group', 100)->nullable();
      $t->string('registration_number', 100)->nullable();

      $t->timestamps(); // created_at, updated_at
    });
  }
  public function down(): void {
    Schema::dropIfExists('products');
  }
};