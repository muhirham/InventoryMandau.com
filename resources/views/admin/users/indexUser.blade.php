    @extends('layouts.home')

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">


    {{-- KPI --}}


    {{-- Filters & toolbar --}}
    <div class="card mb-3">
        <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-md-4">
            <label class="form-label mb-1 ">Role</label>
            <select id="f_role" class="form-select">
                <option value="">Select Role</option>
                <option>admin</option>
                <option>warehouse</option>
                <option>director</option>
            </select>
            </div>
            <div class="col-12 col-md-4">
            <label class="form-label mb-1">Plan</label>
            <select id="f_plan" class="form-select">
                <option value="">Select Plan</option>
            </select>
            </div>
            <div class="col-12 col-md-4">
            <label class="form-label mb-1">Status</label>
            <select id="f_status" class="form-select">
                <option value="">Select Status</option>
                <option>Active</option>
                <option>Inactive</option>
            </select>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <div class="d-flex align-items-center gap-2">
            <label class="text-muted .dataTables_length select" >Show</label>
            <select id="pageLength" class="form-select custom-arrow" style="width:90px">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            </div>

            <div class="row g-2">
            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
            <input id="searchUser" type="text" class="form-control" placeholder="Search User" style="max-width:260px">

            <div class="btn-group">
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bx bx-export me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="" id="btnExportCSV">CSV</a></li>
                <li><a class="dropdown-item" href="" id="btnExportPrint">Print</a></li>
                </ul>
            </div>

                    {{-- Tombol delet --}}
            <button id="btnBulkDelete" class="btn btn-outline-danger">
            <i class="bx bx-trash me-1"></i> Delete Selected
            </button>

        {{-- resources/views/users/partials/add_user_glass_modal.blade.php --}}
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#glassAddUser">
        <i class="bx bx-plus"></i> Add User
        </button>

        <div class="modal fade" id="glassAddUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="background:rgba(17,22,28,.6);backdrop-filter:blur(14px)">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">Add User</h5>
                {{-- PENTING: data-bs-dismiss="modal" + type="button" --}}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- ALERT untuk error (validasi) di dalam modal --}}
            @if ($errors->any())
                <div class="px-4">
                <div class="alert alert-danger mb-0">
                    <div class="fw-semibold mb-1">Gagal menyimpan:</div>
                    <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                    </ul>
                </div>
                </div>
            @endif

            <form id="formAddUser" method="POST" action="{{ route('users.store') }}" class="modal-body text-white">
                @csrf
                <div class="mb-3">
                <label class="form-label text-white ">Name</label>
                <input name="name" value="{{ old('name') }}"
                        class="form-control bg-transparent text-white border-secondary" required>
                </div>
                <div class="mb-3">
                <label class="form-label text-white">Email</label>
                <input name="email" type="email" value="{{ old('email') }}"
                        class="form-control bg-transparent text-white border-secondary" required>
                </div>
                <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select bg-transparent text-white border-secondary" required>
                <option value="" selected disabled hidden>— Select role —</option>
                @foreach (['admin','warehouse','director'] as $r)
                    <option value="{{ $r }}" @selected(old('role')===$r)>{{ ucfirst($r) }}</option>
                @endforeach
                </select>

                </div>
                <div class="row g-2">
                <div class="col">
                    <input name="password" type="password" placeholder="Password"
                        class="form-control bg-transparent text-white border-secondary" required>
                </div>
                <div class="col">
                    <input name="password_confirmation" type="password" placeholder="Confirm"
                        class="form-control bg-transparent text-white border-secondary" required>
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

        {{-- EDIT USER – Glass Modal --}}
        <div class="modal fade" id="glassEditUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="background:rgba(17,22,28,.6);backdrop-filter:blur(14px)">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formEditUser" method="POST" class="modal-body text-white">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id">

                <div class="mb-3">
                <label class="form-label text-white">Name</label>
                <input id="edit_name" name="name" class="form-control bg-transparent text-white border-secondary" required>
                </div>

                <div class="mb-3">
                <label class="form-label text-white">Email</label>
                <input id="edit_email" name="email" type="email" class="form-control bg-transparent text-white border-secondary" required>
                </div>

                <div class="mb-3">
                <label class="form-label text-white ">Role</label>
                <select id="edit_role" name="role" class="form-select bg-transparent text-white border-secondary text-white" required>
                    <option value="" disabled hidden selected>— Select role —</option>
                    @foreach(['admin','warehouse','director'] as $r)
                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
                </div>

                <div class="row g-2">
                <div class="col">
                    <input name="password" type="password" placeholder="New password (opsional)" class="form-control bg-transparent text-white border-secondary">
                </div>
                <div class="col">
                    <input name="password_confirmation" type="password" placeholder="Confirm" class="form-control bg-transparent text-white border-secondary">
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



    {{-- Users table (data dari DB) --}}
    <div class="card">
        <div class="table-responsive">
        <table id="tblUsers" class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th style="width:36px"><input class="form-check-input" type="checkbox" id="checkAll"></th>
                <th>ID</th>
                <th>USER</th>
                <th>EMAIL</th>
                <th>DATE</th>
                <th>ROLE</th>
                <th>UPDATE</th>
                <th style="width:120px">ACTIONS</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                <td><input class="form-check-input row-check" type="checkbox"></td>
                <td>{{ $u->id }}</td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->created_at?->format('Y-m-d') }}</td>
                <td>{{ ucfirst($u->role ?? '-') }}</td>
                <td>{{ $u->updated_at?->diffForHumans() }}</td>
            <td>
            <div class="d-flex gap-1">
                <a href="#"
                class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                title="Edit"
                data-id="{{ $u->id }}"
                data-name="{{ $u->name }}"
                data-email="{{ $u->email }}"
                data-role="{{ $u->role }}">
                <i class="bx bx-edit-alt"></i>
                </a>

                 <a href="#"
                class="btn btn-sm btn-icon btn-outline-danger js-del"
                title="Delete"
                data-id="{{ $u->id }}"
                data-name="{{ $u->name }}">
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


  @if (session('error'))
    <div class="toast align-items-center border-0 show bg-danger text-white" role="alert">
      <div class="d-flex">
        <div class="toast-body">{{ session('error') }}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  @endif
