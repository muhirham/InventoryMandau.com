    @extends('layouts.home')

    @section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @php
        $me           = auth()->user();
        $roles        = $me?->roles ?? collect();
        $isSuperadmin = $roles->contains('slug', 'superadmin');
        $isWarehouse  = $roles->contains('slug', 'warehouse');
        $myWarehouse  = $me?->warehouse?->warehouse_name
                        ?? $me?->warehouse?->name
                        ?? null;
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0 fw-bold">Goods Received</h4>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body">
        <form id="gr-filter-form" class="row g-2" method="GET" action="{{ route('goodreceived.index') }}">
            <div class="col-lg-3 col-md-4">
            <label class="form-label">Cari Kode GR / PO</label>
            <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="GR- / PO-">
            </div>

            <div class="col-lg-3 col-md-4">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select">
                <option value="">— Semua —</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
                @endforeach
            </select>
            </div>

            {{-- WAREHOUSE FILTER --}}
            <div class="col-lg-3 col-md-4">
            <label class="form-label">Warehouse</label>

            @if($isWarehouse)
                {{-- Warehouse user: dikunci ke warehouse sendiri --}}
                <input type="text"
                    class="form-control"
                    value="{{ $myWarehouse ?? '-' }}"
                    disabled>
                <input type="hidden" name="warehouse_id"
                    value="{{ $me?->warehouse_id }}">
            @else
                {{-- superadmin / admin bisa pilih semua warehouse --}}
                <select name="warehouse_id" class="form-select">
                <option value="">— Semua —</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" {{ $warehouseId == $w->id ? 'selected' : '' }}>
                    {{ $w->name ?? $w->warehouse_name }}
                    </option>
                @endforeach
                </select>
            @endif
            </div>

            <div class="col-lg-3 col-md-4">
            <label class="form-label">Periode</label>
            <div class="d-flex gap-1">
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            </div>

            <div class="col-12 d-flex justify-content-end mt-2">
            <button class="btn btn-primary me-2" type="submit">
                <i class="bx bx-search"></i> Filter
            </button>
            <a href="{{ route('goodreceived.index') }}" class="btn btn-outline-secondary">
                Reset
            </a>
            </div>
        </form>
        </div>
    </div>

    {{-- List per PO --}}
    <div class="card">
        <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th style="width:60px;">#</th>
                <th>PO Code</th>
                <th>GR Code (Terakhir)</th>
                <th>Product (Summary)</th>
                <th>Supplier</th>
                <th>Warehouse</th>
                <th>Last Received At</th>
                <th>Status Cancel GR</th>
                <th style="width:180px;" class="text-center">Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pos as $i => $po)
                @php
                // Summary product
                $totalLines   = $po->items->count();
                $firstItem    = $po->items->first();
                $firstProduct = optional($firstItem?->product)->name;
                if ($totalLines > 1) {
                    $productSummary = $firstProduct
                        ? $firstProduct . ' + ' . ($totalLines - 1) . ' item'
                        : $totalLines . ' item';
                } else {
                    $productSummary = $firstProduct ?? '-';
                }

                // GR
                $receipts      = $po->restockReceipts->sortByDesc('received_at');
                $lastReceipt   = $receipts->first();
                $lastGrCode    = $lastReceipt?->code;
                $lastReceiveAt = optional($lastReceipt?->received_at)?->format('Y-m-d H:i') ?? '-';

                $photosAll = $receipts->flatMap(fn ($r) => $r->photos ?? collect());

                // ==== SUPPLIER (ringkasan) ====
                $supFromPo = optional($po->supplier)->name;

                $itemSuppliers = $po->items->map(function ($it) {
                    return optional(optional($it->product)->supplier)->name;
                })->filter();

                $receiptSuppliers = $receipts->map(function ($r) {
                    return optional($r->supplier)->name;
                })->filter();

                $supplierNames = collect([$supFromPo])
                    ->merge($itemSuppliers)
                    ->merge($receiptSuppliers)
                    ->filter()
                    ->unique()
                    ->values();

                if ($supplierNames->isEmpty()) {
                    $supplierLabel = '-';
                } elseif ($supplierNames->count() === 1) {
                    $supplierLabel = $supplierNames->first();
                } else {
                    $supplierLabel = $supplierNames->first() . ' + ' . ($supplierNames->count() - 1) . ' supplier';
                }

                // ===== LABEL WAREHOUSE =====
                // Kalau warehouse_id null → dianggap Central Stock
                $warehouseNames = $receipts->map(function ($r) {
                        if ($r->warehouse) {
                            return $r->warehouse->warehouse_name
                                ?? $r->warehouse->name
                                ?? 'Warehouse #' . $r->warehouse_id;
                        }
                        return 'Central Stock';
                    })
                    ->filter()
                    ->unique()
                    ->values();

                if ($warehouseNames->isEmpty()) {
                    $warehouseLabel = '-';
                } elseif ($warehouseNames->count() === 1) {
                    $warehouseLabel = $warehouseNames->first();
                } else {
                    $hasCentral  = $warehouseNames->contains('Central Stock');
                    $otherCount  = $warehouseNames->count() - 1;

                    if ($hasCentral) {
                        // contoh: Central Stock + 1 wh
                        $warehouseLabel = 'Central Stock + ' . $otherCount . ' wh';
                    } else {
                        $warehouseLabel = $warehouseNames->first() . ' + ' . $otherCount . ' wh';
                    }
                }

                $reqGroup  = $deleteRequests[$po->id] ?? collect();
                $latestReq = $reqGroup->first();
                @endphp
                <tr>
                <td>{{ $pos->firstItem() + $i }}</td>
                <td><span class="badge bg-label-primary">{{ $po->po_code }}</span></td>
                <td>{{ $lastGrCode ?? '-' }}</td>
                <td>{{ $productSummary }}</td>
                <td>{{ $supplierLabel }}</td>
                <td>{{ $warehouseLabel }}</td>
                <td>{{ $lastReceiveAt }}</td>
                <td>
                    @if($latestReq)
                        <span class="badge bg-label-{{ $latestReq->status === 'pending' ? 'warning' : ($latestReq->status === 'approved' ? 'success' : 'secondary') }}">
                            Delete GR: {{ strtoupper($latestReq->status) }}
                        </span>
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>

                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                    {{-- DETAIL GR / PO --}}
                    <button type="button"
                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                            data-bs-toggle="modal"
                            data-bs-target="#poGrModal-{{ $po->id }}">
                        <i class="bx bx-file"></i>
                        <span>Detail</span>
                        @if($photosAll->count() > 0)
                        <span class="badge bg-primary border-0 ms-1"
                                style="font-size:0.65rem;min-width:20px;">
                            {{ $photosAll->count() }}
                        </span>
                        @endif
                    </button>

                    {{-- CANCEL GR → BUKA MODAL ALASAN --}}
                    @if($lastReceipt)
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-cancel-gr"
                                data-url="{{ route('goodreceived.request-delete', $lastReceipt) }}"
                                data-po-code="{{ $po->po_code }}"
                                data-gr-code="{{ $lastReceipt->code }}"
                                data-product="{{ $productSummary }}"
                                data-supplier="{{ $supplierLabel }}"
                                data-warehouse="{{ $warehouseLabel }}"
                                data-received="{{ $lastReceiveAt }}">
                        <i class="bx bx-trash"></i> Cancel GR
                        </button>
                    @endif
                    </div>
                </td>
                </tr>
            @empty
                <tr>
                <td colspan="9" class="text-center text-muted">Belum ada Goods Received.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>

        @if($pos->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $pos->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
    </div>

    {{-- ============ MODAL DETAIL PER PO (TETAP SAMA) ============ --}}
    @foreach($pos as $po)
    @php
        $receipts      = $po->restockReceipts->sortBy('id');
        $totalGood     = (int) $receipts->sum('qty_good');
        $totalDamaged  = (int) $receipts->sum('qty_damaged');
        $photosAll     = $receipts->flatMap(fn ($r) => $r->photos ?? collect());

        $goodPhotos    = collect();
        $damagedPhotos = collect();

        foreach ($photosAll as $p) {
            $tag = strtolower(trim($p->kind ?? $p->type ?? $p->status ?? $p->caption ?? ''));

            $isDamaged = (strpos($tag, 'dam') !== false)
                    || (strpos($tag, 'bad') !== false)
                    || (strpos($tag, 'rusak') !== false);

            if ($isDamaged) {
                $damagedPhotos->push($p);
            } else {
                $goodPhotos->push($p);
            }
        }

        if ($photosAll->count() > 0 && $goodPhotos->count() === 0 && $damagedPhotos->count() === 0) {
            $goodPhotos = $photosAll;
        }

        $totalLines = $po->items->count();

        $subtotal      = (float) ($po->subtotal        ?? 0);
        $discountTotal = (float) ($po->discount_total ?? 0);
        $grandTotal    = (float) ($po->grand_total    ?? ($subtotal - $discountTotal));

        $manualTotal = 0;
        foreach ($po->items as $it) {
            $price       = $it->unit_price ?? 0;
            $manualTotal += (int) $it->qty_ordered * (float) $price;
        }
        if ($grandTotal <= 0 && $manualTotal > 0) {
            $grandTotal = $manualTotal;
        }

        $reqGroup      = $deleteRequests[$po->id] ?? collect();
        $latestReq     = $reqGroup->first();

        $lastReceipt   = $receipts->sortByDesc('received_at')->first();
        $lastReceiveAt = optional($lastReceipt?->received_at)?->format('d/m/Y H:i') ?? '-';
        $lastReceiver  = $lastReceipt?->receiver->name ?? '-';

        // Supplier summary
        $supFromPo = optional($po->supplier)->name;
        $itemSuppliers = $po->items->map(function ($it) {
            return optional(optional($it->product)->supplier)->name;
        })->filter();
        $receiptSuppliers = $receipts->map(function ($r) {
            return optional($r->supplier)->name;
        })->filter();
        $supplierNames = collect([$supFromPo])
            ->merge($itemSuppliers)
            ->merge($receiptSuppliers)
            ->filter()
            ->unique()
            ->values();
        if ($supplierNames->isEmpty()) {
            $supplierLabelModal = '-';
        } elseif ($supplierNames->count() === 1) {
            $supplierLabelModal = $supplierNames->first();
        } else {
            $supplierLabelModal = $supplierNames->first() . ' + ' . ($supplierNames->count() - 1) . ' supplier';
        }

        // Label warehouse di modal
        $warehouseNamesModal = $receipts->map(function ($r) {
                if ($r->warehouse) {
                    return $r->warehouse->warehouse_name
                        ?? $r->warehouse->name
                        ?? 'Warehouse #' . $r->warehouse_id;
                }
                return 'Central Stock';
            })
            ->filter()
            ->unique()
            ->values();

        if ($warehouseNamesModal->isEmpty()) {
            $warehouseLabelModal = '-';
        } elseif ($warehouseNamesModal->count() === 1) {
            $warehouseLabelModal = $warehouseNamesModal->first();
        } else {
            $hasCentralModal = $warehouseNamesModal->contains('Central Stock');
            $otherCountModal = $warehouseNamesModal->count() - 1;

            if ($hasCentralModal) {
                $warehouseLabelModal = 'Central Stock + ' . $otherCountModal . ' wh';
            } else {
                $warehouseLabelModal = $warehouseNamesModal->first() . ' + ' . $otherCountModal . ' wh';
            }
        }

        $notes = $receipts->pluck('notes')->filter()->unique()->implode(' | ');

        $formatRupiah = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
    @endphp

    <div class="modal fade" id="poGrModal-{{ $po->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
            <h5 class="modal-title fw-bold">
                Tanda Terima Barang (GR) &amp; Detail PO
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-0">
            {{-- Header --}}
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-uppercase small text-muted mb-1">PO Code</div>
                    <div class="fs-5 fw-bold">{{ $po->po_code }}</div>
                    <div class="small text-muted">
                    Terakhir diterima: {{ $lastReceiveAt }}
                    </div>
                </div>
                <div class="text-end small">
                    <div class="fw-bold">{{ config('app.name', 'Inventory System') }}</div>
                    <div>{{ $warehouseLabelModal }}</div>
                    <div>Diterima oleh: <strong>{{ $lastReceiver }}</strong></div>
                </div>
                </div>
            </div>

            {{-- Info PO --}}
            <div class="row mb-2">
                <div class="col-md-7">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                    <th class="ps-0" style="width:140px;">Supplier</th>
                    <td class="ps-0">{{ $supplierLabelModal }}</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Total Item</th>
                    <td class="ps-0">{{ $totalLines }} item</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Subtotal</th>
                    <td class="ps-0">{{ $formatRupiah($subtotal) }}</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Discount</th>
                    <td class="ps-0">{{ $formatRupiah($discountTotal) }}</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Total Amount</th>
                    <td class="ps-0 fw-semibold">{{ $formatRupiah($grandTotal) }}</td>
                    </tr>
                </table>
                </div>
                <div class="col-md-5">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                    <th class="ps-0" style="width:160px;">Total Qty Order</th>
                    <td class="ps-0">{{ $po->items->sum('qty_ordered') }}</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Total Qty Received</th>
                    <td class="ps-0">{{ $po->items->sum('qty_received') }}</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Total Qty Good (GR)</th>
                    <td class="ps-0 text-success fw-semibold">{{ $totalGood }}</td>
                    </tr>
                    <tr>
                    <th class="ps-0">Total Qty Damaged (GR)</th>
                    <td class="ps-0 text-danger fw-semibold">{{ $totalDamaged }}</td>
                    </tr>
                </table>
                </div>
            </div>

            {{-- Item PO + harga --}}
            <div class="mb-3">
                <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr class="text-center">
                    <th style="width:50px;">No.</th>
                    <th>Nama Barang / Deskripsi</th>
                    <th style="width:90px;">Qty Order</th>
                    <th style="width:90px;">Qty Received</th>
                    <th style="width:120px;">Harga Satuan</th>
                    <th style="width:130px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($po->items as $idx => $item)
                    @php
                        $price        = $item->unit_price ?? 0;
                        $subtotalItem = (int) $item->qty_ordered * (float) $price;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                        {{ $item->product->name ?? '-' }}<br>
                        <small class="text-muted">
                            {{ $item->product->product_code ?? '' }}
                        </small>
                        </td>
                        <td class="text-center">{{ $item->qty_ordered }}</td>
                        <td class="text-center">{{ $item->qty_received }}</td>
                        <td class="text-end">{{ $formatRupiah($price) }}</td>
                        <td class="text-end fw-semibold">{{ $formatRupiah($subtotalItem) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                </table>
            </div>

            {{-- Notes --}}
            <div class="mb-3">
                <div class="fw-semibold mb-1">Catatan Penerimaan</div>
                <div class="border rounded p-2" style="min-height:60px;">
                {{ $notes ?: '-' }}
                </div>
            </div>

            {{-- Foto --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                <div class="fw-semibold mb-2">Foto Barang Good</div>
                @if($goodPhotos->count())
                    <div class="d-flex flex-wrap gap-2">
                    @foreach($goodPhotos as $p)
                        <a href="{{ asset('storage/'.$p->path) }}" target="_blank">
                        <img src="{{ asset('storage/'.$p->path) }}"
                            class="rounded border"
                            style="width:90px;height:90px;object-fit:cover;">
                        </a>
                    @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">Tidak ada foto barang good.</p>
                @endif
                </div>

                <div class="col-md-6 mb-3">
                <div class="fw-semibold text-danger mb-2">Foto Barang Damaged</div>
                @if($damagedPhotos->count())
                    <div class="d-flex flex-wrap gap-2">
                    @foreach($damagedPhotos as $p)
                        <a href="{{ asset('storage/'.$p->path) }}" target="_blank">
                        <img src="{{ asset('storage/'.$p->path) }}"
                            class="rounded border border-danger"
                            style="width:90px;height:90px;object-fit:cover;">
                        </a>
                    @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">Tidak ada foto barang damaged.</p>
                @endif
                </div>
            </div>

            {{-- Info delete GR --}}
            @if($latestReq)
                <div class="mt-4">
                <div class="alert alert-{{ $latestReq->status === 'pending' ? 'warning' : ($latestReq->status === 'approved' ? 'success' : 'secondary') }} mb-3">
                    <div class="fw-semibold mb-1">
                    Status Permohonan Delete GR: {{ strtoupper($latestReq->status) }}
                    </div>
                    <div class="small">
                    Diajukan oleh: <strong>{{ $latestReq->requester->name ?? '-' }}</strong><br>
                    Alasan: {{ $latestReq->reason ?? '-' }}<br>
                    @if($latestReq->approval_note)
                        Catatan Approval: {{ $latestReq->approval_note }}
                    @endif
                    </div>
                </div>
                </div>
            @endif

            {{-- Tanda tangan --}}
            <br><br><br>
            <div class="row mt-4">
                <div class="col-md-6 text-center">
                <div class="small mb-5">Diterima oleh,</div>
                <div style="height:40px;"></div>
                <div class="fw-semibold">{{ $lastReceiver ?: '________________' }}</div>
                <div class="small text-muted">Warehouse / Penerima</div>
                </div>
                <div class="col-md-6 text-center">
                <div class="small mb-5">Diserahkan oleh,</div>
                <div style="height:40px;"></div>
                <div class="fw-semibold">________________</div>
                <div class="small text-muted">Supplier / Kurir</div>
                </div>
            </div>
            </div>

            <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Tutup
            </button>
            </div>
        </div>
        </div>
    </div>
    @endforeach

    {{-- ============ MODAL CANCEL GR (ALASAN) ============ --}}
    <div class="modal fade" id="modalCancelGr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
        <form id="formCancelGr" method="POST" action="#">
            @csrf
            <div class="modal-header">
            <h5 class="modal-title fw-bold">Ajukan Pembatalan GR</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            <dl class="row mb-3">
                <dt class="col-sm-3">PO Code</dt>
                <dd class="col-sm-9" id="cancel_po_code">-</dd>

                <dt class="col-sm-3">GR Code</dt>
                <dd class="col-sm-9" id="cancel_gr_code">-</dd>

                <dt class="col-sm-3">Product</dt>
                <dd class="col-sm-9" id="cancel_product">-</dd>

                <dt class="col-sm-3">Supplier</dt>
                <dd class="col-sm-9" id="cancel_supplier">-</dd>

                <dt class="col-sm-3">Warehouse</dt>
                <dd class="col-sm-9" id="cancel_warehouse">-</dd>

                <dt class="col-sm-3">Last Received</dt>
                <dd class="col-sm-9" id="cancel_received">-</dd>
            </dl>

            <div class="mb-3">
                <label class="form-label fw-semibold">Alasan Pembatalan</label>
                <textarea class="form-control"
                        name="reason"
                        id="cancel_reason"
                        rows="3"
                        placeholder="Jelaskan kenapa GR ini ingin dibatalkan..."
                        required></textarea>
            </div>

            <small class="text-muted">
                Permohonan ini akan dikirim ke superadmin. Jika disetujui, stok akan dikembalikan
                dan status PO akan berubah menjadi <strong>reviewed</strong> sehingga dapat diedit kembali.
            </small>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Batal
            </button>
            <button type="submit" class="btn btn-danger">
                Kirim Permohonan
            </button>
            </div>
        </form>
        </div>
    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
    // SweetAlert flash
    @if(session('success'))
        Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: @json(session('success')),
        timer: 2500,
        showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: @json(session('error')),
        });
    @endif

    // === Auto-filter (tanpa harus klik tombol) ===
    const formFilter = document.getElementById('gr-filter-form');
    if (formFilter) {
        const debounce = (fn, delay = 400) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(null, args), delay);
        };
        };

        const autoSubmit = debounce(() => formFilter.submit(), 400);

        const qInput = formFilter.querySelector('input[name="q"]');
        if (qInput) {
        qInput.addEventListener('keyup', function (e) {
            if (e.key === 'Enter') return; // biar Enter tetap jalan
            autoSubmit();
        });
        }

        formFilter.querySelectorAll('select,input[type="date"]').forEach(el => {
        el.addEventListener('change', () => formFilter.submit());
        });
    }

    // === Modal Cancel GR ===
    const modalEl   = document.getElementById('modalCancelGr');
    const formCancel = document.getElementById('formCancelGr');

    if (modalEl && formCancel && window.bootstrap) {
        const modal = new bootstrap.Modal(modalEl);

        document.querySelectorAll('.btn-cancel-gr').forEach(btn => {
        btn.addEventListener('click', function () {
            const ds = this.dataset;

            formCancel.action = ds.url || '#';

            document.getElementById('cancel_po_code').textContent   = ds.poCode || '-';
            document.getElementById('cancel_gr_code').textContent   = ds.grCode || '-';
            document.getElementById('cancel_product').textContent   = ds.product || '-';
            document.getElementById('cancel_supplier').textContent  = ds.supplier || '-';
            document.getElementById('cancel_warehouse').textContent = ds.warehouse || '-';
            document.getElementById('cancel_received').textContent  = ds.received || '-';
            document.getElementById('cancel_reason').value          = '';

            modal.show();
        });
        });

        // konfirmasi sebelum kirim
        formCancel.addEventListener('submit', function (e) {
        if (!confirm('Kirim permohonan pembatalan GR ini ke superadmin?')) {
            e.preventDefault();
        }
        });
    }
    });
    </script>
    @endsection
