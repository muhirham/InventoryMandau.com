<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StockWhController extends Controller
{
    /* ================== INDEX (HALAMAN) ================== */

    public function index(Request $r)
    {
        $me = auth()->user();

        $canSwitchWarehouse = $me->hasRole(['admin', 'superadmin']);
        $isWarehouseUser    = $me->hasRole('warehouse');

        $warehouses          = collect();
        $selectedWarehouseId = null;

        if ($canSwitchWarehouse) {
            $warehouses = Warehouse::orderBy('warehouse_name')
                ->get(['id', 'warehouse_name']);

            $selectedWarehouseId = $r->integer('warehouse_id') ?: null;
        } elseif ($isWarehouseUser) {
            $selectedWarehouseId = $me->warehouse_id;
        }

        // cuma product (supplier nanti diambil dari kolom di tabel products)
        $products = Product::orderBy('name')
            ->get(['id', 'name', 'product_code']);

        return view('wh.restocks', compact(
            'me',
            'warehouses',
            'selectedWarehouseId',
            'canSwitchWarehouse',
            'isWarehouseUser',
            'products'
        ));
    }

    /* ================== DATATABLE ================== */

    public function datatable(Request $r)
    {
        try {
            $me = auth()->user();

            $canSwitchWarehouse = $me->hasRole(['admin', 'superadmin']);
            $isWarehouseUser    = $me->hasRole('warehouse');

            // tentukan warehouse yang dipakai filter
            if ($isWarehouseUser) {
                $warehouseId = $me->warehouse_id;
            } elseif ($canSwitchWarehouse) {
                $warehouseId = $r->integer('warehouse_id') ?: null;
            } else {
                $warehouseId = null;
            }

            // parameter DataTables
            $draw        = (int) $r->input('draw', 1);
            $start       = (int) $r->input('start', 0);
            $length      = (int) $r->input('length', 10);
            $orderColIdx = (int) $r->input('order.0.column', 1);
            $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
            $search      = trim((string) $r->input('search.value', ''));

            $hasCode  = Schema::hasColumn('request_restocks', 'code');
            $hasWh    = Schema::hasColumn('request_restocks', 'warehouse_id');
            $hasReqBy = Schema::hasColumn('request_restocks', 'requested_by');

            // ekspresi qty request
            if (Schema::hasColumn('request_restocks', 'quantity_requested')) {
                $qtyReqExpr = 'COALESCE(rr.quantity_requested,0)';
            } elseif (Schema::hasColumn('request_restocks', 'qty_requested')) {
                $qtyReqExpr = 'COALESCE(rr.qty_requested,0)';
            } elseif (Schema::hasColumn('request_restocks', 'qty')) {
                $qtyReqExpr = 'COALESCE(rr.qty,0)';
            } else {
                $qtyReqExpr = '0';
            }

            // ekspresi qty received
            $rcvJoin = false;
            if (Schema::hasColumn('request_restocks', 'quantity_received')) {
                $qtyRcvExpr = 'COALESCE(rr.quantity_received,0)';
            } elseif (Schema::hasColumn('request_restocks', 'qty_received')) {
                $qtyRcvExpr = 'COALESCE(rr.qty_received,0)';
            } elseif (Schema::hasTable('restock_receipts') && Schema::hasColumn('restock_receipts', 'qty_good')) {
                $rcvJoin    = true;
                $qtyRcvExpr = 'COALESCE(rcv.qty_rcv,0)';
            } else {
                $qtyRcvExpr = '0';
            }

            // kolom note / description
            if (Schema::hasColumn('request_restocks', 'note')) {
                $noteCol = 'rr.note';
            } elseif (Schema::hasColumn('request_restocks', 'description')) {
                $noteCol = 'rr.description';
            } else {
                $noteCol = "''"; // kosong
            }

            // mapping kolom order dari DataTables
            $orderMap = [
                1 => 'code',
                2 => 'product_name',
                3 => 'supplier_name',
                4 => 'qty_req',
                5 => 'qty_rcv',
                6 => 'status',
                7 => 'created_at',
                8 => 'note',
            ];
            $orderKey = $orderMap[$orderColIdx] ?? 'code';

            // ekspresi code
            $codeExpr = $hasCode
                ? 'rr.code'
                : "CONCAT('RR-', rr.id)";

            $base = DB::table('request_restocks as rr')
                ->leftJoin('products as p', 'p.id', '=', 'rr.product_id')
                ->leftJoin('suppliers as s', 's.id', '=', 'rr.supplier_id');

            if ($rcvJoin) {
                $sub = DB::table('restock_receipts')
                    ->selectRaw('request_id, COALESCE(SUM(qty_good),0) as qty_rcv')
                    ->groupBy('request_id');

                $base->leftJoinSub($sub, 'rcv', 'rcv.request_id', '=', 'rr.id');
            }

            // FILTER GUDANG
            if ($warehouseId) {
                if ($hasWh) {
                    $base->where('rr.warehouse_id', $warehouseId);
                } elseif ($hasReqBy && $isWarehouseUser) {
                    $base->where('rr.requested_by', $me->id);
                }
            } elseif ($isWarehouseUser && $hasReqBy && ! $hasWh) {
                $base->where('rr.requested_by', $me->id);
            }

            // total sebelum search
            $recordsTotal = (clone $base)->count();

            // SELECT kolom
            $base->select([
                'rr.id',
                DB::raw($codeExpr . ' as code'),
                'p.product_code',
                'p.name as product_name',
                's.name as supplier_name',
                DB::raw($qtyReqExpr . ' as qty_req'),
                DB::raw($qtyRcvExpr . ' as qty_rcv'),
                DB::raw("COALESCE(rr.status,'pending') as status"),
                'rr.created_at',
                DB::raw($noteCol . ' as note'),
            ]);

            // SEARCH global
            if ($search !== '') {
                $like = '%' . $search . '%';

                $base->where(function ($q) use ($like, $hasCode, $noteCol) {
                    if ($hasCode) {
                        $q->where('rr.code', 'like', $like);
                    } else {
                        $q->where('rr.id', 'like', $like);
                    }

                    $q->orWhere('p.name', 'like', $like)
                      ->orWhere('p.product_code', 'like', $like)
                      ->orWhere('s.name', 'like', $like);

                    if ($noteCol !== "''") {
                        $q->orWhereRaw($noteCol . ' LIKE ?', [$like]);
                    }
                });
            }

            $recordsFiltered = (clone $base)->count();

            // ORDER BY
            switch ($orderKey) {
                case 'qty_req':
                    $base->orderByRaw($qtyReqExpr . ' ' . $orderDir);
                    break;
                case 'qty_rcv':
                    $base->orderByRaw($qtyRcvExpr . ' ' . $orderDir);
                    break;
                case 'code':
                    $base->orderBy('code', $orderDir);
                    break;
                case 'product_name':
                    $base->orderBy('product_name', $orderDir);
                    break;
                case 'supplier_name':
                    $base->orderBy('supplier_name', $orderDir);
                    break;
                case 'status':
                    $base->orderBy('status', $orderDir);
                    break;
                case 'created_at':
                    $base->orderBy('created_at', $orderDir);
                    break;
                case 'note':
                    $base->orderBy('note', $orderDir);
                    break;
                default:
                    $base->orderBy('code', 'desc');
                    break;
            }

            // PAGING
            $rows = $base->skip($start)->take($length)->get()
                ->map(function ($r, $idx) use ($start) {
                    $status = strtolower($r->status);

                    if ($status === 'approved') {
                        $badge = '<span class="badge bg-label-info">REVIEW</span>';
                    } elseif ($status === 'ordered') {
                        $badge = '<span class="badge bg-label-secondary">ORDERED</span>';
                    } elseif ($status === 'received') {
                        $badge = '<span class="badge bg-label-primary">RECEIVED</span>';
                    } elseif ($status === 'cancelled') {
                        $badge = '<span class="badge bg-label-dark">CANCELLED</span>';
                    } else {
                        $badge = '<span class="badge bg-label-warning">PENDING</span>';
                    }

                    $canReceive = ! in_array($status, ['received', 'cancelled'], true);

                    $actions = '<div class="d-flex gap-1">';
                    if ($canReceive) {
                        $actions .= '<button class="btn btn-sm btn-outline-primary js-receive"
                            data-id="' . $r->id . '"
                            data-code="' . e($r->code) . '"
                            data-product="' . e($r->product_name) . '"
                            data-supplier="' . e($r->supplier_name) . '"
                            data-qty_req="' . (int) $r->qty_req . '"
                            data-qty_rcv="' . (int) $r->qty_rcv . '"
                            data-action="' . e(route('restocks.receive', $r->id)) . '"
                            data-total_cost="{{ $row->total_cost }}">
                            <i class="bx bx-download"></i></button>';
                    }
                    $actions .= '</div>';

                    return [
                        'rownum'     => $start + $idx + 1,
                        'code'       => e($r->code),
                        'product'    => e($r->product_name) .
                                        '<div class="small text-muted">' . e($r->product_code) . '</div>',
                        'supplier'   => e($r->supplier_name),
                        'qty_req'    => number_format((int) $r->qty_req, 0, ',', '.'),
                        'qty_rcv'    => number_format((int) $r->qty_rcv, 0, ',', '.'),
                        'status'     => $badge,
                        'created_at' => Carbon::parse($r->created_at)->format('Y-m-d'),
                        'note'       => e($r->note ?? ''),
                        'actions'    => $actions,
                    ];
                });

            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            \Log::error('restocks.datatable: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'draw'            => (int) $r->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Server error',
            ]);
        }
    }

    /* ================== STORE REQUEST ================== */

    public function store(Request $r)
{
    $me = auth()->user();

    $canSwitchWarehouse = $me->hasRole(['admin', 'superadmin']);
    $isWarehouseUser    = $me->hasRole('warehouse');

    $payload = $r->all();

    // ==== VALIDASI: multi item ====
    $rules = [
        'items'                         => ['required', 'array', 'min:1'],
        'items.*.product_id'            => ['required', 'exists:products,id'],
        'items.*.quantity_requested'    => ['required', 'integer', 'min:1'],
        'items.*.note'                  => ['nullable', 'string', 'max:500'],
    ];

    if ($canSwitchWarehouse) {
        $rules['warehouse_id'] = ['required', 'exists:warehouses,id'];
    }

    $data = validator($payload, $rules)->validate();

    // Tentukan warehouse yang dipakai
    if ($isWarehouseUser && ! $canSwitchWarehouse) {
        $warehouseId = $me->warehouse_id;
    } else {
        $warehouseId = $data['warehouse_id'] ?? $me->warehouse_id;
    }

    // Ambil base sequence sekali, biar kode RR nggak bentrok
    $baseSeq = 0;
    if (Schema::hasColumn('request_restocks', 'code')) {
        $baseSeq = (int) (DB::table('request_restocks')->max('id') ?? 0);
    }

    DB::transaction(function () use ($data, $warehouseId, $me, $baseSeq) {
        $seq = $baseSeq;

        foreach ($data['items'] as $row) {
            // Ambil supplier dari product
            $product = Product::select('id','supplier_id')->findOrFail($row['product_id']);
            $qty     = (int) $row['quantity_requested'];

            $insert = [
                'supplier_id'        => $product->supplier_id,
                'product_id'         => $product->id,
                'warehouse_id'       => $warehouseId,
                'requested_by'       => $me->id,
                'quantity_requested' => $qty,
                'quantity_received'  => 0,
                'cost_per_item'      => 0,
                'total_cost'         => 0,
                'status'             => 'pending',
                'note'               => $row['note'] ?? null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];

            // generate code per baris (kalau kolomnya ada)
            if (Schema::hasColumn('request_restocks', 'code')) {
                $seq++;
                $insert['code'] = 'RR-' . now()->format('ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
            }

            DB::table('request_restocks')->insert($insert);
        }
        });

        return back()->with('success', 'Request restock berhasil dibuat.');
    }


    /* ================== RECEIVE BARANG ================== */

    private function nextReceiptCode(): string
    {
        $prefix = 'GR-' . now()->format('ymd') . '-';

        $last = DB::table('restock_receipts')
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('code');

        $n = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    public function receive(Request $r, $id)
{
    $user = auth()->user();

    // TANPA cost_per_item input, tapi masih bisa pakai kolom di DB kalau mau diisi belakangan
    $data = $r->validate([
        'qty_good'    => 'required|integer|min:0',
        'qty_damaged' => 'nullable|integer|min:0',
        'notes'       => 'nullable|string',
        'photos.*'    => 'nullable|image|max:4096',
    ]);

    $qtyGood    = (int) ($data['qty_good'] ?? 0);
    $qtyDamaged = (int) ($data['qty_damaged'] ?? 0);

    if ($qtyGood === 0 && $qtyDamaged === 0) {
        return back()->with('error', 'Qty diterima dan rusak tidak boleh dua-duanya nol.');
    }

    $req = DB::table('request_restocks')->where('id', $id)->first();
    if (! $req) {
        return back()->with('error', 'Restock request tidak ditemukan.');
    }

    $reqQty  = (int) ($req->quantity_requested ?? $req->quantity ?? $req->qty ?? 0);
    $sumGood = (int) DB::table('restock_receipts')->where('request_id', $id)->sum('qty_good');
    $sumBad  = (int) DB::table('restock_receipts')->where('request_id', $id)->sum('qty_damaged');
    $already = $sumGood + $sumBad;

    if ($reqQty > 0 && ($already + $qtyGood + $qtyDamaged) > $reqQty) {
        return back()->with('error', 'Total penerimaan melebihi qty request.');
    }

    $warehouseId = $user->warehouse_id ?? ($req->warehouse_id ?? null);
    $productId   = (int) ($req->product_id ?? 0);

    // cari PO yang terkait (kalau ada)
    $poId = null;
    if (
        Schema::hasTable('purchase_order_items') &&
        Schema::hasColumn('purchase_order_items', 'request_id')
    ) {
        $poId = DB::table('purchase_order_items')
            ->where('request_id', $id)
            ->value('purchase_order_id');
    }

    DB::beginTransaction();

    try {
        $payload = [
            'purchase_order_id' => $poId,
            'request_id'        => $id,
            'product_id'        => $productId,
            'warehouse_id'      => $warehouseId,
            'supplier_id'       => $req->supplier_id ?? null,
            'qty_requested'     => $reqQty,
            'qty_good'          => $qtyGood,
            'qty_damaged'       => $qtyDamaged,
            // kolom cost_per_item di DB dibiarkan NULL
            'notes'             => $data['notes'] ?? null,
            'received_by'       => $user->id ?? null,
            'received_at'       => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        // auto kode GR
        if (Schema::hasColumn('restock_receipts', 'code')) {
            $payload['code'] = $this->nextReceiptCode();
        }

        // insert dan ambil ID untuk relasi foto
        $receiptId = DB::table('restock_receipts')->insertGetId($payload);

        // SIMPAN FOTO (jika ada)
        if ($r->hasFile('photos')) {
            foreach ($r->file('photos') as $file) {
                if (! $file->isValid()) {
                    continue;
                }

                $path = $file->store('restock_receipts', 'public');

                DB::table('restock_receipt_photos')->insert([
                    'receipt_id' => $receiptId,
                    'path'       => $path,
                    'caption'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // update status di request_restocks
        $nowGood = (int) DB::table('restock_receipts')->where('request_id', $id)->sum('qty_good');
        $nowBad  = (int) DB::table('restock_receipts')->where('request_id', $id)->sum('qty_damaged');
        $nowAll  = $nowGood + $nowBad;

        $status = ($reqQty > 0 && $nowAll >= $reqQty)
            ? 'received'
            : ($req->status ?? 'ordered');

        DB::table('request_restocks')->where('id', $id)->update([
            'quantity_received' => $nowGood,
            'status'            => $status,
            'received_at'       => $status === 'received' ? now() : $req->received_at,
            'updated_at'        => now(),
        ]);

        // UPDATE STOCK LEVEL
        if (Schema::hasTable('stock_levels') && $warehouseId && $productId) {
            $q = DB::table('stock_levels')
                ->where('owner_type', 'warehouse')
                ->where('owner_id', $warehouseId)
                ->where('product_id', $productId);

            $existing = $q->first();
            if ($existing) {
                $qtyNow = (int) ($existing->quantity ?? 0);
                $q->update([
                    'quantity'   => $qtyNow + $qtyGood,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('stock_levels')->insert([
                    'owner_type' => 'warehouse',
                    'owner_id'   => $warehouseId,
                    'product_id' => $productId,
                    'quantity'   => $qtyGood,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        /* ================== NEW: UPDATE STATUS PO ================== */
        if (
            $poId &&
            Schema::hasTable('purchase_orders') &&
            Schema::hasTable('purchase_order_items')
        ) {
            $hasWhCol = Schema::hasColumn('restock_receipts', 'warehouse_id');

            $rcvQuery = DB::table('restock_receipts')
                ->where('purchase_order_id', $poId)
                ->selectRaw('product_id' . ($hasWhCol ? ', warehouse_id' : '') . ', SUM(qty_good + qty_damaged) as qty_rcv')
                ->groupBy('product_id');

            if ($hasWhCol) {
                $rcvQuery->groupBy('warehouse_id');
            }

            $rcvRows = $rcvQuery->get();

            $rcvIndex = [];
            foreach ($rcvRows as $row) {
                $key = $row->product_id . '-' . ($hasWhCol ? ($row->warehouse_id ?? 0) : 0);
                $rcvIndex[$key] = (int) $row->qty_rcv;
            }

            $items = DB::table('purchase_order_items')
                ->where('purchase_order_id', $poId)
                ->get(['id', 'product_id', 'warehouse_id', 'qty_ordered', 'qty_received']);

            $allFull     = true;
            $anyReceived = false;

            foreach ($items as $it) {
                $key     = $it->product_id . '-' . ($hasWhCol ? ($it->warehouse_id ?? 0) : 0);
                $qtyRcv  = $rcvIndex[$key] ?? 0;
                $ordered = (int) $it->qty_ordered;

                DB::table('purchase_order_items')
                    ->where('id', $it->id)
                    ->update([
                        'qty_received' => $qtyRcv,
                        'updated_at'   => now(),
                    ]);

                if ($qtyRcv > 0)        $anyReceived = true;
                if ($qtyRcv < $ordered) $allFull    = false;
            }

            $updatePo = [];
            if ($allFull && $anyReceived) {
                $updatePo['status'] = 'completed';
                if (Schema::hasColumn('purchase_orders', 'received_at')) {
                    $updatePo['received_at'] = now();
                }
            } elseif ($anyReceived) {
                $updatePo['status'] = 'partially_received';
            }

            if (!empty($updatePo)) {
                $updatePo['updated_at'] = now();
                DB::table('purchase_orders')
                    ->where('id', $poId)
                    ->update($updatePo);
            }
        }
        /* ============== END UPDATE STATUS PO ============== */

        DB::commit();
        return back()->with('success', 'Penerimaan berhasil disimpan.');
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->with('error', 'Gagal menyimpan penerimaan: ' . $e->getMessage());
    }
}

}
