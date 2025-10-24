    @extends('layouts.home')
    @section('title','Warehouses - Animated Grid')

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
        <h4 class="fw-bold mb-1">Warehouses</h4>
        <small class="text-muted">Animated flip cards + SweetAlert CRUD + Pagination</small>
        </div>
        <div class="d-flex gap-2">
        <input id="searchBox" class="form-control" placeholder="Search warehouse..." style="min-width:260px">
        </div>
    </div>

    <div id="gridWarehouses" class="row g-4"></div>

    {{-- Pager (tetap di tengah, tinggi tetap) --}}
    <div class="pager-wrap d-flex justify-content-center align-items-center mt-3">
        <nav>
        <ul id="pager" class="pagination mb-0"></ul>
        </nav>
    </div>
    </div>

    {{-- deps --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.1/lib/anime.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    .swal2-container { z-index: 20000 !important; }
    .swal2-container.swal2-backdrop-show,

    :root{
        --glass-bg: rgba(255,255,255,.55);
        --glass-br: 16px;
        --glass-blur: 12px;
        --shadow: 0 10px 30px rgba(15, 23, 42, .12);
        --shadow-hover: 0 14px 36px rgba(15, 23, 42, .18);
        --muted: #6b7280;
    }
    html[data-color-scheme="dark"]{
        --glass-bg: rgba(20, 24, 38, .65);
        --shadow: 0 10px 30px rgba(0,0,0,.35);
        --shadow-hover: 0 16px 42px rgba(0,0,0,.45);
        --muted: #9ca3af;
    }

    .wh-card{ perspective: 1100px; }
    .wh-inner{
        position:relative; width:100%; height:240px; transform-style:preserve-3d;
        transition: transform .65s cubic-bezier(.22,.61,.36,1), box-shadow .25s ease, translate .25s ease;
    }
    .wh-inner:hover{ translate: 0 -4px; box-shadow: var(--shadow-hover); }
    .wh-inner.flipped{ transform: rotateY(180deg); }

    .wh-face{
        position:absolute; inset:0; backface-visibility:hidden; border-radius: var(--glass-br);
        background: var(--glass-bg); backdrop-filter: blur(var(--glass-blur)); -webkit-backdrop-filter: blur(var(--glass-blur));
        box-shadow: var(--shadow); overflow:hidden; border: 1px solid rgba(100,116,139,.18);
    }
    .wh-front{ display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:1.25rem; }
    .wh-front .meta{ font-size:.8rem; color: var(--muted); letter-spacing:.02em; }
    .wh-front h5{ margin:.25rem 0 .35rem; font-weight:800; }
    .wh-front .sub{ color: var(--muted); font-size:.9rem; }

    .wh-back{ transform: rotateY(180deg); padding:1rem; }
    .wh-back .form-control{ height:36px; font-size:.9rem; }

    #gridWarehouses .col-sm-6.col-lg-4{ display:flex; }
    #gridWarehouses .wh-card{ width:100%; }

    .btn-3d{
        border-radius:50%; width:56px; height:56px;
        background: radial-gradient(120px 120px at 30% 30%, #34d399, #16a34a);
        color:#fff; border:none; box-shadow: 0 10px 24px rgba(22,163,74,.35); transition: transform .2s ease, box-shadow .2s ease;
    }
    .btn-3d:hover{ transform: translateY(-3px); box-shadow: 0 14px 34px rgba(22,163,74,.45); }

    /* Pager fixed height + center */
    .pager-wrap{ min-height:56px; } /* biar tempat pager nggak geser2 */
    .pagination .page-link { cursor:pointer; }
    </style>

    <script>
    $(function(){
    const baseUrl = @json(url('warehouses'));
    const grid = $('#gridWarehouses');
    const pager = $('#pager');

    // SweetAlert helpers (center)
    const Alert = Swal.mixin({ buttonsStyling:false, customClass:{ confirmButton:'btn btn-primary', cancelButton:'btn btn-outline-secondary ms-2' } });
    const confirmBox = (title, text, confirm='Yes') => Alert.fire({ icon:'question', title, text, showCancelButton:true, confirmButtonText:confirm });
    const successBox = (t='Success', m='') => Alert.fire({ icon:'success', title:t, text:m, confirmButtonText:'OK' });
    const errorBox = (t='Error', m='') => Alert.fire({ icon:'error', title:t, html:m, confirmButtonText:'OK' });
    const warnBox   = (t='Validation', m='') => Alert.fire({ icon:'warning', title:t, html:m, confirmButtonText:'OK' });

    // ===== STATE =====
    let warehouses = @json(
        $warehouses instanceof \Illuminate\Pagination\AbstractPaginator ? $warehouses->items() : $warehouses
    ) || [];

    const pageSize = 9;      // 9 kotak per halaman
    let currentPage = 1;     // 1-based
    let keyword = '';        // pencarian aktif

    // Derived list (filtering by keyword)
    function getActiveList(){
        if(!keyword) return warehouses;
        const q = keyword.toLowerCase();
        return warehouses.filter(w => [w.warehouse_code,w.warehouse_name,w.address,w.note].join(' ').toLowerCase().includes(q));
    }
    function getTotalPages(){
        return Math.max(1, Math.ceil(getActiveList().length / pageSize));
    }
    function clampPage(p){
        const tp = getTotalPages();
        return Math.min(Math.max(1, p), tp);
    }

    // ===== UI BUILDERS =====
    function cardHTML(w, no){
        return `
        <div class="col-sm-6 col-lg-4">
            <div class="wh-card" data-id="${w.id}">
            <div class="wh-inner">
                <div class="wh-face wh-front">
                <div class="small text-muted">${no} • ${w.warehouse_code || '-'}</div>
                <h5 class="fw-bold mb-1">${w.warehouse_name}</h5>
                <div class="small-dim">${w.address || '-'}</div>
                <div class="small-dim mt-1">${w.note || ''}</div>
                <div class="mt-3">
                    <button class="btn btn-outline-dark btn-sm js-edit">Edit</button>
                    <button class="btn btn-outline-danger btn-sm js-del">Del</button>
                </div>
                </div>
                <div class="wh-face wh-back">
                <form class="edit-form h-100 d-flex flex-column justify-content-between">
                    <div class="mb-2">
                    <input class="form-control mb-1 f-code"  value="${w.warehouse_code || ''}" placeholder="Code">
                    <input class="form-control mb-1 f-name"  value="${w.warehouse_name || ''}" placeholder="Name">
                    <input class="form-control mb-1 f-addr"  value="${w.address || ''}" placeholder="Address">
                    <input class="form-control mb-1 f-note"  value="${w.note || ''}" placeholder="Note">
                    </div>
                    <div class="d-flex gap-2">
                    <button class="btn btn-primary w-100 btn-save">Save</button>
                    <button type="button" class="btn btn-outline-secondary w-100 btn-cancel">Back</button>
                    </div>
                </form>
                </div>
            </div>
            </div>
        </div>`;
    }

    function renderPage(){
        grid.empty();

        const list = getActiveList();
        const total = list.length;

        const start = (currentPage - 1) * pageSize;
        const end   = Math.min(start + pageSize, total);
        const slice = list.slice(start, end);

        // nomor urut tampil = offset + index di halaman
        slice.forEach((w, i) => grid.append(cardHTML(w, start + i + 1)));

        // Kartu Add (selalu di akhir halaman yang tampil)
        grid.append(`
        <div class="col-sm-6 col-lg-4">
            <div class="wh-card add-card" id="cardAdd">
            <div class="wh-inner d-flex align-items-center justify-content-center">
                <button class="btn-3d"><i class="bx bx-plus fs-3"></i></button>
            </div>
            </div>
        </div>
        `);

        renderPager();
    }

    function renderPager(){
        const tp = getTotalPages();
        pager.empty();

        // Previous
        pager.append(`
        <li class="page-item ${currentPage===1?'disabled':''}">
            <a class="page-link" data-goto="${currentPage-1}">«</a>
        </li>
        `);

        // Numbered buttons (semua halaman, karena biasanya nggak terlalu banyak)
        for(let p=1;p<=tp;p++){
        pager.append(`
            <li class="page-item ${p===currentPage?'active':''}">
            <a class="page-link" data-goto="${p}">${p}</a>
            </li>
        `);
        }

        // Next
        pager.append(`
        <li class="page-item ${currentPage===tp?'disabled':''}">
            <a class="page-link" data-goto="${currentPage+1}">»</a>
        </li>
        `);
    }

    function gotoPage(p){
        currentPage = clampPage(p);
        renderPage();
    }

    // ===== INIT =====
    renderPage();

    // ===== Search =====
    $('#searchBox').on('input', function(){
        keyword = this.value || '';
        // setelah filter berubah, balik ke page 1 biar predictable
        currentPage = 1;
        renderPage();
    });

    // Pager clicks
    pager.on('click','.page-link', function(){
        const p = parseInt(this.dataset.goto,10);
        if(!isNaN(p)) gotoPage(p);
    });

    // Flip edit
    grid.on('click','.js-edit', function(e){
        e.stopPropagation();
        const inner = $(this).closest('.wh-inner');
        inner.addClass('flipped');
        anime({ targets: inner[0], rotateY: [0,180], duration:600, easing:'easeInOutQuart' });
    });
    grid.on('click','.btn-cancel', function(e){
        e.preventDefault();
        const inner = $(this).closest('.wh-inner');
        anime({ targets: inner[0], rotateY: [180,0], duration:600, easing:'easeInOutQuart', complete:()=>inner.removeClass('flipped') });
    });

    // DELETE
    grid.on('click','.js-del', async function(e){
        e.stopPropagation();
        const id = $(this).closest('.wh-card').data('id');
        const wh = warehouses.find(x => x.id == id);

        const ok = await confirmBox('Delete?', `Delete ${wh?.warehouse_name || 'warehouse'}?`, 'Delete');
        if(!ok.isConfirmed) return;

        try{
        const res = await fetch(`${baseUrl}/${id}`, { method:'DELETE', headers:{ 'Accept':'application/json' } });
        if(!res.ok){
            const txt = await res.text().catch(()=> '');
            throw new Error(`HTTP ${res.status} ${txt}`);
        }
        // Hapus dari master
        warehouses = warehouses.filter(x => x.id != id);

        // Jika halaman jadi kosong, mundurkan halaman
        const tpBefore = getTotalPages();
        currentPage = clampPage(currentPage);
        const tpAfter = getTotalPages();
        if(currentPage > tpAfter) currentPage = tpAfter;

        renderPage();
        await successBox('Deleted', 'Warehouse has been removed.');
        }catch(err){
        await errorBox('Error', err.message);
        }
    });

    // SAVE (edit)
    grid.on('submit','.edit-form', async function(e){
        e.preventDefault();
        const card = $(this).closest('.wh-card');
        const id   = card.data('id');

        const payload = {
        warehouse_code: $(this).find('.f-code').val().trim(),
        warehouse_name: $(this).find('.f-name').val().trim(),
        address:        $(this).find('.f-addr').val().trim(),
        note:           $(this).find('.f-note').val().trim(),
        };
        if(!payload.warehouse_code || !payload.warehouse_name){
        return warnBox('Validation','Code & Name are required');
        }

        try{
        const res = await fetch(`${baseUrl}/${id}`, {
            method:'PUT',
            headers: { 'Accept':'application/json', 'Content-Type':'application/json' },
            body: JSON.stringify(payload)
        });

        if(res.status===422){
            const j = await res.json();
            const html = Object.values(j.errors || {}).flat().join('<br>');
            return warnBox('Validation', html);
        }
        if(!res.ok){
            const txt = await res.text().catch(()=> '');
            throw new Error(`HTTP ${res.status} ${txt}`);
        }

        const j = await res.json();
        // replace di master
        warehouses = warehouses.map(x => x.id==id ? j.row : x);

        // tetap di halaman sekarang
        renderPage();
        await successBox('Saved', 'Warehouse has been updated.');
        }catch(err){
        await errorBox('Error', err.message);
        }
    });

    // ADD (new data ke belakang & auto ke halaman terakhir)
    grid.on('click','#cardAdd', function(){
        Alert.fire({
        title:'Add Warehouse',
        html:`
            <input id="sw_code" class="form-control mb-2" placeholder="Code">
            <input id="sw_name" class="form-control mb-2" placeholder="Name">
            <input id="sw_addr" class="form-control mb-2" placeholder="Address">
            <input id="sw_note" class="form-control" placeholder="Note">
        `,
        showCancelButton: true,
        confirmButtonText:'Save',
        preConfirm:()=>{
            const code = $('#sw_code').val().trim();
            const name = $('#sw_name').val().trim();
            if(!code || !name){
            Alert.showValidationMessage('Code & Name required');
            return false;
            }
            return {
            warehouse_code: code,
            warehouse_name: name,
            address: $('#sw_addr').val().trim(),
            note: $('#sw_note').val().trim(),
            };
        }
        }).then(async r=>{
        if(!r.isConfirmed) return;
        try{
            const res = await fetch(baseUrl, {
            method:'POST',
            headers:{ 'Accept':'application/json', 'Content-Type':'application/json' },
            body: JSON.stringify(r.value)
            });
            if(res.status===422){
            const j=await res.json();
            const html=Object.values(j.errors||{}).flat().join('<br>');
            return warnBox('Validation', html);
            }
            if(!res.ok){
            const txt = await res.text().catch(()=> '');
            throw new Error(`HTTP ${res.status} ${txt}`);
            }
            const j = await res.json();

            // === KUNCI: push ke belakang ===
            warehouses.push(j.row);

            // kalau ada keyword aktif, bersihin dulu biar kelihatan item baru
            if(keyword){ keyword=''; $('#searchBox').val(''); }

            // lompat ke halaman terakhir dan render
            currentPage = Math.ceil(warehouses.length / pageSize);
            renderPage();

            await successBox('Added', 'New warehouse has been created at the end.');
        }catch(err){
            await errorBox('Error', err.message);
        }
        });
    });

    console.log('[Warehouses Grid] pagination=9, centered pager, append-on-add ready');
    });
    </script>
    @endsection
