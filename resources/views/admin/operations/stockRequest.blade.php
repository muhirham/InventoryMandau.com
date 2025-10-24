        @extends('layouts.home')
        @section('title','Restock Approval')

        @section('content')
        <div class="container-fluid flex-grow-1 container-p-y px-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
            <h4 class="fw-bold mb-1">Restock Requests (Admin)</h4>
            <small class="text-muted">Approve / Reject incoming requests</small>
            </div>
            <button id="btnReload" class="btn btn-outline-secondary">
            <i class="bx bx-refresh"></i> Reload
            </button>
        </div>

        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                <label class="form-label">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="" selected>All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                </div>
                <div class="col-md-3">
                <label class="form-label">Supplier</label>
                <select id="filterSupplier" class="form-select">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->label }}</option>
                    @endforeach
                </select>
                </div>
                <div class="col-md-3">
                <label class="form-label">Warehouse</label>
                <select id="filterWarehouse" class="form-select">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $w)
                    <option value="{{ $w->id }}">{{ $w->label }}</option>
                    @endforeach
                </select>
                </div>
                <div class="col-md-3">
                <label class="form-label">Product</label>
                <select id="filterProduct" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->label }}</option>
                    @endforeach
                </select>
                </div>
                <div class="col-md-2">
                <label class="form-label">Date from</label>
                <input id="dateFrom" type="date" class="form-control">
                </div>
                <div class="col-md-2">
                <label class="form-label">Date to</label>
                <input id="dateTo" type="date" class="form-control">
                </div>
                <div class="col-md-3">
                <label class="form-label">Search</label>
                <input id="searchBox" class="form-control" placeholder="Product/Supplier/Warehouse...">
                </div>
                <div class="col-md-2">
                <label class="form-label">Per page</label>
                <select id="perPage" class="form-select">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                </div>
            </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tbl">
                <thead class="table-light">
                <tr>
                    <th>ID</th><th>Date</th><th>Product</th><th>Supplier</th><th>Warehouse</th>
                    <th class="text-end">Qty</th><th class="text-end">Total Cost</th><th>Status</th><th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
            <small id="pageInfo" class="text-muted">—</small>
            <nav><ul id="pagination" class="pagination mb-0"></ul></nav>
            </div>
        </div>
        </div>

        <style>
        .swal2-container{ z-index:20000 !important; }
        .layout-page .content-wrapper { width:100% !important; }
        .container-xxl, .content-wrapper > .container-xxl { max-width:100% !important; }
        .card .table-responsive { overflow-x:auto; }
        #tbl { width:100%; }
        @media (max-width:1200px){ #tbl{ min-width:1100px; } }
        </style>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
        const ENDPOINT     = "{{ route('restocks.json') }}";
        const APPROVE_URL  = id => "{{ url('restocks') }}/" + id + "/approve";
        const REJECT_URL   = id => "{{ url('restocks') }}/" + id + "/reject";

        let state = {
        page:1, per_page:10, status:'', supplier_id:'',
        warehouse_id:'', product_id:'', date_from:'',
        date_to:'', search:''
        };

        function rupiah(n){ return (Number(n||0)).toLocaleString('id-ID'); }
        function escHtml(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
        function escAttr(v){ return encodeURIComponent(v ?? ''); }

        async function fetchList(){
        const params = new URLSearchParams({ ...state, _ts: Date.now() });
        const res = await fetch(ENDPOINT + '?' + params.toString(), {
            headers: { Accept: 'application/json' }, cache: 'no-store'
        });
        if(!res.ok){
            const t = await res.text();
            Swal.fire({
            icon: 'error',
            title: 'Error ' + res.status,
            html: '<pre style="text-align:left">' + t.replace(/[<>&]/g, s=>({'<':'&lt;','>':'&gt;','&':'&amp;'}[s])) + '</pre>',
            width: 800, position: 'center'
            });
            throw new Error('HTTP ' + res.status);
        }
        return res.json();
        }

        function renderRows(rows){
        const tbody = $('#tbl tbody'); tbody.empty();
        rows.forEach(r=>{
            const badge = r.status==='approved' ? '<span class="badge bg-success">Approved</span>'
                        : r.status==='rejected' ? '<span class="badge bg-danger">Rejected</span>'
                        : '<span class="badge bg-warning text-dark">Pending</span>';

            const actions = (r.status==='pending') ? `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-success btn-approve"
                data-id="${r.id}"
                data-product="${escAttr(r.product_name)}"
                data-warehouse="${escAttr(r.warehouse_name)}"
                data-qty="${r.quantity_requested ?? 0}">
                Approve
                </button>
                <button class="btn btn-outline-danger btn-reject" data-id="${r.id}">Reject</button>
            </div>` : '';

            tbody.append(`
            <tr>
                <td>${r.id}</td>
                <td>${r.request_date ?? ''}</td>
                <td>${r.product_name ?? ''}</td>
                <td>${r.supplier_name ?? ''}</td>
                <td>${r.warehouse_name ?? ''}</td>
                <td class="text-end">${Number(r.quantity_requested||0).toLocaleString('id-ID')}</td>
                <td class="text-end">Rp${Number(r.total_cost||0).toLocaleString('id-ID')}</td>
                <td>${badge}</td>
                <td>${r.description ?? ''}</td>
                <td class="text-end">${actions}</td>
            </tr>
            `);
        });
        }

        function renderPagination(pg){
        const ul = $('#pagination'); ul.empty();
        const { page, last_page:last, per_page:per, total } = pg;
        const from = total ? ((page-1)*per + 1) : 0, to = Math.min(page*per, total);
        $('#pageInfo').text(`Showing ${from}-${to} of ${total}`);
        function li(txt,p,dis=false,act=false){
            ul.append(`<li class="page-item ${dis?'disabled':''} ${act?'active':''}">
            <a class="page-link" href="#" data-page="${p}">${txt}</a></li>`);
        }
        li('«', page-1, page<=1);
        for(let p=Math.max(1,page-3); p<=Math.min(last,page+3); p++) li(p,p,false,p===page);
        li('»', page+1, page>=last);
        $('#pagination .page-link').off('click').on('click',e=>{
            e.preventDefault();
            const p = +$(e.target).data('page');
            if(p>=1 && p<=last && p!==page){ state.page=p; load(); }
        });
        }

        async function load(){
        try{
            const j = await fetchList();
            renderRows(j.data);
            renderPagination(j.pagination);
        }catch(e){}
        }

        function apply(){
        state.page=1;
        state.status=$('#filterStatus').val();
        state.supplier_id=$('#filterSupplier').val();
        state.warehouse_id=$('#filterWarehouse').val();
        state.product_id=$('#filterProduct').val();
        state.date_from=$('#dateFrom').val();
        state.date_to=$('#dateTo').val();
        state.per_page=+($('#perPage').val()||10);
        state.search=$('#searchBox').val().trim();
        load();
        }

        $('#btnReload').on('click', load);
        $('#filterStatus,#filterSupplier,#filterWarehouse,#filterProduct,#dateFrom,#dateTo,#perPage').on('change', apply);
        $('#searchBox').on('keyup', e=>{ if(e.key==='Enter') apply(); });


        // === DOUBLE CONFIRM APPROVE ===
        $(document).on('click', '.btn-approve', async function(){
        const $btn = $(this);
        const id   = $btn.data('id');
        const info = {
            product: decodeURIComponent(($btn.data('product')||'').toString()),
            warehouse: decodeURIComponent(($btn.data('warehouse')||'').toString()),
            qty: Number($btn.data('qty') || 0)
        };

        // step 1
        const step1 = await Swal.fire({
            icon: 'question',
            title: 'Approve this request?',
            html: `
            <div class="text-start" style="line-height:1.5">
                <div><b>Product</b>: ${escHtml(info.product)}</div>
                <div><b>Warehouse</b>: ${escHtml(info.warehouse)}</div>
                <div><b>Qty</b>: ${info.qty.toLocaleString('id-ID')}</div>
            </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            position: 'center'
        });
        if (!step1.isConfirmed) return;

        // step 2 final confirm
        const step2 = await Swal.fire({
            icon: 'warning',
            title: 'Final confirmation',
            html: `
            <div class="text-start" style="line-height:1.5">
                This will <b>add ${info.qty.toLocaleString('id-ID')}</b> to the stock of
                <b>${escHtml(info.product)}</b> in <b>${escHtml(info.warehouse)}</b>.<br>
                <u>This action cannot be undone.</u>
            </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Yes, confirm approve',
            cancelButtonText: 'Back',
            position: 'center'
        });
        if (!step2.isConfirmed) return;

        Swal.fire({ title:'Processing...', allowOutsideClick:false, didOpen:()=>Swal.showLoading(), position:'center' });

        try{
            const res = await fetch(APPROVE_URL(id), {
            method:'POST',
            headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' }
            });
            const j = await res.json().catch(()=>({}));
            if(res.ok && j.ok){
            Swal.fire({ icon:'success', title:'Approved', html:`Stock updated for <b>${escHtml(info.product)}</b>.`, timer:1300, showConfirmButton:false, position:'center' });
            load();
            }else{
            Swal.fire({ icon:'error', title:'Approve failed', text:j.message||'Server error', position:'center' });
            }
        }catch(err){
            Swal.fire({ icon:'error', title:'Network error', text:String(err), position:'center' });
        }
        });


        // === REJECT REQUEST ===
        $(document).on('click', '.btn-reject', async function(){
        const id = $(this).data('id');
        const { value: reason } = await Swal.fire({
            title: 'Reject reason',
            input: 'text',
            inputPlaceholder: 'Optional',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            position: 'center'
        });

        if(reason !== undefined){
            Swal.fire({ title:'Processing...', allowOutsideClick:false, didOpen:()=>Swal.showLoading(), position:'center' });
            try{
            const res = await fetch(REJECT_URL(id), {
                method:'POST',
                headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' },
                body: JSON.stringify({ reason })
            });
            const j = await res.json().catch(()=>({}));
            if(res.ok && j.ok){
                Swal.fire({ icon:'success', title:'Rejected', timer:1000, showConfirmButton:false, position:'center' });
                load();
            }else{
                Swal.fire({ icon:'error', title:'Reject failed', text:j.message||'Server error', position:'center' });
            }
            }catch(err){
            Swal.fire({ icon:'error', title:'Network error', text:String(err), position:'center' });
            }
        }
        });

        load();
        </script>
        @endsection