</div>


    @push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    {{-- tweak dark mode DataTables --}}
    <style>

         .swal2-container {
    z-index: 20000 !important;   /* > z-index navbar (±1030) & modal (1055–1065) */
  }

        html[data-color-scheme="dark"] .dataTables_info,
        html[data-color-scheme="dark"] .dataTables_paginate .pagination > li > a { color: var(--text); }
        html[data-color-scheme="dark"] .page-link { background: var(--bg-card); border-color: var(--border); }
         /* ====== Dark mode pagination (Bootstrap + DataTables) ====== */
        html[data-color-scheme="dark"] .dataTables_info {
            color: var(--text);
        }

        /* Bootstrap pagination */
        html[data-color-scheme="dark"] .pagination .page-link {
            color: var(--text);
            background-color: var(--bg-card);
            border-color: var(--border);
        }
        html[data-color-scheme="dark"] .pagination .page-link:hover {
            color: var(--text);
            background-color: var(--bg-hover, #1f2430);
            border-color: var(--border);
        }
        html[data-color-scheme="dark"] .pagination .page-item.active .page-link {
            color: #fff;
            background-color: var(--primary, #6366f1);
            border-color: var(--primary, #6366f1);
            box-shadow: 0 0 0 0.15rem rgba(99,102,241,.25);
        }
        html[data-color-scheme="dark"] .pagination .page-item.disabled .page-link {
            color: var(--text-muted, #8b93a7);
            background-color: var(--bg-elev-1, #171b24);
            border-color: var(--border);
            opacity: .75;
        }

        /* DataTables paginate_button (non-BS skin) */
        html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text) !important;
            border: 1px solid var(--border) !important;
            background: var(--bg-card) !important;
        }
        html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: var(--text) !important;
            background: var(--bg-hover, #1f2430) !important;
            border-color: var(--border) !important;
        }
        html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            color: #fff !important;
            background: var(--primary, #6366f1) !important;
            border-color: var(--primary, #6366f1) !important;
        }
        html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: var(--text-muted, #8b93a7) !important;
            background: var(--bg-elev-1, #171b24) !important;
            border-color: var(--border) !important;
        }

        /* Fokus aksesibilitas */
        html[data-color-scheme="dark"] .pagination .page-link:focus,
        html[data-color-scheme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:focus {
            outline: none;
            box-shadow: 0 0 0 0.15rem rgba(99,102,241,.25);
        }
    </style>
    @endpush

    @push('scripts')
    {{-- Plugins --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    // delet js
    <script>
document.addEventListener('DOMContentLoaded', () => {

  const baseUrl = @json(url('users')); // /users

  // ========== DELETE USER ==========
  $('#tblUsers').on('click', '.js-del', function(e){
    e.preventDefault();
    const id   = this.dataset.id;
    const name = this.dataset.name || 'user ini';

    Swal.fire({
      title: 'Hapus user?',
      html: `<div class="text-muted">Data <b>${name}</b> akan dihapus permanen.</div>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#d33',
      reverseButtons: true,
      backdrop: true,
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          const res = await fetch(`${baseUrl}/${id}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
            }
          });

          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Terhapus',
              text: `User ${name} berhasil dihapus.`,
              timer: 2000,
              showConfirmButton: false,
            });
            // reload tabel biar hilang dari view
            setTimeout(() => location.reload(), 1200);
          } else {
            const msg = await res.text();
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: msg || 'Gagal menghapus user.',
            });
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'Terjadi kesalahan koneksi.',
          });
        }
      }
    });
  });
});
</script>



   // edit JS
        <script>
    document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('glassEditUser');
    const modal   = modalEl ? new bootstrap.Modal(modalEl) : null;
    const form    = document.getElementById('formEditUser');
    const baseUrl = @json(url('users'));

    // === KLIK EDIT BUTTON ===
    document.querySelectorAll('.js-edit').forEach(btn => {
        btn.addEventListener('click', e => {
        e.preventDefault();
        const id    = btn.dataset.id;
        const name  = btn.dataset.name || '';
        const email = btn.dataset.email || '';
        const role  = btn.dataset.role || '';

        // isi form
        form.setAttribute('action', `${baseUrl}/${id}`);
        document.getElementById('edit_id').value   = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value= email;
        document.getElementById('edit_role').value = role;

        form.querySelector('input[name="password"]').value = '';
        form.querySelector('input[name="password_confirmation"]').value = '';

        modal?.show();
        });
    });

    // === Reset form saat modal ditutup ===
    modalEl?.addEventListener('hidden.bs.modal', () => form?.reset());

    // === Flash feedback ===
    @if ($errors->any() && session('edit_open_id'))
        // auto buka modal edit dengan data lama
        (()=>{
        const id = @json(session('edit_open_id'));
        form.setAttribute('action', `${baseUrl}/${id}`);
        document.getElementById('edit_name').value  = @json(old('name'));
        document.getElementById('edit_email').value = @json(old('email'));
        document.getElementById('edit_role').value  = @json(old('role'));
        modal?.show();
        })();

        Swal.fire({
        icon: 'error',
        title: 'Gagal menyimpan',
        html: '<div style="text-align:left;">' + @json($errors->all()).map(e => '• ' + e).join('<br>') + '</div>',
        confirmButtonText: 'OK'
        });
    @endif

    @if (session('edit_success'))
        modal?.hide();
        Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: @json(session('edit_success')),
        timer: 2000,
        showConfirmButton: false
        });
    @endif

    @if (session('edit_error'))
        modal?.show();
        Swal.fire({
        icon: 'error',
        title: 'Error',
        text: @json(session('edit_error')),
        confirmButtonText: 'OK'
        });
    @endif
    });
    </script>



    <script>
        // Inisialisasi halaman
        $(function () {
        // DataTable
        const table = $('#tblUsers').DataTable({
            order: [[1, 'asc']],
            ordering: true,
            columnDefs: [
            { targets: 0, orderable: false },
            { targets: 7, orderable: false },
            { targets: 1, type: 'num' }
            ],
            pageLength: 10,
            lengthChange: false,
            searching: true,
            info: true,
            dom: 't<"d-flex justify-content-between align-items-center p-3 pt-2"ip>'
        });

        // Search, page length, filter role, check all
        $('#searchUser').on('keyup', function(){ table.search(this.value).draw(); });
        $('#pageLength').on('change', function(){ table.page.len(parseInt(this.value,10)).draw(); });
        $('#f_role').on('change', function(){
            const val = this.value ? '^'+this.value+'$' : '';
            table.column(5).search(val, true, false).draw();
        });
        $('#checkAll').on('change', function(){ $('.row-check').prop('checked', this.checked); });

        // Export CSV
        $('#btnExportCSV').on('click', function(e){
            e.preventDefault();
            const headers = ['ID','User','Email','Date','Role','Updated'];
            let csv = headers.join(',') + '\n';
            $('#tblUsers tbody tr:visible').each(function(){
            const tds = $(this).find('td');
            const id      = tds.eq(1).text().trim();
            const user    = tds.eq(2).text().trim();
            const email   = tds.eq(3).text().trim();
            const date    = tds.eq(4).text().trim();
            const role    = tds.eq(5).text().trim();
            const updated = tds.eq(6).text().trim();
            csv += [id,user,email,date,role,updated].join(',') + '\n';
            });
            const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            $('<a>').attr({href:url, download:'users.csv'})[0].click();
            setTimeout(()=>URL.revokeObjectURL(url), 1000);
        });

        // Print
        $('#btnExportPrint').on('click', function(e){ e.preventDefault(); window.print(); });
// Modal add user (bootstrap)
const modalEl = document.getElementById('glassAddUser');
const modalAdd = modalEl ? new bootstrap.Modal(modalEl) : null;

// Disable submit double click
const form = document.getElementById('formAddUser');
if (form) {
  form.addEventListener('submit', () => {
    const btn = form.querySelector('button[type="submit"]');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
  });
}

// Reset form saat cancel / close
modalEl?.addEventListener('hidden.bs.modal', () => {
  form?.reset();
  form?.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
});

// === AUTO SHOW jika validasi gagal ===
@if ($errors->any())
  modalAdd?.show();
@endif

// === ALERT sukses & error pakai SweetAlert2 ===
@if (session('success'))
  modalAdd?.hide();
  Swal.fire({
    icon: 'success',
    title: 'Success',
    text: @json(session('success')),
    confirmButtonText: 'OK',
    timer: 2500,
    showConfirmButton: false,
  });
@endif

@if (session('error'))
  modalAdd?.show();
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: @json(session('error')),
    confirmButtonText: 'OK'
  });
