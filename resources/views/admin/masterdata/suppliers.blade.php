    @extends('layouts.home')

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

    {{-- Toolbar --}}
    <div class="card mb-3">
        <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
            <label class="text-muted">Show</label>
            <select id="pageLength" class="form-select" style="width:90px">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            </div>

            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
            <input id="searchSupplier" type="text" class="form-control" placeholder="Search supplier..." style="max-width:260px">

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#glassAddSupplier">
                <i class="bx bx-plus"></i> Add Supplier
            </button>
            </div>
        </div>
        </div>
    </div>

    {{-- ADD – Glass Modal --}}
    <div class="modal fade" id="glassAddSupplier" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="background:rgba(17,22,28,.6);backdrop-filter:blur(14px)">
            <div class="modal-header border-0">
            <h5 class="modal-title text-white">Add Supplier</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formAddSupplier" class="modal-body text-white">
            <div class="row g-2">
                <div class="col-md-6">
                <label class="form-label text-white">Company <span class="text-danger">*</span></label>
                <input name="company_name" class="form-control bg-transparent text-white border-secondary" required>
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Contact</label>
                <input name="contact_person" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Phone</label>
                <input name="phone_number" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Address</label>
                <input name="address" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Bank</label>
                <input name="bank_name" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Account</label>
                <input name="bank_account" class="form-control bg-transparent text-white border-secondary">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-light text-dark">Submit</button>
            </div>
            </form>
        </div>
        </div>
    </div>

    {{-- EDIT – Glass Modal --}}
    <div class="modal fade" id="glassEditSupplier" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="background:rgba(17,22,28,.6);backdrop-filter:blur(14px)">
            <div class="modal-header border-0">
            <h5 class="modal-title text-white">Edit Supplier</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formEditSupplier" class="modal-body text-white">
            <input type="hidden" id="edit_id">
            <div class="row g-2">
                <div class="col-md-6">
                <label class="form-label text-white">Company <span class="text-danger">*</span></label>
                <input id="edit_company_name" name="company_name" class="form-control bg-transparent text-white border-secondary" required>
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Contact</label>
                <input id="edit_contact_person" name="contact_person" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Phone</label>
                <input id="edit_phone_number" name="phone_number" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Address</label>
                <input id="edit_address" name="address" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Bank</label>
                <input id="edit_bank_name" name="bank_name" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col-md-6">
                <label class="form-label text-white">Account</label>
                <input id="edit_bank_account" name="bank_account" class="form-control bg-transparent text-white border-secondary">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-light text-dark" type="submit">Save</button>
            </div>
            </form>
        </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
        <table id="tblSuppliers" class="table table-hover align-middle mb-0">
            <thead>
            <tr>
            <th>ID</th>
            <th>COMPANY</th>
            <th>ADDRESS</th>
            <th>CONTACT</th>
            <th>PHONE</th>
            <th>BANK</th>
            <th>ACCOUNT</th>
            <th style="width:120px">ACTIONS</th>
            </tr>
            </thead>
            <tbody>
            @forelse($suppliers as $s)
            <tr data-id="{{ $s->id }}">
                <td>{{ $s->id }}</td>
                <td>{{ $s->company_name }}</td>
                <td>{{ $s->address }}</td>
                <td>{{ $s->contact_person }}</td>
                <td>{{ $s->phone_number }}</td>
                <td>{{ $s->bank_name }}</td>
                <td>{{ $s->bank_account }}</td>
                <td>
                <div class="d-flex gap-1">
                    <a href="#" class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                    data-id="{{ $s->id }}"
                    data-company="{{ $s->company_name }}"
                    data-address="{{ $s->address }}"
                    data-contact="{{ $s->contact_person }}"
                    data-phone="{{ $s->phone_number }}"
                    data-bank="{{ $s->bank_name }}"
                    data-account="{{ $s->bank_account }}"
                    title="Edit">
                    <i class="bx bx-edit-alt"></i>
                    </a>
                    <a href="#" class="btn btn-sm btn-icon btn-outline-danger js-del"
                    data-id="{{ $s->id }}"
                    data-company="{{ $s->company_name }}"
                    title="Delete">
                    <i class="bx bx-trash"></i>
                    </a>
                </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted">No data</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    </div>
    @endsection

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
    .swal2-container{ z-index:20000 !important; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(function () {
    const baseUrl = @json(url('suppliers')); // /suppliers

    // DataTable (client-side)
    const table = $('#tblSuppliers').DataTable({
        order: [[0, 'asc']],
        columnDefs: [
        { targets: 7, orderable: false },
        { targets: 0, type: 'num' }
        ],
        pageLength: 10,
        lengthChange: false,
        searching: true,
        info: true,
        dom: 't<"d-flex justify-content-between align-items-center p-3 pt-2"ip>'
    });

    // search + length
    $('#searchSupplier').on('keyup', function(){ table.search(this.value).draw(); });
    $('#pageLength').on('change', function(){ table.page.len(parseInt(this.value,10)).draw(); });

    const toast = (msg, icon='success') => Swal.fire({icon, title: msg, timer: 1600, showConfirmButton:false});

    // ========== CREATE (no token) ==========
    const addModalEl = document.getElementById('glassAddSupplier');
    const addModal   = addModalEl ? new bootstrap.Modal(addModalEl) : null;
    const addForm    = document.getElementById('formAddSupplier');

    addForm?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const fd = new FormData(addForm);
        try{
        const res = await fetch(baseUrl, { method:'POST', body: fd });
        if(res.status === 422){
            const j = await res.json();
            const html = Object.values(j.errors||{}).flat().map(s=>'• '+s).join('<br>');
            return Swal.fire({icon:'error', title:'Validation', html});
        }
        if(!res.ok){ throw new Error(await res.text() || 'Failed'); }
        const j = await res.json();
        // add row to table (top)
        const r = j.row;
        table.row.add([
            r.id, r.company_name, r.address??'', r.contact_person??'', r.phone_number??'', r.bank_name??'', r.bank_account??'',
            `<div class="d-flex gap-1">
            <a href="#" class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                data-id="${r.id}" data-company="${r.company_name}" data-address="${r.address??''}"
                data-contact="${r.contact_person??''}" data-phone="${r.phone_number??''}"
                data-bank="${r.bank_name??''}" data-account="${r.bank_account??''}">
                <i class="bx bx-edit-alt"></i></a>
            <a href="#" class="btn btn-sm btn-icon btn-outline-danger js-del"
                data-id="${r.id}" data-company="${r.company_name}">
                <i class="bx bx-trash"></i></a>
            </div>`
        ]).draw(false);
        addForm.reset();
        addModal?.hide();
        toast('Created');
        }catch(err){
        Swal.fire({icon:'error', title:'Failed', text: err.message || 'Cannot create data'});
        }
    });

    // ========== OPEN EDIT ==========
    const editModalEl = document.getElementById('glassEditSupplier');
    const editModal   = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    const editForm    = document.getElementById('formEditSupplier');

    $('#tblSuppliers').on('click', '.js-edit', function(e){
        e.preventDefault();
        const d = this.dataset;
        $('#edit_id').val(d.id);
        $('#edit_company_name').val(d.company);
        $('#edit_address').val(d.address||'');
        $('#edit_contact_person').val(d.contact||'');
        $('#edit_phone_number').val(d.phone||'');
        $('#edit_bank_name').val(d.bank||'');
        $('#edit_bank_account').val(d.account||'');
        editModal?.show();
    });

    // ========== UPDATE (no token) ==========
    editForm?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const id = $('#edit_id').val();
        const fd = new FormData(editForm);
        try{
        const res = await fetch(`${baseUrl}/${id}`, { method:'PUT', body: fd });
        if(res.status === 422){
            const j = await res.json();
            const html = Object.values(j.errors||{}).flat().map(s=>'• '+s).join('<br>');
            return Swal.fire({icon:'error', title:'Validation', html});
        }
        if(!res.ok){ throw new Error(await res.text() || 'Failed'); }
        const j = await res.json();
        const r = j.row;

        // update row in table
        const $tr = $(`#tblSuppliers tbody tr[data-id="${r.id}"]`);
        if($tr.length){
            const row = table.row($tr);
            row.data([
            r.id, r.company_name, r.address??'', r.contact_person??'', r.phone_number??'', r.bank_name??'', r.bank_account??'',
            `<div class="d-flex gap-1">
                <a href="#" class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                    data-id="${r.id}" data-company="${r.company_name}" data-address="${r.address??''}"
                    data-contact="${r.contact_person??''}" data-phone="${r.phone_number??''}"
                    data-bank="${r.bank_name??''}" data-account="${r.bank_account??''}">
                    <i class="bx bx-edit-alt"></i></a>
                <a href="#" class="btn btn-sm btn-icon btn-outline-danger js-del"
                    data-id="${r.id}" data-company="${r.company_name}">
                    <i class="bx bx-trash"></i></a>
            </div>`
            ]).draw(false);
        }
        editModal?.hide();
        toast('Saved');
        }catch(err){
        Swal.fire({icon:'error', title:'Failed', text: err.message || 'Cannot save data'});
        }
    });

    // ========== DELETE (no token) ==========
    $('#tblSuppliers').on('click', '.js-del', async function(e){
        e.preventDefault();
        const id   = this.dataset.id;
        const name = this.dataset.company || 'this supplier';
        const ok = await Swal.fire({
        title:'Delete?', html:`<div class="text-muted">Delete <b>${name}</b> permanently.</div>`,
        icon:'warning', showCancelButton:true, confirmButtonText:'Yes, delete', cancelButtonText:'Cancel', confirmButtonColor:'#d33'
        });
        if(!ok.isConfirmed) return;

        try{
        const res = await fetch(`${baseUrl}/${id}`, { method:'DELETE' });
        if(!res.ok){ throw new Error(await res.text() || 'Failed'); }
        // remove row
        const $tr = $(`#tblSuppliers tbody tr[data-id="${id}"]`);
        table.row($tr).remove().draw(false);
        toast('Deleted');
        }catch(err){
        Swal.fire({icon:'error', title:'Failed', text: err.message || 'Cannot delete data'});
        }
    });

    console.log('[Suppliers] ready. Swal?', !!window.Swal);
    });
    </script>
    @endpush
