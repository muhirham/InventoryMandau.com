<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestockReceipt;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GrDeleteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class GoodReceivedController extends Controller
{
    public function index(Request $request)
{
    $q           = trim($request->get('q', ''));
    $supplierId  = $request->get('supplier_id');
    $warehouseId = $request->get('warehouse_id');
    $dateFrom    = $request->get('date_from');
    $dateTo      = $request->get('date_to');

    $me    = auth()->user();
    $roles = $me?->roles ?? collect();

    $isWarehouseUser = $roles->contains('slug', 'warehouse');
    $isSuperadmin    = $roles->contains('slug', 'superadmin'); // kalau kepake nanti

    // ===== helper filter untuk GR yang masih aktif (qty > 0) =====
    $onlyActiveGr = function ($q) {
        $q->where(function ($qq) {
            $qq->where('qty_good', '>', 0)
               ->orWhere('qty_damaged', '>', 0);
        });
    };

    // ====== QUERY PO YANG SUDAH PUNYA GR (masih aktif) ======
    $poQuery = PurchaseOrder::with([
        'supplier',
        'items.product',
        'grDeleteRequests.requester',
        'grDeleteRequests.approver',
        // restockReceipts yg di-load juga cuma yang masih punya qty
        'restockReceipts' => function ($rr) use (
            $me,
            $isWarehouseUser,
            $warehouseId,
            $dateFrom,
            $dateTo,
            $onlyActiveGr
        ) {
            // USER WAREHOUSE: hanya GR di gudang & oleh user itu sendiri
            if ($isWarehouseUser && $me) {
                if ($me->warehouse_id) {
                    $rr->where('warehouse_id', $me->warehouse_id);
                }
                $rr->where('received_by', $me->id);
            } else {
                // ADMIN / SUPERADMIN bisa filter warehouse bebas
                if ($warehouseId) {
                    $rr->where('warehouse_id', $warehouseId);
                }
            }

            if ($dateFrom && $dateTo) {
                $rr->whereBetween('received_at', [
                    $dateFrom,
                    $dateTo . ' 23:59:59',
                ]);
            }

            // hanya GR yang qty_good + qty_damaged > 0
            $onlyActiveGr($rr);

            // load relasi lain
            $rr->with(['photos', 'receiver', 'warehouse']);
        },
    ]);

    // PO wajib punya restockReceipts (yang masih aktif)
    $poQuery->whereHas('restockReceipts', function ($rr) use (
        $me,
        $isWarehouseUser,
        $warehouseId,
        $dateFrom,
        $dateTo,
        $onlyActiveGr
    ) {
        if ($isWarehouseUser && $me) {
            if ($me->warehouse_id) {
                $rr->where('warehouse_id', $me->warehouse_id);
            }
            $rr->where('received_by', $me->id);
        } else {
            if ($warehouseId) {
                $rr->where('warehouse_id', $warehouseId);
            }
        }

        if ($dateFrom && $dateTo) {
            $rr->whereBetween('received_at', [
                $dateFrom,
                $dateTo . ' 23:59:59',
            ]);
        }

        // hanya GR aktif
        $onlyActiveGr($rr);
    });

    // ===== Filter search q (PO code / GR code) =====
    if ($q !== '') {
        $poQuery->where(function ($qq) use ($q, $onlyActiveGr) {
            $qq->where('po_code', 'like', "%{$q}%")
               ->orWhereHas('restockReceipts', function ($rr) use ($q, $onlyActiveGr) {
                   $rr->where('code', 'like', "%{$q}%");

                   // pastikan search juga cuma ke GR yang masih punya qty
                   $onlyActiveGr($rr);
               });
        });
    }

    // ===== Filter supplier =====
    if ($supplierId) {
        $poQuery->where('supplier_id', $supplierId);
    }

    $pos = $poQuery
        ->orderByDesc('id')
        ->paginate(15);

    $pos->appends($request->all());

    // group delete request per PO (log tetap ada, nggak dihapus)
    $deleteRequests = GrDeleteRequest::whereIn('purchase_order_id', $pos->pluck('id')->all())
        ->latest()
        ->get()
        ->groupBy('purchase_order_id');

    // === AJAX RESPONSE (untuk search/filter/pagination tanpa reload) ===
    if ($request->ajax()) {
        $html = view('admin.masterdata.partials.goodReceivedTable', compact(
            'pos',
            'deleteRequests'
        ))->render();

        return response()->json([
            'html' => $html,
        ]);
    }

    // ====== LIST WAREHOUSE UNTUK FILTER (full page pertama saja) ======
    $whQuery = Warehouse::query();
    if (Schema::hasColumn('warehouses', 'warehouse_name')) {
        $whQuery->orderBy('warehouse_name');
        $warehouses = $whQuery->get(['id', DB::raw('warehouse_name as name')]);
    } elseif (Schema::hasColumn('warehouses', 'name')) {
        $whQuery->orderBy('name');
        $warehouses = $whQuery->get(['id', 'name']);
    } else {
        $warehouses = $whQuery->get(['id'])->map(function ($w) {
            return (object) [
                'id'   => $w->id,
                'name' => 'Warehouse #' . $w->id,
            ];
        });
    }

    $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

    return view('admin.masterdata.goodReceived', compact(
        'pos',
        'q',
        'warehouses',
        'suppliers',
        'supplierId',
        'warehouseId',
        'dateFrom',
        'dateTo',
        'deleteRequests'
    ));
}


    /** Generator kode GR unik: GR-YYMMDD-0001 */
    protected function nextReceiptCode(): string
    {
        $prefix = 'GR-' . now()->format('ymd') . '-';

        $last = DB::table('restock_receipts')
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('code');

        $n = 1;
        if ($last) {
            $lastSeq = (int) substr($last, -4);
            $n       = $lastSeq + 1;
        }

        return $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    /** Simpan Goods Received dari PO manual (1 form → banyak item) */
    public function storeFromPo(Request $r, PurchaseOrder $po)
    {
        // PO yang berasal dari Restock Request tetap TIDAK boleh lewat sini
        $fromRequest = $po->items()->whereNotNull('request_id')->exists();
        if ($fromRequest) {
            return back()->with('error', 'PO ini berasal dari Restock Request. Penerimaan dilakukan dari menu Warehouse (Restock).');
        }

        $data = $r->validate([
            'receives'               => ['required', 'array', 'min:1'],
            'receives.*.qty_good'    => ['nullable', 'integer', 'min:0'],
            'receives.*.qty_damaged' => ['nullable', 'integer', 'min:0'],
            'receives.*.notes'       => ['nullable', 'string', 'max:500'],

            'photos_good'            => ['nullable'],
            'photos_good.*'          => ['nullable', 'image', 'max:4096'],
            'photos_damaged'         => ['nullable'],
            'photos_damaged.*'       => ['nullable', 'image', 'max:4096'],
        ]);

        $user           = auth()->user();
        $rows           = $data['receives'];
        $now            = now();
        $anyRow         = false;
        $firstReceiptId = null;

        $hasCodeColumn = Schema::hasColumn('restock_receipts', 'code');

        DB::beginTransaction();

        try {
            foreach ($rows as $itemId => $row) {
                $item = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('id', $itemId)
                    ->first();

                if (! $item) {
                    continue;
                }

                $good = (int) ($row['qty_good'] ?? 0);
                $bad  = (int) ($row['qty_damaged'] ?? 0);

                if ($good === 0 && $bad === 0) {
                    continue;
                }

                $ordered   = (int) ($item->qty_ordered ?? 0);
                $received  = (int) ($item->qty_received ?? 0);
                $remaining = max(0, $ordered - $received);

                if ($remaining > 0 && ($good + $bad) > $remaining) {
                    DB::rollBack();
                    return back()->with('error', 'Qty Good + Damaged melebihi Qty Remaining untuk salah satu item.');
                }

                // PO manual superadmin → dianggap CENTRAL STOCK
                $warehouseId = null; // JANGAN ambil dari $item->warehouse_id

                $payload = [
                    'purchase_order_id' => $po->id,
                    'request_id'        => null,
                    'product_id'        => $item->product_id,
                    'warehouse_id'      => $warehouseId,      // NULL = Central Stock
                    'supplier_id'       => $po->supplier_id,
                    'qty_requested'     => $ordered,
                    'qty_good'          => $good,
                    'qty_damaged'       => $bad,
                    'notes'             => $row['notes'] ?? null,
                    'received_by'       => $user?->id,
                    'received_at'       => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                if ($hasCodeColumn) {
                    $payload['code'] = $this->nextReceiptCode();
                }

                $receiptId = DB::table('restock_receipts')->insertGetId($payload);
                $anyRow    = true;
                if (! $firstReceiptId) {
                    $firstReceiptId = $receiptId;
                }

                $newReceived = $received + $good + $bad;
                DB::table('purchase_order_items')
                    ->where('id', $item->id)
                    ->update([
                        'qty_received' => $newReceived,
                        'updated_at'   => $now,
                    ]);

                // ================== STOK CENTRAL ==================
                if (
                    Schema::hasTable('stock_levels') &&
                    Schema::hasColumn('stock_levels', 'product_id') &&
                    Schema::hasColumn('stock_levels', 'owner_type') &&
                    Schema::hasColumn('stock_levels', 'owner_id') &&
                    Schema::hasColumn('stock_levels', 'quantity')
                ) {
                    $level = DB::table('stock_levels')
                        ->where('owner_type', 'pusat')
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($level) {
                        DB::table('stock_levels')
                            ->where('id', $level->id)
                            ->update([
                                'quantity'   => (int) $level->quantity + $good,
                                'updated_at' => $now,
                            ]);
                    } else {
                        DB::table('stock_levels')->insert([
                            'owner_type' => 'pusat',
                            'owner_id'   => 0,
                            'product_id' => $item->product_id,
                            'quantity'   => $good,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            if (! $anyRow) {
                DB::rollBack();
                return back()->with('error', 'Tidak ada qty yang diinput untuk Goods Received.');
            }

            if ($firstReceiptId) {
                // ini sudah benar, tinggal pastikan storeReceiptPhotos isi kolom type dengan benar
                $this->storeReceiptPhotos($r->file('photos_good') ?? [],    $firstReceiptId, 'good');
                $this->storeReceiptPhotos($r->file('photos_damaged') ?? [], $firstReceiptId, 'damaged');
            }

            // hitung status PO
            $items = $po->items()->get(['qty_ordered', 'qty_received']);

            $allFull     = true;
            $anyReceived = false;

            foreach ($items as $it) {
                $ordered  = (int) $it->qty_ordered;
                $received = (int) $it->qty_received;

                if ($received > 0) {
                    $anyReceived = true;
                }
                if ($received < $ordered) {
                    $allFull = false;
                }
            }

            $updatePo = [];
            if ($allFull && $anyReceived) {
                $updatePo['status'] = 'completed';
                if (Schema::hasColumn('purchase_orders', 'received_at')) {
                    $updatePo['received_at'] = $now;
                }
            } elseif ($anyReceived) {
                $updatePo['status'] = 'partially_received';
            }

            if (! empty($updatePo)) {
                $updatePo['updated_at'] = $now;
                DB::table('purchase_orders')
                    ->where('id', $po->id)
                    ->update($updatePo);
            }

            DB::commit();
            return back()->with('success', 'Goods Received berhasil disimpan dan stok CENTRAL telah diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Gagal menyimpan Goods Received: ' . $e->getMessage());
        }
    }

    /**
     * Simpan foto GR ke tabel restock_receipt_photos
     * $kind = 'good' atau 'damaged'
     */
    protected function storeReceiptPhotos($files, int $receiptId, string $kind): void
    {
        if (empty($files)) {
            return;
        }

        if (! is_array($files)) {
            $files = [$files];
        }

        $now        = now();
        $hasType    = Schema::hasColumn('restock_receipt_photos', 'type');    // <<=== kolom yang benar
        $hasKind    = Schema::hasColumn('restock_receipt_photos', 'kind');    // kalau di masa depan ada kolom ini
        $hasCaption = Schema::hasColumn('restock_receipt_photos', 'caption');

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('restock_receipts', 'public');

            $row = [
                'receipt_id' => $receiptId,
                'path'       => $path,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // PRIORITAS: isi kolom `type` kalau ada
            if ($hasType) {
                $row['type'] = $kind;              // <-- ini yang sebelumnya nggak kepasang
            } elseif ($hasKind) {
                $row['kind'] = $kind;
            } elseif ($hasCaption) {
                $row['caption'] = $kind;
            }

            DB::table('restock_receipt_photos')->insert($row);
        }
    }



    // ================== DELETE GR REQUEST & APPROVAL ==================

    public function requestDelete(Request $r, PurchaseOrder $po)
    {
        $data = $r->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $hasPending = GrDeleteRequest::where('purchase_order_id', $po->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return back()->with('error', 'Masih ada permohonan delete GR yang pending untuk PO ini.');
        }

        $firstReceipt = RestockReceipt::where('purchase_order_id', $po->id)
            ->orderBy('id')
            ->first();

        if (!$firstReceipt) {
            return back()->with('error', 'PO ini tidak memiliki data Goods Received.');
        }

        GrDeleteRequest::create([
            'restock_receipt_id' => $firstReceipt->id,
            'purchase_order_id'  => $po->id,
            'requested_by'       => auth()->id(),
            'status'             => 'pending',
            'reason'             => $data['reason'],
        ]);

        return back()->with('success', 'Permohonan delete GR berhasil diajukan. Menunggu approval.');
    }

    public function handleDeleteApproval(Request $r, GrDeleteRequest $requestModel)
    {
        $data = $r->validate([
            'action'        => ['required', 'in:approve,reject'],
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($requestModel->status !== 'pending') {
            return back()->with('error', 'Permohonan ini sudah diproses sebelumnya.');
        }

        if ($data['action'] === 'reject') {
            $requestModel->update([
                'status'        => 'rejected',
                'approved_by'   => auth()->id(),
                'approval_note' => $data['approval_note'] ?? null,
            ]);

            return back()->with('success', 'Permohonan delete GR ditolak.');
        }

        try {
            $this->rollbackGoodsReceivedForPo($requestModel->purchase_order_id);

            $requestModel->update([
                'status'        => 'approved',
                'approved_by'   => auth()->id(),
                'approval_note' => $data['approval_note'] ?? null,
            ]);

            return back()->with('success', 'Goods Received berhasil dihapus dan PO dibuka kembali.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menghapus Goods Received: ' . $e->getMessage());
        }
    }

    protected function rollbackGoodsReceivedForPo(int $poId): void
    {
        DB::transaction(function () use ($poId) {
            $now = now();

            $receipts = RestockReceipt::where('purchase_order_id', $poId)->get();

            if ($receipts->isEmpty()) {
                return;
            }

            if (
                Schema::hasTable('stock_levels') &&
                Schema::hasColumn('stock_levels', 'product_id') &&
                Schema::hasColumn('stock_levels', 'owner_type') &&
                Schema::hasColumn('stock_levels', 'owner_id') &&
                Schema::hasColumn('stock_levels', 'quantity')
            ) {
                $grouped = $receipts->groupBy('product_id');

                foreach ($grouped as $productId => $rows) {
                    $qtyGood = (int)$rows->sum('qty_good');
                    if ($qtyGood <= 0) {
                        continue;
                    }

                    $level = DB::table('stock_levels')
                        ->where('owner_type', 'pusat')
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if ($level) {
                        $newQty = max(0, (int)$level->quantity - $qtyGood);

                        DB::table('stock_levels')
                            ->where('id', $level->id)
                            ->update([
                                'quantity'   => $newQty,
                                'updated_at' => $now,
                            ]);
                    }
                }
            }

            $poItems = PurchaseOrderItem::where('purchase_order_id', $poId)->get();

            foreach ($poItems as $item) {
                $sumReceipts = $receipts
                    ->where('product_id', $item->product_id)
                    ->sum(function ($r) {
                        return (int)$r->qty_good + (int)$r->qty_damaged;
                    });

                if ($sumReceipts <= 0) {
                    continue;
                }

                $newReceived = max(0, (int)$item->qty_received - $sumReceipts);

                DB::table('purchase_order_items')
                    ->where('id', $item->id)
                    ->update([
                        'qty_received' => $newReceived,
                        'updated_at'   => $now,
                    ]);
            }

            if (Schema::hasTable('restock_receipt_photos')) {
                $ids = $receipts->pluck('id')->all();

                DB::table('restock_receipt_photos')
                    ->whereIn('receipt_id', $ids)
                    ->delete();
            }

            RestockReceipt::whereIn('id', $receipts->pluck('id')->all())->delete();

            $updatePo = [
                'status'     => 'approved',
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('purchase_orders', 'received_at')) {
                $updatePo['received_at'] = null;
            }

            DB::table('purchase_orders')
                ->where('id', $poId)
                ->update($updatePo);
        });
    }
}
