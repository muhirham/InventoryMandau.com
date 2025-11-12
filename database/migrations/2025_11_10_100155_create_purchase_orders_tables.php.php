<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -------- purchase_orders (header) ----------
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->string('po_code', 50)->unique();
            $t->unsignedBigInteger('supplier_id')->nullable()->index();
            $t->unsignedBigInteger('ordered_by')->nullable()->index(); // user admin yang bikin
            $t->enum('status', ['draft','approved','ordered','partially_received','completed','cancelled'])
              ->default('draft')->index();
            $t->decimal('subtotal', 16, 2)->default(0);
            $t->decimal('discount_total', 16, 2)->default(0);
            $t->decimal('grand_total', 16, 2)->default(0);
            $t->text('notes')->nullable();
            $t->timestamp('ordered_at')->nullable();
            $t->timestamps();

            // FK opsional (biar flexible kalau nama tabel/PK beda)
            // $t->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            // $t->foreign('ordered_by')->references('id')->on('users')->nullOnDelete();
        });

        // -------- purchase_order_items (detail) ----------
        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_order_id')->index();
            $t->unsignedBigInteger('product_id')->index();
            $t->unsignedBigInteger('warehouse_id')->index(); // tujuan barang dikirim

            $t->integer('qty_ordered');
            $t->integer('qty_received')->default(0); // akumulasi GR good+damaged
            $t->decimal('unit_price', 16, 2)->default(0);
            $t->enum('discount_type', ['percent','amount'])->nullable();
            $t->decimal('discount_value', 16, 2)->nullable();
            $t->decimal('line_total', 16, 2)->default(0);

            $t->text('notes')->nullable();
            $t->timestamps();

            // $t->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
            // $t->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            // $t->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
        });

        // -------- alter restock_receipts: hubungkan ke PO (opsional tapi enak) ----------
        if (Schema::hasTable('restock_receipts')) {
            Schema::table('restock_receipts', function (Blueprint $t) {
                if (!Schema::hasColumn('restock_receipts', 'purchase_order_id')) {
                    $t->unsignedBigInteger('purchase_order_id')->nullable()->after('id')->index();
                }
                // pastikan code nullable biar gak error HY000 1364
                if (Schema::hasColumn('restock_receipts', 'code')) {
                    $t->string('code', 50)->nullable()->change();
                } else {
                    $t->string('code', 50)->nullable()->after('product_id');
                }
                // $t->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('restock_receipts')) {
            Schema::table('restock_receipts', function (Blueprint $t) {
                if (Schema::hasColumn('restock_receipts', 'purchase_order_id')) {
                    $t->dropColumn('purchase_order_id');
                }
            });
        }
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
