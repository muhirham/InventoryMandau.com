<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class stockController extends Controller
{
    // VIEW: kirim list warehouses & products untuk isi dropdown
    public function index()
    {
        $warehouses = DB::table('warehouses')
            ->select('id', DB::raw('COALESCE(warehouse_name, CONCAT("Warehouse #", id)) AS label'))
            ->orderBy('label')
            ->get();

        $products = DB::table('products')
            ->select('id', DB::raw('COALESCE(product_name, CONCAT("Product #", id)) AS label'))
            ->orderBy('label')
            ->get();

        return view('admin.operations.stock', compact('warehouses', 'products'));
    }

    // JSON: server-side filter + pagination
    public function json(HttpRequest $r)
    {
        $page = max(1, (int)$r->query('page', 1));
        $per  = max(1, min(100, (int)$r->query('per_page', 10)));

        $warehouseId = (int)$r->query('warehouse_id', 0);
        $productId   = (int)$r->query('product_id', 0);
        $status      = $r->query('status', '');
        $search      = trim((string)$r->query('search', ''));

        // Ambil restock approved terakhir per product & warehouse
        $mx = DB::table('restock_requests as r')
            ->select(
                'r.product_id',
                'r.warehouse_id',
                DB::raw("
                    MAX(
                        CONCAT(
                            DATE_FORMAT(r.request_date, '%Y-%m-%d'), '|',
                            IFNULL(DATE_FORMAT(r.updated_at, '%Y-%m-%d %H:%i:%s'), '0000-00-00 00:00:00'), '|',
                            LPAD(r.id, 10, '0')
                        )
                    ) as k
                ")
            )
            ->where('r.status', 'approved')
            ->groupBy('r.product_id', 'r.warehouse_id');

        $last = DB::table('restock_requests as r')
            ->joinSub($mx, 'mx', function ($j) {
                $j->on('r.product_id', '=', 'mx.product_id')
                  ->on('r.warehouse_id', '=', 'mx.warehouse_id')
                  ->whereRaw("
                    CONCAT(
                        DATE_FORMAT(r.request_date, '%Y-%m-%d'), '|',
                        IFNULL(DATE_FORMAT(r.updated_at, '%Y-%m-%d %H:%i:%s'), '0000-00-00 00:00:00'), '|',
                        LPAD(r.id, 10, '0')
                    ) = mx.k
                  ");
            })
            ->select('r.product_id','r.warehouse_id', DB::raw('r.quantity_requested as last_in'));

        // Query utama product_stock
        $q = DB::table('product_stock as ps')
            ->leftJoin('products as p','p.id','=','ps.product_id')
            ->leftJoin('warehouses as w','w.id','=','ps.warehouse_id')
            ->leftJoinSub($last,'la',function($j){
                $j->on('la.product_id','=','ps.product_id')
                  ->on('la.warehouse_id','=','ps.warehouse_id');
            })
            ->select(
                'ps.id','ps.product_id','ps.warehouse_id',
                DB::raw("COALESCE(p.product_name, CONCAT('Product #', p.id)) as product_name"),
                DB::raw("COALESCE(w.warehouse_name, CONCAT('Warehouse #', w.id)) as warehouse_name"),
                DB::raw('(ps.final_stock - COALESCE(la.last_in,0)) as initial_ui'),
                DB::raw('COALESCE(la.last_in,0) as last_in'),
                'ps.stock_out',
                'ps.final_stock',
                'ps.last_update'
            );

        if ($warehouseId > 0) $q->where('ps.warehouse_id', $warehouseId);
        if ($productId   > 0) $q->where('ps.product_id', $productId);

        if ($status !== '') {
            $q->when($status === 'empty', fn($qq) => $qq->where('ps.final_stock', '<=', 0))
              ->when($status === 'low',   fn($qq) => $qq->where('ps.final_stock', '>', 0)->where('ps.final_stock', '<=', 10))
              ->when($status === 'ok',    fn($qq) => $qq->where('ps.final_stock', '>', 10));
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('p.product_name','like',"%{$search}%")
                  ->orWhere('w.warehouse_name','like',"%{$search}%");
            });
        }

        $total = (clone $q)->count();
        $rows  = $q->orderBy('ps.last_update','desc')->orderBy('ps.id','desc')
                   ->forPage($page,$per)->get();

        return response()->json([
            'ok'=>true,
            'data'=>$rows,
            'pagination'=>[
                'page'=>$page,'per_page'=>$per,'total'=>$total,'last_page'=>(int)ceil($total/$per)
            ]
        ]);
    }

    // Sinkronisasi stock warehouse <-> product
    public function syncStocks()
    {
        // Update product_stock.final_stock sesuai product.stock
        $products = DB::table('products')->select('id','stock')->get();
        foreach($products as $p){
            // Cari semua warehouse untuk product ini
            $warehouses = DB::table('product_stock')->where('product_id',$p->id)->get();
            if($warehouses->count() == 0){
                // jika belum ada record di product_stock, buat satu default warehouse
                DB::table('product_stock')->insert([
                    'product_id' => $p->id,
                    'warehouse_id' => 1,
                    'final_stock' => $p->stock,
                    'stock_out' => 0,
                    'last_update' => Carbon::now(),
                ]);
            } else {
                // Update final_stock agar total sum sama dengan product.stock
                $totalOut = $warehouses->sum('stock_out');
                $perWarehouse = intval(($p->stock + $totalOut)/$warehouses->count());
                foreach($warehouses as $w){
                    DB::table('product_stock')->where('id',$w->id)
                        ->update([
                            'final_stock' => $perWarehouse,
                            'last_update' => Carbon::now(),
                        ]);
                }
            }
        }

        // Optional: update product.stock total dari semua warehouse
        $stocks = DB::table('product_stock')
            ->select('product_id', DB::raw('SUM(final_stock) as total_stock'))
            ->groupBy('product_id')
            ->get();

        foreach($stocks as $s){
            DB::table('products')->where('id',$s->product_id)->update(['stock'=>$s->total_stock]);
        }

        return response()->json(['ok'=>true,'message'=>'Stocks synchronized successfully']);
    }

    public function destroy(int $id)
    {
        DB::table('product_stock')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer','exists:product_stock,id'],
        ]);

        DB::table('product_stock')->whereIn('id', $request->ids)->delete();
        return response()->json(['ok' => true, 'deleted' => count($request->ids)]);
    }
}
