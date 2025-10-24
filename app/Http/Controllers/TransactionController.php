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
    // show page
    public function index()
    {
        $users = User::select('id','name')->get();
        $warehouses = Warehouse::select('id','warehouse_name')->get();
        // we need selling_price for default product price in UI
        $products = Product::select('id','product_name','selling_price')->get();

        return view('admin.operations.transactions', compact('users','warehouses','products'));
    }

    // json for listing (used by AJAX)
    public function json(Request $req)
    {
        $items = Transaction::with(['user','warehouse'])->orderBy('id','desc')->get();

        $data = $items->map(function($t){
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
        });

        return response()->json(['data'=>$data]);
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
            // paid_amount may be empty -> default 0
        ]);

        DB::beginTransaction();
        try {
            // compute total server-side to be safe (sum of qty * price)
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

            // status logic: paid enough => completed, otherwise pending
            $status = ($paid >= $total && $total > 0) ? 'completed' : 'pending';

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

            // create details
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
            }

            DB::commit();
            return response()->json(['ok'=>true,'transaction_id'=>$t->id]);
        } catch (\Throwable $e){
            DB::rollBack();
            \Log::error('Transaction store error: '.$e->getMessage());
            return response()->json(['ok'=>false,'error'=>$e->getMessage()],500);
        }
    }
}