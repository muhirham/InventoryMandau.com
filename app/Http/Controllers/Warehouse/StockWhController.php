<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;



class StockWhController extends Controller
{
    public function index()
    {
        $me = auth()->user();
        $suppliers = DB::table('suppliers')->select('id','name')->orderBy('name')->get();
        $products  = DB::table('products')->select('id','product_code','name')->orderBy('name')->get();

        return view('wh.restocks', compact('suppliers','products','me'));
    }

    public function datatable(Request $r)
{
    try {
        $me = auth()->user();

        $draw   = (int)$r->input('draw', 1);
        $start  = (int)$r->input('start', 0);
        $length = (int)$r->input('length', 10);
        $orderColIdx = (int)$r->input('order.0.column', 1);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $search      = trim((string)$r->input('search.value',''));

        $hasCode      = Schema::hasColumn('request_restocks','code');
        $hasWh        = Schema::hasColumn('request_restocks','warehouse_id');
        $hasReqBy     = Schema::hasColumn('request_restocks','requested_by');

        // pilih kolom qty request yang tersedia
        $qtyReqExpr = Schema::hasColumn('request_restocks','quantity_requested')
            ? 'COALESCE(rr.quantity_requested,0)'
            : (Schema::hasColumn('request_restocks','qty_requested')
                ? 'COALESCE(rr.qty_requested,0)'
                : (Schema::hasColumn('request_restocks','qty')
                    ? 'COALESCE(rr.qty,0)'
                    : '0'));

        // qty received: pakai kolom bila ada, kalau tidak sum dari restock_receipts.qty_good
        $rcvJoin = false;
        if (Schema::hasColumn('request_restocks','quantity_received')) {
            $qtyRcvExpr = 'COALESCE(rr.quantity_received,0)';
        } elseif (Schema::hasColumn('request_restocks','qty_received')) {
            $qtyRcvExpr = 'COALESCE(rr.qty_received,0)';
        } elseif (Schema::hasTable('restock_receipts') && Schema::hasColumn('restock_receipts','qty_good')) {
            $rcvJoin = true;
            $qtyRcvExpr = 'COALESCE(rcv.qty_rcv,0)';
        } else {
            $qtyRcvExpr = '0';
        }

        $orderMap = [
            1 => $hasCode ? 'rr.code' : 'rr.id',
            2 => 'p.name',
            3 => 's.name',
            4 => 'qty_req',   // pakai alias
            5 => 'qty_rcv',   // pakai alias
            6 => 'rr.status',
            7 => 'rr.created_at',
        ];
        $orderCol = $orderMap[$orderColIdx] ?? 'rr.id';

        $codeSelect = $hasCode ? 'rr.code as code' : "CONCAT('RR-', rr.id) as code";

        $base = DB::table('request_restocks as rr')
            ->leftJoin('products as p','p.id','=','rr.product_id')
            ->leftJoin('suppliers as s','s.id','=','rr.supplier_id');

        if ($rcvJoin) {
            $sub = DB::table('restock_receipts')
                    ->selectRaw('request_id, COALESCE(SUM(qty_good),0) as qty_rcv')
                    ->groupBy('request_id');
            $base->leftJoinSub($sub, 'rcv', 'rcv.request_id', '=', 'rr.id');
        }

        if ($hasWh && ($me->warehouse_id ?? null)) {
            $base->where('rr.warehouse_id', $me->warehouse_id);
        } elseif ($hasReqBy) {
            $base->where('rr.requested_by', $me->id);
        }

        $recordsTotal = (clone $base)->count();

        $base->selectRaw("
            rr.id,
            {$codeSelect},
            p.product_code,
            p.name as product_name,
            s.name as supplier_name,
            {$qtyReqExpr} as qty_req,
            {$qtyRcvExpr} as qty_rcv,
            COALESCE(rr.status,'pending') as status,
            rr.created_at
        ");

        if ($search !== '') {
            $base->where(function($q) use ($search,$hasCode){
                if ($hasCode) $q->where('rr.code','like',"%{$search}%");
                else $q->where('rr.id','like',"%{$search}%");
                $q->orWhere('p.name','like',"%{$search}%")
                  ->orWhere('p.product_code','like',"%{$search}%")
                  ->orWhere('s.name','like',"%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        // order: kalau by alias qty_req/qty_rcv → pakai ekspresi raw
        if ($orderCol === 'qty_req') {
            $base->orderByRaw($qtyReqExpr.' '.$orderDir);
        } elseif ($orderCol === 'qty_rcv') {
            $base->orderByRaw($qtyRcvExpr.' '.$orderDir);
        } else {
            $base->orderBy($orderCol, $orderDir);
        }

        $rows = $base->skip($start)->take($length)->get()->map(function($r, $idx) use ($start) {
            $badge = match (strtolower($r->status)) {
                'approved' => '<span class="badge bg-label-success">APPROVED</span>',
                'ordered'  => '<span class="badge bg-label-info">ORDERED</span>',
                'received' => '<span class="badge bg-label-primary">RECEIVED</span>',
                'cancelled'=> '<span class="badge bg-label-danger">CANCELLED</span>',
                default    => '<span class="badge bg-label-warning">PENDING</span>',
            };

            $canReceive = ! in_array(strtolower($r->status), ['received','cancelled']);

            $actions = '<div class="d-flex gap-1">'
                    . ($canReceive ? '<button class="btn btn-sm btn-outline-primary js-receive"
                        data-id="'.$r->id.'"
                        data-code="'.e($r->code).'"
                        data-product="'.e($r->product_name).'"
                        data-supplier="'.e($r->supplier_name).'"
                        data-qty_req="'.$r->qty_req.'"
                        data-qty_rcv="'.$r->qty_rcv.'"
                        data-action="'.e(route('restocks.receive',$r->id)).'">'   // ← route benar + tutup " >
                        .'<i class="bx bx-download"></i></button>' : '')
                    . '</div>';

            return [
                'rownum'     => $start + $idx + 1,
                'code'       => e($r->code),
                'product'    => e($r->product_name).'<div class="small text-muted">'.e($r->product_code).'</div>',
                'supplier'   => e($r->supplier_name),
                'qty_req'    => number_format((int)$r->qty_req,0,',','.'),
                'qty_rcv'    => number_format((int)$r->qty_rcv,0,',','.'),
                'status'     => $badge,
                'created_at' => \Carbon\Carbon::parse($r->created_at)->format('Y-m-d'),
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
        Log::error('restocks.datatable: '.$e->getMessage());
        return response()->json([
            'draw' => (int)$r->input('draw',1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Server error',
        ]);
    }
}


    public function store(Request $r)
    {
        $me = auth()->user();

        $payload = $r->all();
        if (empty($payload['quantity_requested'])) {
            $payload['quantity_requested'] = $payload['qty'] ?? $payload['quantity'] ?? null;
        }

        $data = validator($payload, [
            'supplier_id'        => ['required','exists:suppliers,id'],
            'product_id'         => ['required','exists:products,id'],
            'quantity_requested' => ['required','integer','min:1'],
            'cost_per_item'      => ['nullable','numeric','min:0'],
            'note'               => ['nullable','string','max:500'],
        ])->validate();

        $cost = (int)round($data['cost_per_item'] ?? 0);
        $qty  = (int)$data['quantity_requested'];

        $insert = [
            'supplier_id'        => $data['supplier_id'],
            'product_id'         => $data['product_id'],
            'quantity_requested' => $qty,
            'quantity_received'  => 0,
            'cost_per_item'      => $cost,
            'total_cost'         => $cost * $qty,
            'status'             => 'pending',
            'note'               => $data['note'] ?? null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];

        if (Schema::hasColumn('request_restocks','warehouse_id')) $insert['warehouse_id'] = $me->warehouse_id;
        if (Schema::hasColumn('request_restocks','requested_by')) $insert['requested_by'] = $me->id;

        if (Schema::hasColumn('request_restocks','code')) {
            $seq = (int) (DB::table('request_restocks')->max('id') ?? 0) + 1;
            $insert['code'] = 'RR-'.now()->format('ymd').'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
        }

        DB::table('request_restocks')->insert($insert);
        return back()->with('success','Request restock berhasil dibuat.');
    }

    private function nextReceiptCode(): string
    {
        $prefix = 'RCV-'.now()->format('ymd').'-';
        $last = DB::table('restock_receipts')->where('code','like',$prefix.'%')->orderByDesc('id')->value('code');
        $n = $last ? (int)substr($last,-4) + 1 : 1;
        return $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    public function receive(Request $r, $id)
    {
        $user = auth()->user();

        $data = $r->validate([
            'qty_good'      => 'required|integer|min:0',
            'qty_damaged'   => 'nullable|integer|min:0',
            'cost_per_item' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
            'photos.*'      => 'nullable|image|max:4096',
        ]);

        $qtyGood    = (int)($data['qty_good'] ?? 0);
        $qtyDamaged = (int)($data['qty_damaged'] ?? 0);
        if ($qtyGood === 0 && $qtyDamaged === 0) {
            return back()->with('error','Qty diterima dan rusak tidak boleh dua-duanya nol.');
        }

        $req = DB::table('request_restocks')->where('id',$id)->first();
        if (!$req) return back()->with('error','Restock request tidak ditemukan.');

        $reqQty   = (int)($req->quantity_requested ?? $req->quantity ?? $req->qty ?? 0);
        $sumGood  = (int) DB::table('restock_receipts')->where('request_id',$id)->sum('qty_good');
        $sumBad   = (int) DB::table('restock_receipts')->where('request_id',$id)->sum('qty_damaged');
        $already  = $sumGood + $sumBad;

        if ($reqQty > 0 && ($already + $qtyGood + $qtyDamaged) > $reqQty) {
            return back()->with('error','Total penerimaan melebihi qty request.');
        }

        $warehouseId = $user->warehouse_id ?? null;
        $productId   = (int)($req->product_id ?? 0);

        DB::beginTransaction();
        try {
            $payload = [
                'request_id'    => $id,
                'product_id'    => $productId,
                'warehouse_id'  => $warehouseId,
                'qty_good'      => $qtyGood,
                'qty_damaged'   => $qtyDamaged,
                'cost_per_item' => $data['cost_per_item'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'received_by'   => $user->id ?? null,
                'received_at'   => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
            if (Schema::hasColumn('restock_receipts','code')) {
                $payload['code'] = $this->nextReceiptCode();
            }

            DB::table('restock_receipts')->insert($payload);

            // GOOD → tambah ke stok gudang
            if ($qtyGood > 0 && Schema::hasTable('stock_levels')) {
                $exists = DB::table('stock_levels')->where([
                    'product_id'=>$productId,'owner_type'=>'warehouse','owner_id'=>$warehouseId
                ])->exists();

                if ($exists) {
                    DB::table('stock_levels')->where([
                        'product_id'=>$productId,'owner_type'=>'warehouse','owner_id'=>$warehouseId
                    ])->update([
                        'quantity'  => DB::raw('quantity + '.(int)$qtyGood),
                        'updated_at'=> now(),
                    ]);
                } else {
                    DB::table('stock_levels')->insert([
                        'product_id'=>$productId,'owner_type'=>'warehouse','owner_id'=>$warehouseId,
                        'quantity'=>$qtyGood,'created_at'=>now(),'updated_at'=>now()
                    ]);
                }
            }

            // movement
            if (Schema::hasTable('stock_movements')) {
                DB::table('stock_movements')->insert([
                    'product_id' => $productId,
                    'from_type'  => 'supplier',
                    'from_id'    => $req->supplier_id ?? 0,
                    'to_type'    => 'warehouse',
                    'to_id'      => $warehouseId,
                    'qty'        => $qtyGood,
                    'status'     => 'approved',
                    'approved_by'=> $user->id,
                    'approved_at'=> now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // update request
            $nowGood = (int) DB::table('restock_receipts')->where('request_id',$id)->sum('qty_good');
            $nowBad  = (int) DB::table('restock_receipts')->where('request_id',$id)->sum('qty_damaged');
            $nowAll  = $nowGood + $nowBad;
            $status  = ($reqQty > 0 && $nowAll >= $reqQty) ? 'received' : ($req->status ?? 'pending');

            DB::table('request_restocks')->where('id',$id)->update([
                'quantity_received' => $nowGood,
                'status'            => $status,
                'received_at'       => $status === 'received' ? now() : $req->received_at,
                'updated_at'        => now(),
            ]);

            DB::commit();
            return back()->with('success','Penerimaan berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error','Gagal menyimpan penerimaan: '.$e->getMessage());
        }
    }
}
