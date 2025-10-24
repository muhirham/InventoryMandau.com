<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('suppliers', function (Blueprint $t) {
      $t->bigIncrements('id'); // id_supplier -> id
      $t->string('company_name', 150);
      $t->string('address')->nullable();
      $t->string('contact_person', 100)->nullable();
      $t->string('phone_number', 50)->nullable();
      $t->string('bank_name', 100)->nullable();
      $t->string('bank_account', 100)->nullable();
      $t->timestamps();

      $t->index('company_name');
    });
  }
  public function down(): void {
    Schema::dropIfExists('suppliers');
  }
};