<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\PreOController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestockApprovalController extends Controller
{
    public function index()
    {
        // Label dinamis biar gak pecah walau nama kolom beda-beda
        $supLabel = Schema::hasColumn('suppliers','name')
            ? 'name'
            : (Schema::hasColumn('suppliers','supplier_name') ? 'supplier_name' : 'id');

        $whLabel = Schema::hasColumn('warehouses','warehouse_name')
            ? 'warehouse_name'
            : (Schema::hasColumn('warehouses','name') ? 'name' : 'id');

        $prdLabel = Schema::hasColumn('products','name')
            ? 'name'
            : (Schema::hasColumn('products','product_name') ? 'product_name' : 'id');

        $suppliers  = DB::table('suppliers')->select('id', DB::raw("$supLabel AS label"))->orderBy('label')->get();
        $warehouses = DB::table('warehouses')->select('id', DB::raw("$whLabel AS label"))->orderBy('label')->get();
        $products   = DB::table('products')->select('id', DB::raw("$prdLabel AS label"))->orderBy('label')->get();

        return view('admin.operations.stockRequest', compact('suppliers','warehouses','products'));
    }

    public function json(Request $r)
    {
        $page   = max(1, (int)$r->get('page',1));
        $per    = min(100, max(1,(int)$r->get('per_page',10)));
        $status = $r->get('status');
        $sid    = $r->get('supplier_id');
        $wid    = $r->get('warehouse_id');
        $pid    = $r->get('product_id');
        $q      = trim((string)$r->get('search',''));
        $d1     = $r->get('date_from');
        $d2     = $r->get('date_to');

        // Cek apakah rr.warehouse_id ada
        $hasWh = Schema::hasColumn('request_restocks','warehouse_id');

        // Nama kolom dinamis
        $pName = Schema::hasColumn('products','name')
            ? 'p.name'
            : (Schema::hasColumn('products','product_name') ? 'p.product_name' : "CONCAT('Product #',p.id)");

        $sName = Schema::hasColumn('suppliers','name')
            ? 's.name'
            : (Schema::hasColumn('suppliers','supplier_name') ? 's.supplier_name' : "CONCAT('Supplier #',s.id)");

        // Kalau gak ada rr.warehouse_id, jangan pakai alias w.*
        $wName = $hasWh
            ? (Schema::hasColumn('warehouses','warehouse_name')
                ? 'w.warehouse_name'
                : (Schema::hasColumn('warehouses','name') ? 'w.name' : "CONCAT('Warehouse #',w.id)"))
            : "NULL";

        $qtyReq = Schema::hasColumn('request_restocks','quantity_requested')
            ? 'rr.quantity_requested'
            : (Schema::hasColumn('request_restocks','qty_requested')
                ? 'rr.qty_requested'
                : (Schema::hasColumn('request_restocks','qty') ? 'rr.qty' : '0'));

        $totalCost = Schema::hasColumn('request_restocks','total_cost')
            ? 'rr.total_cost'
            : "(COALESCE($qtyReq,0) * COALESCE(rr.cost_per_item,0))";

        $noteCol = Schema::hasColumn('request_restocks','note')
            ? 'rr.note'
            : (Schema::hasColumn('request_restocks','description') ? 'rr.description' : "''");

        $base = DB::table('request_restocks as rr')
            ->leftJoin('products as p','p.id','=','rr.product_id')
            ->leftJoin('suppliers as s','s.id','=','rr.supplier_id');

        // JOIN warehouses hanya jika rr.warehouse_id memang ada
        if ($hasWh) {
            $base->leftJoin('warehouses as w','w.id','=','rr.warehouse_id');
        }

        $base->selectRaw("
            rr.id,
            rr.created_at as request_date,
            $pName   as product_name,
            $sName   as supplier_name,
            $wName   as warehouse_name,
            COALESCE($qtyReq,0) as quantity_requested,
            $totalCost as total_cost,
            COALESCE(rr.status,'pending') as status,
            $noteCol  as description
        ");

        if ($status !== null && $status !== '') $base->where('rr.status',$status);
        if ($sid) $base->where('rr.supplier_id',$sid);
        if ($pid) $base->where('rr.product_id',$pid);
        if ($wid && $hasWh) $base->where('rr.warehouse_id',$wid);
        if ($d1)  $base->whereDate('rr.created_at','>=',$d1);
        if ($d2)  $base->whereDate('rr.created_at','<=',$d2);

        if ($q !== '') {
            $base->where(function($w) use($q, $hasWh){
                // notes
                if (Schema::hasColumn('request_restocks','note'))        $w->orWhere('rr.note','like',"%$q%");
                if (Schema::hasColumn('request_restocks','description')) $w->orWhere('rr.description','like',"%$q%");
                // product
                if (Schema::hasColumn('products','name'))         $w->orWhere('p.name','like',"%$q%");
                if (Schema::hasColumn('products','product_name')) $w->orWhere('p.product_name','like',"%$q%");
                // supplier
                if (Schema::hasColumn('suppliers','name'))          $w->orWhere('s.name','like',"%$q%");
                if (Schema::hasColumn('suppliers','supplier_name')) $w->orWhere('s.supplier_name','like',"%$q%");
                // warehouse (hanya kalau join)
                if ($hasWh) {
                    if (Schema::hasColumn('warehouses','warehouse_name')) $w->orWhere('w.warehouse_name','like',"%$q%");
                    if (Schema::hasColumn('warehouses','name'))           $w->orWhere('w.name','like',"%$q%");
                }
            });
        }

        $total = (clone $base)->count();
        $rows  = $base->orderByDesc('rr.id')->forPage($page,$per)->get();

        return response()->json([
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $per,
                'last_page' => (int) ceil($total / $per),
                'total' => $total,
            ]
        ]);
    }

    public function approve($id)
    {
        $now = now();
        $updated = DB::table('request_restocks')->where('id',$id)->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => $now,
            'updated_at'  => $now,
        ]);

        return response()->json(['ok' => (bool)$updated, 'message' => $updated ? 'Approved' : 'Not found'], $updated?200:404);
    }

    public function reject(Request $r, $id)
    {
        $reason  = (string)($r->input('reason') ?? '');
        $updated = DB::table('request_restocks')->where('id',$id)->update([
            'status'     => 'rejected',
            'note'       => trim(($reason ? "[REJECT] $reason" : '')),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => (bool)$updated, 'message' => $updated ? 'Rejected' : 'Not found'], $updated?200:404);
    }

    public function bulkPO(Request $r)
    {
        $ids = $r->validate(['ids'=>'required|array|min:1','ids.*'=>'integer'])['ids'];
        $cnt = DB::table('request_restocks')->whereIn('id',$ids)->where('status','approved')->count();
        if ($cnt === 0) return back()->with('error','Tidak ada request berstatus approved.');

        $proxy = app(PreOController::class);
        $req   = Request::create('', 'POST', ['stock_request_ids'=>$ids]);
        return $proxy->createFromRequests($req);
    }
}
