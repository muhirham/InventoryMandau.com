<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RestockReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GRController extends Controller
{
    public function store(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'receives'               => 'required|array|min:1',
            'receives.*.id'          => 'required|integer|exists:purchase_order_items,id',
            'receives.*.qty_good'    => 'required|integer|min:0',
            'receives.*.qty_damaged' => 'required|integer|min:0',
            'receives.*.notes'       => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $po) {
            foreach ($request->receives as $rcv) {
                /** @var PurchaseOrderItem $it */
                $it = PurchaseOrderItem::where('purchase_order_id',$po->id)->findOrFail($rcv['id']);

                $good = (int)$rcv['qty_good'];
                $bad  = (int)$rcv['qty_damaged'];
                $qty  = $good + $bad;
                if ($qty === 0) continue;

                $remaining = max(0, $it->qty_ordered - $it->qty_received);
                if ($qty > $remaining) abort(422, "Qty terima melebihi sisa untuk item #{$it->id}");

                $grCode = 'GR-'.now()->format('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);

                RestockReceipt::create([
                    'purchase_order_id'=> $po->id,
                    'code'             => $grCode,
                    'request_id'       => null,
                    'product_id'       => $it->product_id,
                    'warehouse_id'     => $it->warehouse_id,
                    'qty_good'         => $good,
                    'qty_damaged'      => $bad,
                    'cost_per_item'    => $it->unit_price,
                    'notes'            => $rcv['notes'] ?? null,
                    'received_by'      => auth()->id(),
                    'received_at'      => now(),
                ]);

                // Update progress item
                $it->qty_received += $qty;
                $it->save();

                // ====== UPDATE STOK & MOVEMENT (pakai DB builder biar aman dgn schema lo) ======
                // stock_movements
                DB::table('stock_movements')->insert([
                    'product_id' => $it->product_id,
                    'from_type'  => 'supplier',
                    'from_id'    => $po->supplier_id ?? 0,
                    'to_type'    => 'warehouse',
                    'to_id'      => $it->warehouse_id,
                    'qty'        => $good,                     // yang nambah stok hanya good
                    'status'     => 'approved',                // sesuaikan enum lo
                    'approved_by'=> auth()->id(),
                    'approved_at'=> now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // stock_levels: +good
                $sl = DB::table('stock_levels')->where([
                    'product_id' => $it->product_id,
                    'owner_type' => 'warehouse',
                    'owner_id'   => $it->warehouse_id,
                ]);

                if ($sl->exists()) {
                    DB::table('stock_levels')->where([
                        'product_id'=>$it->product_id,
                        'owner_type'=>'warehouse',
                        'owner_id'  =>$it->warehouse_id,
                    ])->update([
                        'quantity'  => DB::raw('quantity + '.(int)$good),
                        'updated_at'=> now(),
                    ]);
                } else {
                    DB::table('stock_levels')->insert([
                        'product_id'=>$it->product_id,
                        'owner_type'=>'warehouse',
                        'owner_id'  =>$it->warehouse_id,
                        'quantity'  => (int)$good,
                        'created_at'=> now(),
                        'updated_at'=> now(),
                    ]);
                }
            }

            // Update status PO
            $po->load('items');
            if ($po->items->every(fn($i)=> $i->qty_received >= $i->qty_ordered && $i->qty_ordered>0)) {
                $po->status = 'completed';
            } elseif ($po->items->contains(fn($i)=> $i->qty_received > 0)) {
                $po->status = 'partially_received';
            }
            $po->save();
        });

        return back()->with('success','Goods Received tersimpan & stok diperbarui.');
    }
}
