<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('restock_receipt_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('receipt_id')->index();
            $t->string('path');            // storage path bukti foto
            $t->string('caption')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('restock_receipt_photos');
    }
};
