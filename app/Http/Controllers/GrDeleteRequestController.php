<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GrDeleteRequest;
use App\Models\RestockReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RequestRestock;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrDeleteRequestController extends Controller
{
    /**
     * LIST PERMOHONAN DELETE GR
     */
        public function index(Request $request)
    {
        $q      = trim($request->get('q', ''));
        $status = $request->get('status', '');

        // Ambil list permohonan delete GR
        $requests = GrDeleteRequest::with([
                // relasi yang MEMANG ada di model GrDeleteRequest
                'purchaseOrder',     // ->belongsTo(PurchaseOrder::class)
                'restockReceipt',    // ->belongsTo(RestockReceipt::class)
                'requester',         // ->belongsTo(User::class, 'requested_by')
                'approver',          // ->belongsTo(User::class, 'approved_by')
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";

                $qq->where(function ($sub) use ($like) {
                    // cari berdasarkan PO code
                    $sub->whereHas('purchaseOrder', function ($q2) use ($like) {
                        $q2->where('po_code', 'like', $like);
                    })

                    // atau berdasarkan GR code
                    ->orWhereHas('restockReceipt', function ($q2) use ($like) {
                        $q2->where('code', 'like', $like);
                    })

                    // atau berdasarkan nama requester
                    ->orWhereHas('requester', function ($q2) use ($like) {
                        $q2->where('name', 'like', $like);
                    });
                });
            })
            ->when($status !== '' && $status !== null, function ($qq) use ($status) {
                $qq->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(15);

        $requests->appends($request->all());

        return view('admin.masterdata.gr_delete_requests', [
            'requests' => $requests,
            'q'        => $q,
            'status'   => $status,
        ]);
    }


    /**
     * USER (admin pusat / admin warehouse) KIRIM PERMOHONAN DELETE GR
     * Route: POST /good-received/{receipt}/request-delete
     */
    public function store(Request $request, RestockReceipt $receipt)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        // Cegah double request PENDING untuk GR yang sama
        $existing = GrDeleteRequest::where('restock_receipt_id', $receipt->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('error', 'Sudah ada permohonan cancel GR yang masih pending untuk GR ini.');
        }

        $me = $request->user();

        GrDeleteRequest::create([
            'restock_receipt_id' => $receipt->id,
            'purchase_order_id'  => $receipt->purchase_order_id,
            'requested_by'       => $me?->id,
            'approved_by'        => null,
            'status'             => 'pending',
            'reason'             => $request->input('reason'),
            'approval_note'      => null,
        ]);

        return back()->with('success', 'Permohonan pembatalan GR berhasil dikirim ke superadmin.');
    }

    /**
     * APPROVE – rollback stok & qty received.
     * Route: POST /good-received/delete-requests/{deleteRequest}/approve
     */
   // app/Http/Controllers/GrDeleteRequestController.php

    public function approve(Request $request, GrDeleteRequest $deleteRequest)
    {
        $me = auth()->user();

        try {
            DB::transaction(function () use ($request, $deleteRequest, $me) {

                // ==== 1. Update status permohonan ====
                $deleteRequest->status        = 'approved';
                $deleteRequest->approved_by   = $me?->id;
                $deleteRequest->approval_note = $request->input('approval_note');
                $deleteRequest->save();

                // ==== 2. Ambil GR (RestockReceipt) yang mau di-rollback ====
                $rr = $deleteRequest->restockReceipt; // relasi restockReceipt() di model GrDeleteRequest
                if (!$rr) {
                    // fallback: kalau GR sudah hilang, minimal buka lagi PO ke "ordered"
                    if ($deleteRequest->purchase_order_id) {
                        $po = PurchaseOrder::find($deleteRequest->purchase_order_id);
                        if ($po) {
                            $po->status = 'ordered'; // HARUS value yang ada di ENUM
                            $po->save();
                        }
                    }
                    return;
                }

                // ==== 3. Ambil PO terkait ====
                $po = $rr->purchaseOrder ?: PurchaseOrder::find($deleteRequest->purchase_order_id);
                if (!$po) {
                    return; // gak ada PO, ya sudah sampai sini
                }

                // ==== 4. Tentukan pemilik stok (pusat / gudang) ====
                if ($rr->warehouse_id) {
                    // GR ke gudang tertentu
                    $ownerType = 'gudang';
                    $ownerId   = $rr->warehouse_id;
                } else {
                    // GR ke central stock
                    $ownerType = 'pusat';
                    $ownerId   = 0;
                }

                // ==== 5. Rollback qty_received per item + stok level ====
                $po->loadMissing('items'); // pastikan relasi items sudah ke-load

                foreach ($po->items as $poItem) {

                    // qty yang sudah pernah diterima dari GR ini
                    $qtyRollback = (int) ($poItem->qty_received ?? 0);

                    if ($qtyRollback <= 0) {
                        continue;
                    }

                    // 5a. Kurangi stok di stock_levels
                    $stock = StockLevel::firstOrCreate(
                        [
                            'owner_type' => $ownerType,
                            'owner_id'   => $ownerId,
                            'product_id' => $poItem->product_id,
                        ],
                        [
                            'quantity' => 0,
                        ]
                    );

                    $stock->quantity = max(0, (int) $stock->quantity - $qtyRollback);
                    $stock->save();

                    // 5b. Reset qty_received item PO → 0 lagi
                    $poItem->qty_received = max(0, (int) $poItem->qty_received - $qtyRollback);
                    $poItem->save();
                }

                // ==== 6. Hapus GR dari tabel restock_receipts ====
                // Kalau pakai relasi lain (photos, dsb) biasakan FK-nya ON DELETE CASCADE
                $rr->delete();

                // ==== 7. Kalau sudah tidak ada GR lain di PO ini, buka kembali status PO ====
                $masihAdaGR = RestockReceipt::where('purchase_order_id', $po->id)->exists();

                if (!$masihAdaGR) {
                    // PENTING: gunakan value yg valid di ENUM status purchase_orders
                    $po->status = 'ordered';   // supaya tombol Receive kebuka lagi
                }

                $po->save();
            });

            return redirect()
                ->back()
                ->with('success', 'Permohonan delete GR berhasil di-approve dan stok + PO sudah di-rollback.');
        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat approve permohonan: ' . $e->getMessage());
        }
    }


    /**
     * REJECT – hanya update status & catatan, tidak ubah stok / qty
     * Route: POST /good-received/delete-requests/{deleteRequest}/reject
     */
    public function reject(Request $request, GrDeleteRequest $deleteRequest)
    {
        if ($deleteRequest->status !== 'pending') {
            return back()->with('error', 'Permohonan ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $me = $request->user();

        $deleteRequest->status        = 'rejected';
        $deleteRequest->approved_by   = $me?->id;
        $deleteRequest->approval_note = $request->input('approval_note');
        $deleteRequest->save();

        return back()->with('success', 'Permohonan delete GR ditolak.');
    }
}
