<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class RestockRequestController extends Controller
{
    /** PAGE: /restocks (admin view) */
    public function index()
    {
        // supplier pakai company_name
        $suppliers = DB::table('suppliers')
            ->select('id', DB::raw('COALESCE(company_name, CONCAT("Supplier #", id)) AS label'))
            ->orderBy('label')->get();

        $warehouses = DB::table('warehouses')
            ->select('id', DB::raw('COALESCE(warehouse_name, CONCAT("Warehouse #", id)) AS label'))
            ->orderBy('label')->get();

        $products = DB::table('products as p')
            ->select('p.id', DB::raw('COALESCE(p.product_name, CONCAT("Product #", p.id)) AS label'))
            ->orderBy('label')->get();

        // view yang kamu pakai
        return view('admin.operations.stockRequest', compact('suppliers','warehouses','products'));
    }

    /** JSON LIST: /restocks/json */
    public function json(Request $r)
    {
        $page = max(1, (int)$r->query('page', 1));
        $per  = max(1, min(100, (int)$r->query('per_page', 10)));

        $status  = $r->query('status', ''); // '' = ALL
        $sid     = (int)$r->query('supplier_id', 0);
        $wid     = (int)$r->query('warehouse_id', 0);
        $pid     = (int)$r->query('product_id', 0);
        $search  = trim((string)$r->query('search', ''));
        $from    = $r->query('date_from');
        $to      = $r->query('date_to');

        $q = RestockRequest::query()->from('restock_requests as rr')
            ->leftJoin('products as p',   'p.id', '=', 'rr.product_id')
            ->leftJoin('suppliers as s',  's.id', '=', 'rr.supplier_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'rr.warehouse_id')
            ->leftJoin('users as u',      'u.id', '=', 'rr.user_id')
            ->select(
                'rr.id','rr.product_id','rr.supplier_id','rr.warehouse_id','rr.user_id',
                'rr.request_date','rr.quantity_requested','rr.total_cost','rr.description','rr.status',
                DB::raw('COALESCE(p.product_name,  CONCAT("Product #", p.id))   AS product_name'),
                DB::raw('COALESCE(s.company_name, CONCAT("Supplier #", s.id))  AS supplier_name'),
                DB::raw('COALESCE(w.warehouse_name, CONCAT("Warehouse #", w.id)) AS warehouse_name'),
                DB::raw('COALESCE(u.name, "—") AS requested_by')
            );

        if ($status !== '') $q->where('rr.status', $status);
        if ($sid > 0)  $q->where('rr.supplier_id', $sid);
        if ($wid > 0)  $q->where('rr.warehouse_id', $wid);
        if ($pid > 0)  $q->where('rr.product_id', $pid);
        if ($from)     $q->whereDate('rr.request_date', '>=', $from);
        if ($to)       $q->whereDate('rr.request_date', '<=', $to);

        if ($search !== '') {
            $q->where(function($w) use ($search){
                $w->where('p.product_name','like',"%{$search}%")
                  ->orWhere('s.company_name','like',"%{$search}%")
                  ->orWhere('w.warehouse_name','like',"%{$search}%")
                  ->orWhere('rr.description','like',"%{$search}%");
            });
        }

        $total = (clone $q)->count();

        $rows = $q->orderBy('rr.request_date','desc')
                  ->orderBy('rr.id','desc')
                  ->forPage($page,$per)
                  ->get();

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'pagination' => [
                'page'      => $page,
                'per_page'  => $per,
                'total'     => $total,
                'last_page' => (int)ceil($total / $per),
            ]
        ]);
    }

    /** USER CREATE (optional, biarin ada dulu) */
    public function store(Request $r)
    {
        $v = $r->validate([
            'product_id'   => 'required|integer|exists:products,id',
            'supplier_id'  => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'request_date' => 'required|date',
            'quantity'     => 'required|numeric|min:1',
            'unit_price'   => 'nullable|numeric|min:0',
            'description'  => 'nullable|string|max:255',
        ]);

        $qty   = (float)$v['quantity'];
        $price = (float)($v['unit_price'] ?? 0);
        $total = $qty * $price;

        RestockRequest::create([
            'product_id'         => (int)$v['product_id'],
            'supplier_id'        => (int)$v['supplier_id'],
            'warehouse_id'       => (int)$v['warehouse_id'],
            'user_id'            => auth()->id(),
            'request_date'       => $v['request_date'],
            'quantity_requested' => $qty,
            'total_cost'         => $total,
            'description'        => $v['description'] ?? null,
            'status'             => 'pending',
            'created_at'         => Carbon::now(),
            'updated_at'         => Carbon::now(),
        ]);

        return response()->json(['ok'=>true]);
    }

    /** ADMIN: APPROVE → tambah stok di product_stock */
        public function approve(int $id)
    {
        DB::transaction(function () use ($id) {
            $rr = \App\Models\RestockRequest::lockForUpdate()->findOrFail($id);

            if ($rr->status === 'approved') return;
            if ($rr->status === 'rejected') abort(422, 'Request sudah ditolak.');

            // Apakah sudah ada baris stok per-gudang untuk produk ini?
            $ps = DB::table('product_stock')->lockForUpdate()
                ->where('product_id', $rr->product_id)
                ->where('warehouse_id', $rr->warehouse_id)
                ->first();

            if ($ps) {
                // Gudang ini sudah punya baris => tinggal tambah qty ke stok yang ada
                DB::table('product_stock')->where('id', $ps->id)->update([
                    'stock_in'    => DB::raw('stock_in + '.$rr->quantity_requested),
                    'final_stock' => DB::raw('final_stock + '.$rr->quantity_requested),
                    'last_update' => Carbon::now(),
                    'updated_at'  => Carbon::now(),
                ]);
            } else {
                // Gudang ini belum punya baris
                // Cek apakah produk sudah punya baris di product_stock di gudang lain
                $sumExisting = DB::table('product_stock')
                    ->where('product_id', $rr->product_id)
                    ->sum('final_stock');

                // Ambil "stok produk" dari tabel products jika kolomnya ada (default 'stock')
                $stockCol = collect(['stock','qty','quantity','current_stock'])
                    ->first(fn($c) => Schema::hasColumn('products', $c));

                $productTableStock = 0;
                if ($stockCol) {
                    $productTableStock = (int) DB::table('products')->where('id', $rr->product_id)->value($stockCol);
                }

                // Seed initial:
                // - Jika BELUM ada satupun baris di product_stock untuk produk ini => pakai stok yang ada di tabel products
                // - Jika SUDAH ada baris lain => 0 (biar tidak double count total)
                $initial = ($sumExisting == 0) ? $productTableStock : 0;

                DB::table('product_stock')->insert([
                    'product_id'    => $rr->product_id,
                    'warehouse_id'  => $rr->warehouse_id,
                    'initial_stock' => $initial,
                    'stock_in'      => $rr->quantity_requested,
                    'stock_out'     => 0,
                    'final_stock'   => $initial + $rr->quantity_requested, // langsung hitung final
                    'last_update'   => Carbon::now(),
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]);
            }

            // === Recalculate total stok produk dari semua gudang ===
            $totalStock = DB::table('product_stock')
                ->where('product_id', $rr->product_id)
                ->sum('final_stock');

            // Update kembali kolom stok di tabel products (kalau ada)
            $stockCol = collect(['stock','qty','quantity','current_stock'])
                ->first(fn($c) => Schema::hasColumn('products', $c));

            if ($stockCol) {
                DB::table('products')->where('id', $rr->product_id)->update([
                    $stockCol    => $totalStock,
                    'updated_at' => Carbon::now(),
                ]);
            }

            // Tandai approved
            $rr->status     = 'approved';
            $rr->updated_at = Carbon::now();
            $rr->save();
        });

        return response()->json(['ok'=>true,'message'=>'Approved & stock updated']);
    }

    /** ADMIN: REJECT */
    public function reject(int $id, Request $r)
    {
        $rr = RestockRequest::findOrFail($id);
        if ($rr->status === 'approved') {
            return response()->json(['ok'=>false,'message'=>'Sudah approved, tidak bisa ditolak'], 422);
        }
        $reason = trim((string)$r->input('reason', ''));
        $rr->status      = 'rejected';
        $rr->description = trim(($rr->description ?? '').($reason ? ' [REJECT] '.$reason : ''));
        $rr->updated_at  = Carbon::now();
        $rr->save();

        return response()->json(['ok'=>true]);
    }
}