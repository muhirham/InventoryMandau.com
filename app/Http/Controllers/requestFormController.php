<?php

namespace App\Http\Controllers;

use App\Models\RequestForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class requestFormController extends Controller
{
    /** PAGE: form submit + list user (dummy, belum pakai auth scope) */
    public function index()
    {
        // Dropdowns
        $suppliers = DB::table('suppliers')
            ->select('id', DB::raw('COALESCE(company_name, CONCAT("Supplier #", id)) AS label'))
            ->orderBy('label')->get();

        $warehouses = DB::table('warehouses')
            ->select('id', DB::raw('COALESCE(warehouse_name, CONCAT("Warehouse #", id)) AS label'))
            ->orderBy('label')->get();

        // cari kolom harga yang tersedia di products
        $priceCol = null;
        foreach (['purchase','purchase_price','buy_price','cost_price','harga_beli'] as $c) {
            if (Schema::hasColumn('products', $c)) { $priceCol = $c; break; }
        }

        $productsQ = DB::table('products as p')
            ->select('p.id', DB::raw('COALESCE(p.product_name, CONCAT("Product #", p.id)) AS label'));
        $productsQ->addSelect($priceCol ? DB::raw("p.`$priceCol` AS purchase_price") : DB::raw("0 AS purchase_price"));
        $products = $productsQ->orderBy('label')->get();

        return view('admin.barang.requestForm', compact('suppliers','warehouses','products'));
    }

    /** JSON list (dummy: belum filter berdasarkan user_id) */
    public function json(Request $r)
    {
        $page   = max(1, (int)$r->query('page', 1));
        $per    = max(1, min(100, (int)$r->query('per_page', 10)));
        $status = $r->query('status', '');
        $search = trim((string)$r->query('search', ''));

        $q = RequestForm::query()->from('restock_requests as rr')
            ->with(['product','supplier','warehouse'])
            ->leftJoin('products as p',   'p.id', '=', 'rr.product_id')
            ->leftJoin('suppliers as s',  's.id', '=', 'rr.supplier_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'rr.warehouse_id')
            ->select(
                'rr.id','rr.request_date','rr.quantity_requested','rr.total_cost','rr.status','rr.description',
                DB::raw('COALESCE(p.product_name,  CONCAT("Product #", p.id))   AS product_name'),
                DB::raw('COALESCE(s.company_name, CONCAT("Supplier #", s.id))  AS supplier_name'),
                DB::raw('COALESCE(w.warehouse_name, CONCAT("Warehouse #", w.id)) AS warehouse_name')
            )
            ->status($status)
            ->quickSearch($search);

        // TODO nanti saat sudah ada auth:
        // $q->where('rr.user_id', auth()->id());

        $total = (clone $q)->count();
        $rows  = $q->orderBy('rr.request_date','desc')->orderBy('rr.id','desc')
                   ->forPage($page,$per)->get();

        return response()->json([
            'ok'=>true,
            'data'=>$rows,
            'pagination'=>[
                'page'=>$page,'per_page'=>$per,'total'=>$total,'last_page'=>(int)ceil($total/$per)
            ]
        ]);
    }

    /** Submit pengajuan */
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
        $userId = auth()->id() ?? 2;

        RequestForm::create([
            'product_id'         => (int)$v['product_id'],
            'supplier_id'        => (int)$v['supplier_id'],
            'warehouse_id'       => (int)$v['warehouse_id'],
            'user_id'            => $userId, // belum ada auth → bisa null
            'request_date'       => $v['request_date'],
            'quantity_requested' => $qty,
            'total_cost'         => $total,
            'description'        => $v['description'] ?? null,
            'status'             => 'pending',
            'created_at'         => Carbon::now(),
            'updated_at'         => Carbon::now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Request submitted']);
    }

    // Approval/rejection handled in RestockRequestController (admin side)
    
}