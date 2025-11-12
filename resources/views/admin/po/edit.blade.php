    @extends('layouts.home')

    @section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0 fw-bold">PO: {{ $po->po_code }} <span class="badge bg-label-info text-uppercase">{{ $status }}</span></h5>
        <div class="d-flex gap-2">
        <a href="{{ route('admin.po.pdf',$po->id) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        <a href="{{ route('admin.po.excel',$po->id) }}" class="btn btn-outline-secondary btn-sm">Excel</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ implode(', ', $errors->all()) }}</div>
    @endif

    {{-- FORM UPDATE --}}
    <form method="post" action="{{ route('admin.po.update', $po->id) }}">
        @csrf @method('PUT')

        <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
            <div class="small text-muted">Supplier</div>
            <div class="fw-semibold">{{ $po->supplier->supplier_name ?? '-' }}</div>
            </div>
            <div class="ms-auto" style="min-width:320px">
            <label class="form-label small">Notes</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes',$po->notes) }}</textarea>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                <th>#</th>
                <th>Product</th>
                <th>Warehouse</th>
                <th style="width:120px">Qty</th>
                <th style="width:140px">Unit Price</th>
                <th style="width:140px">Disc Type</th>
                <th style="width:140px">Disc Value</th>
                <th class="text-end" style="width:160px">Line Total</th>
                <th style="width:120px">Receive</th>
                </tr>
            </thead>
            <tbody id="poLines">
                @foreach($po->items as $i => $it)
                <tr data-id="{{ $it->id }}">
                <td>{{ $i+1 }}
                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $it->id }}">
                </td>
                <td>
                    <div class="fw-semibold">{{ $it->product->product_name ?? $it->product_id }}</div>
                    <div class="small text-muted">ID: {{ $it->product_id }}</div>
                </td>
                <td>{{ $it->warehouse->warehouse_name ?? $it->warehouse_id }}</td>
                <td>
                    <input name="items[{{ $i }}][qty_ordered]" type="number" min="1" class="form-control form-control-sm qty"
                        value="{{ $it->qty_ordered }}" @disabled($it->qty_received >= $it->qty_ordered)>
                    <div class="small text-muted">Received: {{ $it->qty_received }} / Rem: {{ max(0,$it->qty_ordered - $it->qty_received) }}</div>
                </td>
                <td>
                    <input name="items[{{ $i }}][unit_price]" type="number" step="0.01" min="0" class="form-control form-control-sm price"
                        value="{{ $it->unit_price }}" @disabled($it->qty_received >= $it->qty_ordered)>
                </td>
                <td>
                    <select name="items[{{ $i }}][discount_type]" class="form-select form-select-sm dtype" @disabled($it->qty_received >= $it->qty_ordered)>
                    <option value="">-</option>
                    <option value="percent" @selected($it->discount_type==='percent')>%</option>
                    <option value="amount"  @selected($it->discount_type==='amount')>Nominal</option>
                    </select>
                </td>
                <td>
                    <input name="items[{{ $i }}][discount_value]" type="number" step="0.01" min="0" class="form-control form-control-sm dval"
                        value="{{ $it->discount_value }}" @disabled($it->qty_received >= $it->qty_ordered)>
                </td>
                <td class="text-end fw-bold line-total">{{ number_format($it->line_total,0,',','.') }}</td>
                <td>
                    @if($it->qty_received < $it->qty_ordered)
                    <button type="button" class="btn btn-sm btn-success btnReceive"
                    data-id="{{ $it->id }}"
                    data-product="{{ $it->product->product_name ?? $it->product_id }}"
                    data-warehouse="{{ $it->warehouse->warehouse_name ?? $it->warehouse_id }}">
                    Receive
                    </button>
                    @else
                    <span class="badge bg-label-success">DONE</span>
                    @endif
                </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                <th colspan="7" class="text-end">Subtotal</th>
                <th class="text-end" id="subTotal">{{ number_format($subtotal,0,',','.') }}</th>
                <th></th>
                </tr>
                <tr>
                <th colspan="7" class="text-end">Discount</th>
                <th class="text-end" id="discTotal">{{ number_format($discount,0,',','.') }}</th>
                <th></th>
                </tr>
                <tr>
                <th colspan="7" class="text-end">Grand Total</th>
                <th class="text-end fs-5" id="grandTotal">{{ number_format($grand,0,',','.') }}</th>
                <th></th>
                </tr>
            </tfoot>
            </table>
        </div>

        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Simpan Perubahan</button>
            <form method="post" action="{{ route('admin.po.order',$po->id) }}">
            @csrf
            <button class="btn btn-warning" type="submit">Set ORDERED</button>
            </form>
            <form method="post" action="{{ route('admin.po.cancel',$po->id) }}">
            @csrf
            <button class="btn btn-outline-danger" type="submit">Cancel</button>
            </form>
            <a href="{{ route('admin.po.index') }}" class="btn btn-outline-secondary ms-auto">Kembali</a>
        </div>
        </div>
    </form>

    {{-- FORM RECEIVE (hidden batch) --}}
    <form id="frmReceive" method="post" action="{{ route('admin.po.receive',$po->id) }}" class="d-none">
        @csrf
        <div id="receivePayload"></div>
    </form>

    {{-- Modal Receive --}}
    <div class="modal fade" id="mdlReceive" tabindex="-1">
        <div class="modal-dialog">
        <div class="modal-content">
            <form id="frmReceiveItem" onsubmit="return false">
            <div class="modal-header">
                <h5 class="modal-title">Receive Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rcv_id">
                <div class="mb-2">
                <label class="form-label">Product</label>
                <input class="form-control" id="rcv_product" readonly>
                </div>
                <div class="mb-2">
                <label class="form-label">Warehouse</label>
                <input class="form-control" id="rcv_warehouse" readonly>
                </div>
                <div class="row g-2">
                <div class="col">
                    <label class="form-label">Qty Good</label>
                    <input type="number" min="0" class="form-control" id="rcv_good" value="0">
                </div>
                <div class="col">
                    <label class="form-label">Qty Damaged</label>
                    <input type="number" min="0" class="form-control" id="rcv_bad" value="0">
                </div>
                </div>
                <div class="mt-2">
                <label class="form-label">Notes</label>
                <textarea class="form-control" id="rcv_notes" rows="2"></textarea>
                </div>
                <div class="alert alert-info mt-2 small">
                Tekan <b>Ctrl + Enter</b> untuk submit batch receive.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btnAddReceive">Tambahkan</button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
            </form>
        </div>
        </div>
    </div>

    </div>

    @push('scripts')
    <script>
    (function(){
    const fmt = n => (Math.round(n)).toLocaleString('id-ID');
    const tbody = document.querySelector('#poLines');

    function recalc(){
        let sub=0, disc=0, grand=0;
        tbody.querySelectorAll('tr').forEach(tr=>{
        const qty   = parseFloat(tr.querySelector('.qty')?.value || 0);
        const price = parseFloat(tr.querySelector('.price')?.value || 0);
        const dtype = tr.querySelector('.dtype')?.value || '';
        const dval  = parseFloat(tr.querySelector('.dval')?.value || 0);

        const lineSub = qty*price;
        let lineDisc=0;
        if(dtype==='percent') lineDisc = lineSub*(dval/100);
        if(dtype==='amount')  lineDisc = dval;
        let lineTot = Math.max(0, lineSub - lineDisc);

        const cell = tr.querySelector('.line-total');
        if(cell) cell.textContent = fmt(lineTot);

        sub  += lineSub;
        disc += lineDisc;
        grand+= lineTot;
        });
        document.querySelector('#subTotal').textContent  = fmt(sub);
        document.querySelector('#discTotal').textContent = fmt(disc);
        document.querySelector('#grandTotal').textContent= fmt(grand);
    }

    tbody.addEventListener('input', function(e){
        if(e.target.matches('.qty,.price,.dtype,.dval')) recalc();
    });

    // Modal Receive
    let mdl;
    document.querySelectorAll('.btnReceive').forEach(btn=>{
        btn.addEventListener('click', ()=>{
        document.querySelector('#rcv_id').value = btn.dataset.id;
        document.querySelector('#rcv_product').value = btn.dataset.product;
        document.querySelector('#rcv_warehouse').value = btn.dataset.warehouse;
        document.querySelector('#rcv_good').value = 0;
        document.querySelector('#rcv_bad').value = 0;
        document.querySelector('#rcv_notes').value = '';
        mdl = new bootstrap.Modal(document.getElementById('mdlReceive'));
        mdl.show();
        });
    });

    document.querySelector('#btnAddReceive').addEventListener('click', ()=>{
        const id = document.querySelector('#rcv_id').value;
        const good = parseInt(document.querySelector('#rcv_good').value||0);
        const bad  = parseInt(document.querySelector('#rcv_bad').value||0);
        const notes= document.querySelector('#rcv_notes').value||'';

        if(good+bad<=0){ alert('Isi qty yang diterima.'); return; }

        // append payload hidden
        const cont = document.querySelector('#receivePayload');
        const idx  = cont.children.length;
        cont.insertAdjacentHTML('beforeend', `
        <input type="hidden" name="receives[${idx}][id]" value="${id}">
        <input type="hidden" name="receives[${idx}][qty_good]" value="${good}">
        <input type="hidden" name="receives[${idx}][qty_damaged]" value="${bad}">
        <input type="hidden" name="receives[${idx}][notes]" value="${notes.replace(/"/g,'&quot;')}">
        `);
        mdl.hide();
    });

    // Submit batch receive pakai Ctrl+Enter
    document.addEventListener('keydown', function(e){
        if(e.ctrlKey && e.key==='Enter'){
        const cont = document.querySelector('#receivePayload');
        if(cont.children.length===0){ alert('Belum ada item receive yang ditambahkan.'); return; }
        document.querySelector('#frmReceive').submit();
        }
    });

    recalc();
    })();
    </script>
    @endpush
    @endsection
