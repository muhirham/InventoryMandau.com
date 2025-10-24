    @extends('layouts.home')

    @section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
        <h4 class="mb-1 fw-bold">Categories</h4>
        <small class="text-muted">Manage your product categories here.</small>
        </div>
    </div>

    {{-- Create form (inline, no modal) --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
        <form id="createCategoryForm" class="row g-2 align-items-end" method="POST" action="{{ route('categories.store') }}">
            @csrf
            <div class="col-md-4">
            <label class="form-label mb-0">Category Name <span class="text-danger">*</span></label>
            <input name="category_name" class="form-control" required>
            </div>
            <div class="col-md-6">
            <label class="form-label mb-0">Description</label>
            <input name="description" class="form-control">
            </div>
            <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="bx bx-save"></i> Save</button>
            </div>
        </form>
        </div>
    </div>

    {{-- Table + Right edit panel layout --}}
    <div class="row">
        <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body pb-0">
            <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
                <input id="dt-search" class="form-control" placeholder="Type to search...">
                <select id="dt-length" class="form-select" style="width:120px">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                </select>
            </div>
            </div>
            <div class="table-responsive">
            <table id="dtCategories" class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:80px">ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th style="width:160px">Updated</th>
                    <th style="width:120px" class="text-end">Actions</th>
                </tr>
                </thead>
            </table>
            </div>
        </div>
        </div>

        {{-- Right side edit panel (no modal/offcanvas) --}}
        <div class="col-lg-4">
        @php $editId = (int) request('edit'); $edit = $editId ? \App\Models\Category::find($editId) : null; @endphp
        <div class="card border-0 shadow-sm sticky-top" style="top:84px">
            <div class="card-header bg-light fw-semibold">
            <i class="bx bx-edit-alt"></i> {{ $edit ? "Edit Category #{$edit->id}" : 'Tips' }}
            </div>
            <div class="card-body">
            @if($edit)
            <form id="editCategoryForm" method="POST" action="{{ route('categories.update', $edit) }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-12">
                <label class="form-label">Category Name</label>
                <input name="category_name" value="{{ old('category_name', $edit->category_name) }}" class="form-control" required>
                </div>
                <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-control">{{ old('description', $edit->description) }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bx bx-save"></i> Save</button>
                <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}"><i class="bx bx-x-circle"></i> Cancel</a>
                </div>
            </form>
            @else
                <div class="text-muted small">
                Klik tombol <span class="badge bg-label-primary">Edit</span> pada baris untuk membuka panel ini.
                </div>
            @endif
            </div>
        </div>
        </div>
    </div>

    </div>
    @endsection

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.5/r-3.0.3/datatables.min.css">
    <style>

    .swal2-container {
        z-index: 20000 !important;   /* > z-index navbar (±1030) & modal (1055–1065) */
    }

    /* Dark mode skin (mengatasi bentrok) */
    html[data-color-scheme="dark"] .dataTable,
    html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_info,
    html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: var(--text, #e5e7eb) !important;
    }
    html[data-color-scheme="dark"] .table thead th {
        background: var(--bg-elev-1, #171b24) !important;
        border-color: var(--border, #2a3244) !important;
    }
    html[data-color-scheme="dark"] table.dataTable>tbody>tr>td,
    html[data-color-scheme="dark"] table.dataTable>tbody>tr>th,
    html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
        background: var(--bg-card, #0f1420) !important;
        border-color: var(--border, #2a3244) !important;
    }
    html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--primary, #6366f1) !important; color: #fff !important; border-color: var(--primary, #6366f1)!important;
    }
    html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--bg-hover, #1f2430) !important;
    }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.5/r-3.0.3/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (() => {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // ===== DataTable =====
    const DT = $('#dtCategories').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: "{{ route('categories.datatable') }}", type: 'GET' },
        order: [[0,'asc']],
        columns: [
        { data: 'id', name: 'id', width: 80, className:'align-middle' },
        { data: 'category_name', name: 'category_name', className:'fw-semibold align-middle' },
        { data: 'description', name: 'description', className:'text-muted align-middle' },
        { data: 'updated_at', name: 'updated_at', width: 160, className:'align-middle' },
        { data: 'actions', orderable:false, searchable:false, width: 120, className:'text-end align-middle' },
        ],
        responsive: true,
        stateSave: true,
        searchDelay: 250,
        lengthMenu: [[10,25,50],[10,25,50]],
        dom: 't<"d-flex justify-content-between align-items-center p-2"ip>',
        language: {
        processing: 'Loading...', search: '', searchPlaceholder: '',
        paginate: { previous: 'Prev', next: 'Next' }
        }
    });

    // External search & length
    document.getElementById('dt-search').addEventListener('input', e => DT.search(e.target.value).draw());
    document.getElementById('dt-length').addEventListener('change', e => DT.page.len(+e.target.value).draw());

    const toast = (msg, icon='success') => Swal.fire({icon, title: msg, timer: 1600, showConfirmButton:false});

    // ===== CREATE (AJAX + SweetAlert) =====
    const createForm = document.getElementById('createCategoryForm');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await fetch(createForm.action, { method:'POST', headers:{'X-CSRF-TOKEN': CSRF}, body:new FormData(createForm) });
            if (res.status === 422) {
            const j = await res.json();
            const html = Object.values(j.errors||{}).flat().join('<br>');
            return Swal.fire({icon:'error', title:'Validasi gagal', html});
            }
            // JSON mode (controller ajax)
            let j; try { j = await res.json(); } catch { j = null; }
            if (j && j.success) {
            toast(j.success);
            createForm.reset();
            DT.ajax.reload(null,false);
            } else {
            // fallback kalau balasan redirect HTML
            toast('Kategori berhasil ditambahkan');
            createForm.reset();
            DT.ajax.reload(null,false);
            }
        } catch {
            Swal.fire('Error','Gagal menambahkan kategori','error');
        }
        });
    }

    // ===== EDIT (AJAX + SweetAlert) =====
    const editForm = document.getElementById('editCategoryForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e)=>{
        e.preventDefault();
        try {
            const res = await fetch(editForm.action, {
            method:'POST',
            headers:{'X-CSRF-TOKEN': CSRF, 'X-HTTP-Method-Override':'PUT'},
            body:new FormData(editForm)
            });
            if (res.status === 422) {
            const j = await res.json();
            const html = Object.values(j.errors||{}).flat().join('<br>');
            return Swal.fire({icon:'error', title:'Validasi gagal', html});
            }
            let j; try { j = await res.json(); } catch { j = null; }
            if (j && j.success) {
            toast(j.success);
            } else {
            toast('Kategori diperbarui');
            }
            DT.ajax.reload(null,false);
            setTimeout(()=> location.href="{{ route('categories.index') }}", 800);
        } catch {
            Swal.fire('Error','Gagal update kategori','error');
        }
        });
    }

    // ===== DELETE (SweetAlert confirm + AJAX) =====
    window.delCategory = function(id){
        Swal.fire({
        title:'Yakin hapus?', text:'Tindakan ini tidak bisa dibatalkan.',
        icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'
        }).then(async r=>{
        if (!r.isConfirmed) return;
        const url = "{{ route('categories.destroy', ':id') }}".replace(':id', id);
        const res = await fetch(url, { method:'POST', headers:{'X-CSRF-TOKEN': CSRF, 'X-HTTP-Method-Override':'DELETE'} });
        let j; try { j = await res.json(); } catch { j = null; }
        toast(j?.success || 'Kategori dihapus');
        DT.ajax.reload(null,false);
        });
    };
    })();
    </script>
    @endpush