@endif

// === Inisialisasi toast (opsional) ===
document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t, { delay: 3000 }).show());

        });
    </script>

    <script>
$(function(){

  // Helper ambil IDs yang dicentang (dari kolom ID = index 1)
  function getCheckedIds() {
    const ids = [];
    $('#tblUsers tbody tr').each(function(){
      const $tr = $(this);
      const $cb = $tr.find('.row-check');
      if ($cb.is(':checked')) {
        const id = $tr.find('td').eq(1).text().trim();
        if (id) ids.push(Number(id));
      }
    });
    return ids;
  }

  // Check all (header) → centang semua di halaman aktif
  $('#checkAll').on('change', function(){
    const checked = this.checked;
    $('#tblUsers tbody .row-check').prop('checked', checked);
  });

  // Bulk delete
  $('#btnBulkDelete').on('click', async function(e){
    e.preventDefault();
    const ids = getCheckedIds();

    if (!ids.length) {
      return Swal.fire({ icon:'info', title:'Tidak ada data', text:'Pilih minimal satu baris.' });
    }

    const sample = ids.slice(0,5).join(', ') + (ids.length > 5 ? '…' : '');

    const confirm = await Swal.fire({
      icon: 'warning',
      title: 'Hapus data terpilih?',
      html: `<div class="text-muted">Total <b>${ids.length}</b> user akan dihapus.<br>ID: ${sample}</div>`,
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#d33',
      reverseButtons: true
    });

    if (!confirm.isConfirmed) return;

    try {
      const res = await fetch(@json(route('users.bulk-destroy')), {
        method: 'POST',
        headers: {
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ ids })
      });

      if (!res.ok) {
        const msg = await res.text();
        throw new Error(msg || 'Gagal menghapus.');
      }

      Swal.fire({
        icon:'success',
        title:'Berhasil',
        text:'Data terpilih berhasil dihapus.',
        timer: 1500,
        showConfirmButton: false
      });

      // refresh tampilan
      setTimeout(()=> location.reload(), 900);

    } catch (err) {
      Swal.fire({ icon:'error', title:'Gagal', text: err.message || 'Terjadi kesalahan.' });
    }
  });

});
</script>

    @endpush
