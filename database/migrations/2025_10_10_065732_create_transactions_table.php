<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('transactions', function (Blueprint $t) {
      $t->bigIncrements('id'); // id_transaction -> id
      $t->foreignId('user_id')->constrained('users'); // id_user -> users.id
      $t->foreignId('warehouse_id')->constrained('warehouses');
      $t->dateTime('transaction_date');

      $t->decimal('total', 18, 2)->default(0);
      $t->decimal('paid_amount', 18, 2)->default(0);
      $t->decimal('change_amount', 18, 2)->default(0);

      // ENUM di MySQL; kalau pakai SQLite/Posgre bisa diganti string + check
      $t->enum('transaction_type', ['sale','purchase']);
      $t->enum('status', ['pending','completed','cancelled'])->default('pending');

      $t->timestamps();

      $t->index(['transaction_date','transaction_type','status']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('transactions');
  }
};