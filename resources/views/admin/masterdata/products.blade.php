    @extends('layouts.home')
    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Product List</h5>

        <div class="d-flex gap-2 align-items-center">
            <input id="searchBox" class="form-control" style="min-width:280px" placeholder="Search products...">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mdlAdd">
            <i class="bx bx-plus"></i> Add Product
            </button>
        </div>
        </div>

        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>CODE</th>
                <th>PRODUCT NAME</th>
                <th>CATEGORY</th>
                <th>SUPPLIER</th>
                <th class="text-end">STOCK</th>
                <th class="text-end">PURCHASE</th>
                <th class="text-end">SELLING</th>
                <th>PACKAGE</th>
                <th class="text-end">ACTIONS</th>
            </tr>
            </thead>

            <tbody id="tbodyProducts">
            @forelse($products as $p)
            <tr class="striped-row">
                <td><span class="fw-semibold text-dark">{{ $p->product_code ?? '-' }}</span></td>
                <td>
                <div class="fw-semibold">{{ $p->product_name }}</div>
                <div class="text-muted small">Group: {{ $p->product_group ?? '-' }}</div>
                </td>
                <td>{{ $p->category->name ?? $p->category->category_name ?? '-' }}</td>
                <td>{{ $p->supplier->name ?? $p->supplier->company_name ?? '-' }}</td>
                <td class="text-end"><span class="badge bg-label-info">{{ number_format((int)($p->stock ?? 0), 0, ',', '.') }}</span></td>
                <td class="text-end text-muted">Rp{{ number_format((float)($p->purchase_price ?? 0), 0, ',', '.') }}</td>
                <td class="text-end fw-semibold text-success">Rp{{ number_format((float)($p->selling_price ?? 0), 0, ',', '.') }}</td>
                <td>{{ $p->package_type ?? '-' }}</td>
                <td class="text-end">
                <div class="dropdown">
                    <button class="btn btn-text-secondary p-0" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded fs-4"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="bx bx-show me-2"></i> Detail</a></li>
                    <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#mdlEdit-{{ $p->id }}"><i class="bx bx-edit-alt me-2"></i> Edit</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('products.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Yakin hapus produk ini?')">
                        @csrf @method('DELETE')
                        <button class="dropdown-item text-danger" type="submit"><i class="bx bx-trash me-2"></i> Delete</button>
                        </form>
                    </li>
                    </ul>
                </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-5">Belum ada data produk.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- ===== MODALS EDIT untuk data halaman pertama (server render) ===== --}}
    <div id="modalZone">
        @foreach($products as $p)
        <div class="modal fade" id="mdlEdit-{{ $p->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title fw-semibold">Edit Product #{{ $p->id }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            @php $v = fn($key,$def=null)=> old($key, data_get($p,$key,$def)); @endphp
            <form action="{{ route('products.update', $p->id) }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="open" value="edit-{{ $p->id }}">
            <div class="modal-body">
                <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Product Code <span class="text-danger">*</span></label>
                    <input type="text" name="product_code" class="form-control @error('product_code') is-invalid @enderror" value="{{ $v('product_code') }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror" value="{{ $v('product_name') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                    <option value="">— Choose —</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected($v('category_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                    <option value="">— Choose —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($v('supplier_id')==$s->id)>{{ $s->name }}</option>
                    @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                    <option value="">— Choose —</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($v('warehouse_id')==$w->id)>{{ $w->name }}</option>
                    @endforeach
                    </select>
                </div>

                {{-- masker ribuan --}}
                <div class="col-md-4">
                    <label class="form-label">Purchase Price</label>
                    <input type="text" inputmode="numeric" name="purchase_price" class="form-control money" value="{{ $v('purchase_price',0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price</label>
                    <input type="text" inputmode="numeric" name="selling_price" class="form-control money" value="{{ $v('selling_price',0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock</label>
                    <input type="text" inputmode="numeric" name="stock" class="form-control int" value="{{ (int)$v('stock',0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Package Type</label>
                    <input type="text" name="package_type" class="form-control" value="{{ $v('package_type') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Group</label>
                    <input type="text" name="product_group" class="form-control" value="{{ $v('product_group') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Registration Number</label>
                    <input type="text" name="registration_number" class="form-control" value="{{ $v('registration_number') }}">
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
            </form>
        </div></div>
        </div>
        @endforeach
    </div>

    {{-- ===== MODAL: ADD ===== --}}
    <div class="modal fade" id="mdlAdd" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title fw-semibold">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            <input type="hidden" name="open" value="add">
            <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-4">
                <label class="form-label">Product Code <span class="text-danger">*</span></label>
                <input type="text" name="product_code" value="{{ old('product_code') }}" class="form-control @error('product_code') is-invalid @enderror" required>
                </div>
                <div class="col-md-8">
                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="product_name" value="{{ old('product_name') }}" class="form-control @error('product_name') is-invalid @enderror" required>
                </div>

                <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">— Choose —</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                </div>
                <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">— Choose —</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
                </div>
                <div class="col-md-4">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">— Choose —</option>
                    @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
                </div>

                {{-- masker ribuan --}}
                <div class="col-md-4">
                <label class="form-label">Purchase Price</label>
                <input type="text" inputmode="numeric" name="purchase_price" class="form-control money" value="{{ old('purchase_price',0) }}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Selling Price</label>
                <input type="text" inputmode="numeric" name="selling_price" class="form-control money" value="{{ old('selling_price',0) }}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Stock</label>
                <input type="text" inputmode="numeric" name="stock" class="form-control int" value="{{ old('stock',0) }}">
                </div>

                <div class="col-md-4">
                <label class="form-label">Package Type</label>
                <input type="text" name="package_type" class="form-control" value="{{ old('package_type') }}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Product Group</label>
                <input type="text" name="product_group" class="form-control" value="{{ old('product_group') }}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Registration Number</label>
                <input type="text" name="registration_number" class="form-control" value="{{ old('registration_number') }}">
                </div>
            </div>
            </div>
            <div class="modal-footer">
            <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Create</button>
            </div>
        </form>
        </div></div>
    </div>

    </div>

    @if(session('success'))
    <script>
    document.addEventListener('DOMContentLoaded', ()=> {
    Swal.fire({ icon:'success', title:'Berhasil', text:'{{ session('success') }}' });
    });
    </script>
    @endif
    @endsection

    @push('styles')
    <style>
        .swal2-container{ z-index:20000 !important; }
    .table tbody tr.striped-row:nth-child(odd) { background: #f8f9fa; }
    .table tbody tr.striped-row:hover { background: #eef2ff; }
    .btn-text-secondary { background: transparent; border: 0; color: var(--bs-secondary-color); }
    </style>
    @endpush

    @push('scripts')
    <script>
    /* ================== DATA DROPDOWN (biarkan kalau sudah ada) ================== */
    window.DD = window.DD || {
    categories: @json($categories ?? []),
    suppliers:  @json($suppliers ?? []),
    warehouses: @json($warehouses ?? []),
    updateUrlPattern: @json(route('products.update', 0)),
    csrf: @json(csrf_token()),
    };

    /* ================== FORMATTER ANGKA — FIX “100.000 jadi 100” ================== */
    // Deteksi gaya penulisan:
    // - Kalau ada koma => koma = desimal, titik = ribuan
    // - Kalau hanya titik:
    //    * cocok ^\d{1,3}(\.\d{3})+$ => titik = ribuan
    //    * selain itu => titik = desimal (gaya 1234.56)
    // - Selain itu => tidak ada desimal
    function normalize(val, isInt=false){
    let s = String(val ?? '').replace(/[^\d.,]/g,'');
    if (!s) return '0';

    const hasComma = s.includes(',');
    const hasDot   = s.includes('.');
    let decimalSep = null;

    if (hasComma) {
        decimalSep = ',';
    } else if (hasDot) {
        const thousandPattern = /^\d{1,3}(?:\.\d{3})+$/; // 1.000 / 12.345.678
        decimalSep = thousandPattern.test(s) ? null : '.';
    } else {
        decimalSep = null;
    }

    if (isInt || decimalSep === null) {
        // integer / tanpa desimal: buang semua non-digit
        const intPart = s.replace(/\D/g,'') || '0';
        return isInt ? intPart : intPart;
    }

    // ada desimal → gunakan pemisah terakhir sebagai desimal
    const last = s.lastIndexOf(decimalSep);
    let intPart = s.slice(0, last).replace(/\D/g,'');
    let decPart = s.slice(last + 1).replace(/\D/g,'').slice(0, 2);
    if (!intPart) intPart = '0';
    return decPart ? `${intPart}.${decPart}` : intPart;
    }

    // Tampilkan rapi (ID): ribuan titik, desimal koma
    function pretty(val, isInt=false){
    const plain = normalize(val, isInt);            // "1234.56"
    let [i, d=''] = plain.split('.');
    i = i.replace(/^0+(?=\d)/,'');
    i = (i || '0').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return isInt ? i : (i + (d ? ','+d : ''));
    }

    // Masker UX: focus = plain, blur = pretty
    function bindMask(root=document){
    root.querySelectorAll('.money, .int').forEach(el=>{
        const isInt = el.classList.contains('int');

        // Init pertama
        el.value = pretty(el.value, isInt);

        el.addEventListener('focus', ()=>{
        el.value = normalize(el.value, isInt);
        // caret di akhir
        queueMicrotask(()=>{ el.selectionStart = el.selectionEnd = el.value.length; });
        });

        // Jangan terlalu agresif saat input; cukup izinkan angka + 1 separator
        el.addEventListener('input', ()=>{
        let v = String(el.value).replace(/[^\d.,]/g,'');
        // biarkan user ketik bebas; normalisasi terjadi saat blur/submit
        el.value = v;
        });

        el.addEventListener('blur', ()=>{
        el.value = pretty(el.value, isInt);
        });
    });
    }

    // Sebelum submit: kirim nilai plain ke server
    document.addEventListener('submit', (e)=>{
    const f = e.target;
    if (!f.closest('.modal')) return; // hanya untuk form di modal
    f.querySelectorAll('.money').forEach(el=> el.value = normalize(el.value, false));
    f.querySelectorAll('.int').forEach(el=> el.value = normalize(el.value, true));
    });

    /* ================== HELPER TABEL (kalau belum ada) ================== */
    function rp(n){ return 'Rp' + Number(n||0).toLocaleString('id-ID'); }
    function optionList(list, selectedId){
    return ['<option value="">— Choose —</option>'].concat(
        (list||[]).map(o=>`<option value="${o.id}" ${Number(selectedId)===Number(o.id)?'selected':''}>${o.name}</option>`)
    ).join('');
    }
    function rowHTML(p){
    return `
    <tr class="striped-row">
        <td><span class="fw-semibold text-dark">${p.product_code ?? '-'}</span></td>
        <td><div class="fw-semibold">${p.product_name ?? '-'}</div>
            <div class="text-muted small">Group: ${p.product_group ?? '-'}</div></td>
        <td>${p.category ?? '-'}</td>
        <td>${p.supplier ?? '-'}</td>
        <td class="text-end"><span class="badge bg-label-info">${Number(p.stock||0).toLocaleString('id-ID')}</span></td>
        <td class="text-end text-muted">${rp(p.purchase_price)}</td>
        <td class="text-end fw-semibold text-success">${rp(p.selling_price)}</td>
        <td>${p.package_type ?? '-'}</td>
        <td class="text-end">
        <div class="dropdown">
            <button class="btn btn-text-secondary p-0" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded fs-4"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="bx bx-show me-2"></i> Detail</a></li>
            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#mdlEdit-${p.id}"><i class="bx bx-edit-alt me-2"></i> Edit</button></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="${DD.updateUrlPattern.replace(/0$/, p.id)}" method="POST" onsubmit="return confirm('Yakin hapus produk ini?')">
                <input type="hidden" name="_token" value="${DD.csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <button class="dropdown-item text-danger" type="submit"><i class="bx bx-trash me-2"></i> Delete</button>
                </form>
            </li>
            </ul>
        </div>
        </td>
    </tr>`;
    }
    function modalHTML(p){
    const updateUrl = DD.updateUrlPattern.replace(/0$/, p.id);
    return `
    <div class="modal fade" id="mdlEdit-${p.id}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title fw-semibold">Edit Product #${p.id}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="${updateUrl}" method="POST">
            <input type="hidden" name="_token" value="${DD.csrf}">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="open" value="edit-${p.id}">
            <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-4">
                <label class="form-label">Product Code <span class="text-danger">*</span></label>
                <input type="text" name="product_code" class="form-control" value="${p.product_code??''}" required>
                </div>
                <div class="col-md-8">
                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="product_name" class="form-control" value="${p.product_name??''}" required>
                </div>
                <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">${optionList(DD.categories, p.category_id)}</select>
                </div>
                <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">${optionList(DD.suppliers, p.supplier_id)}</select>
                </div>
                <div class="col-md-4">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id" class="form-select">${optionList(DD.warehouses, p.warehouse_id)}</select>
                </div>
                <div class="col-md-4">
                <label class="form-label">Purchase Price</label>
                <input type="text" inputmode="numeric" name="purchase_price" class="form-control money" value="${p.purchase_price??0}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Selling Price</label>
                <input type="text" inputmode="numeric" name="selling_price" class="form-control money" value="${p.selling_price??0}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Stock</label>
                <input type="text" inputmode="numeric" name="stock" class="form-control int" value="${p.stock??0}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Package Type</label>
                <input type="text" name="package_type" class="form-control" value="${p.package_type??''}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Product Group</label>
                <input type="text" name="product_group" class="form-control" value="${p.product_group??''}">
                </div>
                <div class="col-md-4">
                <label class="form-label">Registration Number</label>
                <input type="text" name="registration_number" class="form-control" value="${p.registration_number??''}">
                </div>
            </div>
            </div>
            <div class="modal-footer">
            <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
        </div></div>
    </div>`;
    }

    /* ================== SEARCH AJAX (kalau sudah ada, biarkan) ================== */
    const searchUrl  = @json(route('products.search'));
    const searchBox  = document.getElementById('searchBox');
    const tbody      = document.getElementById('tbodyProducts');
    const modalZone  = document.getElementById('modalZone');

    function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }
    const doSearch = debounce(async (q)=>{
    try{
        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('q', q);
        const res = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
        const {data} = await res.json();

        if(!Array.isArray(data) || data.length===0){
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5">Tidak ada data.</td></tr>';
        modalZone.innerHTML = '';
        return;
        }

        tbody.innerHTML = data.map(rowHTML).join('');
        modalZone.innerHTML = data.map(modalHTML).join('');

        // bind masker ke modal hasil AJAX
        bindMask(modalZone);
    }catch(err){ console.error(err); }
    }, 250);

    searchBox?.addEventListener('input', e => doSearch(e.target.value.trim()));

    /* ================== INIT ================== */
    document.addEventListener('DOMContentLoaded', ()=>{
    bindMask(document);
    });

    // Auto reopen modal saat validasi gagal
    @if($errors->any())
    document.addEventListener('DOMContentLoaded', () => {
    const open = @json(old('open'));
    if(!open) return;
    const id = open==='add' ? '#mdlAdd' : '#mdlEdit-' + (open.split('edit-')[1]||'');
    const el = document.querySelector(id);
    if(el){ new bootstrap.Modal(el).show(); }
    });
    @endif
    </script>
    @endpush


