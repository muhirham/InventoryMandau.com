<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    protected int $perPage = 10;

    // show page
    public function index()
    {
        $users = User::select('id','name')->get();
        $warehouses = Warehouse::select('id','warehouse_name')->get();
        $products = Product::select('id','product_name','selling_price')->get();

        return view('admin.operations.transactions', compact('users','warehouses','products'));
    }

    // json for listing (used by AJAX) WITH pagination + optional q filter
    public function json(Request $req)
    {
        $q = trim((string)$req->query('q',''));
        $page = max(1, (int)$req->query('page', 1));

        $query = Transaction::with(['user','warehouse'])->orderBy('id','desc');

        if ($q !== '') {
            $query->where(function($qq) use ($q) {
                $qq->where('id', 'like', "%{$q}%")
                   ->orWhereHas('user', function($u) use ($q){ $u->where('name','like', "%{$q}%"); })
                   ->orWhereHas('warehouse', function($w) use ($q){ $w->where('warehouse_name','like', "%{$q}%"); })
                   ->orWhere('transaction_type','like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($this->perPage, ['*'], 'page', $page);

        $items = $paginator->items();

        $data = collect($items)->map(function($t){
            return [
                'id' => $t->id,
                'user_name' => $t->user->name ?? '-',
                'warehouse_name' => $t->warehouse->warehouse_name ?? '-',
                'transaction_date' => optional($t->transaction_date)->format('Y-m-d H:i') ?? $t->transaction_date,
                'transaction_type' => $t->transaction_type,
                'status' => $t->status,
                'total' => (float)$t->total,
                'paid_amount' => (float)$t->paid_amount,
                'change_amount' => (float)$t->change_amount,
            ];
        })->values()->all();

        $paginationHtml = $paginator->withQueryString()->links('pagination::bootstrap-5')->render();

        if (!trim($paginationHtml)) {
            $last = $paginator->lastPage() ?: 1;
            $cur = $paginator->currentPage() ?: 1;
            $from = max(1, $cur - 3);
            $to = min($last, $cur + 3);

            $html = '<nav aria-label="Transactions pagination"><ul class="pagination">';
            $html .= '<li class="page-item '.($cur==1?'disabled':'').'"><a class="page-link" href="'.$paginator->url(1).'" data-page="1">&laquo;</a></li>';
            for ($i=$from;$i<=$to;$i++){
                if ($i==$cur) {
                    $html .= '<li class="page-item active" aria-current="page"><span class="page-link">'.$i.'</span></li>';
                } else {
                    $html .= '<li class="page-item"><a class="page-link" href="'.$paginator->url($i).'" data-page="'.$i.'">'.$i.'</a></li>';
                }
            }
            $html .= '<li class="page-item '.($cur==$last?'disabled':'').'"><a class="page-link" href="'.$paginator->url($last).'" data-page="'.$last.'">&raquo;</a></li>';
            $html .= '</ul></nav>';

            $paginationHtml = $html;
        }

        return response()->json([
            'data' => $data,
            'pagination' => $paginationHtml,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    // store transaction and details
    public function store(Request $req)
    {
        $req->validate([
            'user_id'=>'required|exists:users,id',
            'warehouse_id'=>'required|exists:warehouses,id',
            'transaction_date'=>'required|date',
            'transaction_type'=>'required|string',
            'product_id'=>'required|array|min:1',
            'product_id.*'=>'required|exists:products,id',
            'quantity'=>'required|array',
            'price'=>'required|array',
        ]);

        DB::beginTransaction();
        try {
            $productIds = $req->input('product_id', []);
            $qtys = $req->input('quantity', []);
            $prices = $req->input('price', []);

            $total = 0;
            foreach($productIds as $i => $pid){
                $q = isset($qtys[$i]) ? (float)$qtys[$i] : 0;
                $p = isset($prices[$i]) ? (float)$prices[$i] : 0;
                $total += $q * $p;
            }

            $paid = floatval($req->input('paid_amount', 0));
            $change = max(0, $paid - $total);
            $status = ($paid >= $total && $total > 0) ? 'completed' : 'pending';

            // buat transaksi
            $t = Transaction::create([
                'user_id' => $req->user_id,
                'warehouse_id' => $req->warehouse_id,
                'transaction_date' => Carbon::parse($req->transaction_date),
                'transaction_type' => $req->transaction_type,
                'total' => $total,
                'paid_amount' => $paid,
                'change_amount' => $change,
                'status' => $status,
            ]);

            foreach($productIds as $i => $pid){
                $q = isset($qtys[$i]) ? (float)$qtys[$i] : 0;
                $p = isset($prices[$i]) ? (float)$prices[$i] : 0;
                if($q <= 0) continue;

                TransactionDetail::create([
                    'transaction_id' => $t->id,
                    'product_id' => $pid,
                    'quantity' => $q,
                    'price' => $p,
                    'subtotal' => $q * $p,
                ]);

                if($req->transaction_type === 'sale'){
                    // ambil stock per warehouse
                    $stock = DB::table('product_stock')
                        ->where('product_id', $pid)
                        ->where('warehouse_id', $req->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock) {
                        throw new \Exception("Stock not found for product: {$pid} in this warehouse");
                    }

                    if ($q > $stock->final_stock) {
                        throw new \Exception("Quantity ({$q}) exceeds stock ({$stock->final_stock}) in warehouse: {$req->warehouse_id}");
                    }

                    // update stock_out & final_stock
                    DB::table('product_stock')->where('id', $stock->id)->update([
                        'stock_out' => $stock->stock_out + $q,
                        'final_stock' => $stock->final_stock - $q,
                        'last_update' => now(),
                    ]);

                    // sinkronisasi total stock ke products
                    $totalStock = DB::table('product_stock')
                        ->where('product_id', $pid)
                        ->sum('final_stock');

                    DB::table('products')->where('id', $pid)->update([
                        'stock' => $totalStock
                    ]);
                }
            }

            DB::commit();
            return response()->json(['ok'=>true,'transaction_id'=>$t->id]);

        } catch (\Throwable $e){
            DB::rollBack();
            \Log::error('Transaction store error: '.$e->getMessage());
            return response()->json(['ok'=>false,'error'=>$e->getMessage()],500);
        }
    }

    // approve endpoint
    public function approve($id)
    {
        $t = Transaction::find($id);
        if(!$t) return response()->json(['ok'=>false,'error'=>'Not found'],404);

        $t->status = 'completed';
        $t->save();

        return response()->json(['ok'=>true]);
    }
}
