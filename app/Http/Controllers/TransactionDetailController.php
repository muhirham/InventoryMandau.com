<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;


class TransactionDetailController extends Controller
{
    public function index($transactionId)
{
    $transaction = Transaction::with(['user', 'warehouse', 'details.product'])
                            ->findOrFail($transactionId);

    return view('admin.operations.transactionDetail', compact('transaction'));
}

    public function json($transactionId) {
        $rows = DB::table('transaction_details as td')
            ->join('products as p','p.id','=','td.product_id')
            ->where('td.transaction_id',$transactionId)
            ->select('td.*','p.product_name')
            ->orderBy('td.id')
            ->get();

        return response()->json(['ok'=>true,'data'=>$rows]);
    }

    public function store(Request $r, $transactionId)
{
    $r->validate([
        'product_id' => 'required|array',
        'product_id.*' => 'required|exists:products,id',
        'quantity'   => 'required|array',
        'quantity.*' => 'required|numeric|min:0',
        'price'      => 'required|array',
        'price.*'    => 'required|numeric|min:0',
    ]);

    $productIds = $r->input('product_id', []);
    $quantities = $r->input('quantity', []);
    $prices     = $r->input('price', []);

    DB::beginTransaction();
    try {
        foreach ($productIds as $i => $pid) {
            $qty = (float)($quantities[$i] ?? 0);
            $price = (float)($prices[$i] ?? 0);
            $subtotal = $qty * $price;

            DB::table('transaction_details')->insert([
                'transaction_id' => $transactionId,
                'product_id' => $pid,
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $subtotal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // update total
        $total = DB::table('transaction_details')->where('transaction_id', $transactionId)->sum('subtotal');
        DB::table('transactions')->where('id', $transactionId)->update(['total' => $total, 'updated_at' => now()]);

        DB::commit();
        return response()->json(['ok' => true, 'total' => $total]);
    } catch(\Throwable $e){
        DB::rollBack();
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

    public function update(Request $r, $detailId) {
        $r->validate([
            'quantity' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        $subtotal = $r->quantity * $r->price;
        DB::table('transaction_details')->where('id',$detailId)->update([
            'quantity'=>$r->quantity,
            'price'=>$r->price,
            'subtotal'=>$subtotal,
            'updated_at'=>now()
        ]);

        return response()->json(['ok'=>true,'subtotal'=>$subtotal]);
    }

    public function destroy($detailId) {
        $detail = DB::table('transaction_details')->where('id',$detailId)->first();
        if($detail) {
            DB::table('transaction_details')->where('id',$detailId)->delete();

            // Update total transaksi
            $total = DB::table('transaction_details')->where('transaction_id',$detail->transaction_id)->sum('subtotal');
            DB::table('transactions')->where('id',$detail->transaction_id)->update(['total'=>$total,'updated_at'=>now()]);
        }
        return response()->json(['ok'=>true,'total'=>$total ?? 0]);
    }
}
