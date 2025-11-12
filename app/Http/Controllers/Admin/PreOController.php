<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\StockRequest; // tabel lo untuk permintaan dari WH/Sales
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreOController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q',''));
        $status = $request->get('status');

        $pos = PurchaseOrder::withCount('items')
            ->when($q, fn($qq)=> $qq->where('po_code','like',"%$q%"))
            ->when($status, fn($qq)=> $qq->where('status',$status))
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.po.index', compact('pos','q'));
    }

    // Bulk create PO dari stock_requests (group by supplier → bisa banyak PO)
    public function createFromRequests(Request $request)
    {
        $request->validate([
            'stock_request_ids' => 'required|array|min:1',
            'stock_request_ids.*' => 'integer'
        ]);

        $reqIds = $request->stock_request_ids;

        $requests = StockRequest::query()
            ->whereIn('id', $reqIds)
            ->whereIn('status', ['pending','approved']) // sesuaikan rule lo
            ->get();

        if ($requests->isEmpty()) return back()->with('error','Tidak ada stock request valid.');

        // group per supplier berdasarkan product.supplier_id
        $bySupplier = [];
        foreach ($requests as $sr) {
            $product = Product::find($sr->product_id);
            $supplierId = $product->supplier_id ?? null;
            $bySupplier[$supplierId ?? 0][] = [$sr, $product];
        }

        $created = [];
        DB::transaction(function () use (&$created, $bySupplier) {
            foreach ($bySupplier as $supplierId => $rows) {
                $po = PurchaseOrder::create([
                    'po_code'    => $this->generatePoCode(),
                    'supplier_id'=> $supplierId ?: null,
                    'ordered_by' => auth()->id(),
                    'status'     => 'draft',
                ]);

                foreach ($rows as [$sr, $product]) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id'        => $sr->product_id,
                        'warehouse_id'      => $sr->to_id ?? $sr->requester_id, // tujuan kirim
                        'qty_ordered'       => (int)$sr->quantity_requested,
                        'unit_price'        => (float)($product->purchasing_price ?? 0),
                        'discount_type'     => null,
                        'discount_value'    => null,
                        'line_total'        => 0,
                        'notes'             => 'Generated from stock_requests#'.$sr->id,
                    ]);

                    // tandai request
                    $sr->status = 'approved';
                    $sr->approved_by = auth()->id();
                    $sr->approved_at = now();
                    $sr->save();
                }

                $po->load('items');
                $po->recalcTotals();
                $created[] = $po->id;
            }
        });

        return count($created) === 1
            ? redirect()->route('po.edit', $created[0])->with('success','PO dibuat.')
            : redirect()->route('po.index')->with('success', count($created).' PO dibuat.');
    }

    public function edit(PurchaseOrder $po)
    {
        $po->load(['items.product','items.warehouse','supplier']);
        $statusDerived = $po->derivedStatus();

        return view('po.edit', [
            'po'      => $po,
            'status'  => $statusDerived,
            'subtotal'=> $po->subtotal,
            'discount'=> $po->discount_total,
            'grand'   => $po->grand_total,
        ]);
    }

    public function update(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id'            => 'required|integer|exists:purchase_order_items,id',
            'items.*.qty_ordered'   => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:percent,amount',
            'items.*.discount_value'=> 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $po) {
            foreach ($request->items as $line) {
                $it = PurchaseOrderItem::where('purchase_order_id',$po->id)->findOrFail($line['id']);
                if ($it->qty_received >= $it->qty_ordered) continue; // lock jika sudah full
                $it->qty_ordered   = (int)$line['qty_ordered'];
                $it->unit_price    = (float)$line['unit_price'];
                $it->discount_type = $line['discount_type'] ?: null;
                $it->discount_value= $line['discount_value'] ?: null;
                $it->save();
            }
            $po->notes = $request->notes; $po->save();
            $po->load('items'); $po->recalcTotals();
        });

        return back()->with('success','PO updated.');
    }

    public function order(PurchaseOrder $po)
    {
        $po->update(['status'=>'ordered','ordered_at'=>now()]);
        return back()->with('success','PO di-set ORDERED.');
    }

    public function cancel(PurchaseOrder $po)
    {
        $po->update(['status'=>'cancelled']);
        return back()->with('success','PO dibatalkan.');
    }

    public function exportPdf(PurchaseOrder $po)  { return back()->with('info','Export PDF belum diaktifkan.'); }
    public function exportExcel(PurchaseOrder $po){ return back()->with('info','Export Excel belum diaktifkan.'); }

    private function generatePoCode(): string
    {
        return 'PO-'.now()->format('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
    }
}
