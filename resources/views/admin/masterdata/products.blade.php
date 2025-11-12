@extends('layouts.home')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- DataTables Responsive CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>

<style>
        .swal2-container { z-index: 20000 !important; }
  /* Biar tabel rapi di desktop & mobile tanpa scroll horizontal */
  #tblProducts { width: 100% !important; }
  #tblProducts th { white-space: nowrap; }
  #tblProducts td { white-space: nowrap; } /* child rows akan auto-wrap sendiri */
</style>

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
          <input id="searchProduct" type="text" class="form-control" placeholder="Search product..." style="max-width:260px">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mdlProduct" id="btnShowAdd">
            <i class="bx bx-plus"></i> Add Product
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Table: TANPA .table-responsive, pakai Responsive plugin --}}
  <div class="card">
    <table id="tblProducts" class="table table-hover align-middle mb-0 table-bordered">
      <thead>
      <tr>
        <th>NO</th>             {{-- dipakai jadi control kolom responsive --}}
        <th>CODE</th>
        <th>PRODUCT NAME</th>
        <th>CATEGORY</th>
        <th>PACKAGE</th>
        <th>SUPPLIER</th>
        <th>DESCRIPTION</th>
        <th class="text-end">STOCK</th>
        <th class="text-end">PURCHASING</th>
        <th class="text-end">SELLING</th>
        <th style="width:120px">ACTIONS</th>
      </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

</div>

{{-- Modal Add/Edit --}}
<div class="modal fade" id="mdlProduct" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title fw-semibold" id="modalTitle">Add Product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form id="formProduct" class="modal-body">
      @csrf
      <input type="hidden" name="_method" id="method" value="POST">
      <div class="row g-3">

        <div class="col-md-4">
          <label class="form-label">Product Code <span class="text-danger">*</span></label>
          <input type="text" name="product_code" id="product_code" class="form-control"
                 value="{{ $nextProductCode }}" data-default="{{ $nextProductCode }}" required placeholder="PRD-001">
          <small class="text-muted">Bisa diubah. Duplikat akan ditolak.</small>
        </div>
        <div class="col-md-8">
          <label class="form-label">Product Name <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Category <span class="text-danger">*</span></label>
          <select name="category_id" id="category_id" class="form-select" required>
            <option value="">— Choose —</option>
            @foreach($categories as $c)
              <option value="{{ $c->id }}">{{ $c->category_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Package / Satuan</label>
          <select name="package_id" id="package_id" class="form-select">
            <option value="">— None —</option>
            @foreach($packages as $p)
              <option value="{{ $p->id }}">{{ $p->package_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Supplier</label>
          <select name="supplier_id" id="supplier_id" class="form-select">
            <option value="">— None —</option>
            @foreach($suppliers as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea name="description" id="description" class="form-control" rows="3" placeholder="Optional"></textarea>
        </div>

        <div class="col-md-4">
          <label class="form-label">Purchasing Price</label>
          <input type="number" name="purchasing_price" id="purchasing_price" class="form-control" value="0" min="0">
        </div>
        <div class="col-md-4">
          <label class="form-label">Selling Price</label>
          <input type="number" name="selling_price" id="selling_price" class="form-control" value="0" min="0">
        </div>
        <div class="col-md-4">
          <label class="form-label">Min Stock</label>
          <input type="number" name="stock_minimum" id="stock_minimum" class="form-control" min="0">
        </div>

      </div>

      <div class="mt-4 d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="btnSubmit">Submit</button>
      </div>
    </form>
  </div></div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

{{-- DataTables Responsive JS --}}
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  const baseUrl     = @json(url('products'));
  const dtUrl       = @json(route('products.datatable'));
  const nextCodeUrl = @json(route('products.next_code'));
  const csrf        = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': csrf} });

  const table = $('#tblProducts').DataTable({
    processing: true,
    serverSide: true,
    lengthChange: false,
    dom: 'rt<"d-flex justify-content-between align-items-center p-2"ip>',
    ajax: { url: dtUrl, type: 'GET' },
    order: [[1, 'asc']],

    // === RESPONSIVE ===
    responsive: {
      details: {
        type: 'column',     // klik kolom pertama (NO) untuk buka detail
        target: 0,
        renderer: function ( api, rowIdx, columns ) {
          // render kolom yang "none" ke bentuk daftar rapi
          const data = $.map(columns, function (col, i) {
            if (col.hidden) {
              return `<tr>
                        <td class="text-muted">${col.title}</td>
                        <td>${col.data || '-'}</td>
                      </tr>`;
            }
            return '';
          }).join('');
          return data ? $('<table/>').addClass('table table-sm mb-0').append(data) : false;
        }
      }
    },

    columnDefs: [
      // jadikan kolom NO sebagai control untuk responsive details
      { className: 'dtr-control', orderable: false, targets: 0 },
      // Prioritas: NAME & ACTIONS tetap kelihatan di layar kecil
      { responsivePriority: 1, targets: 2 },    // PRODUCT NAME
      { responsivePriority: 2, targets: 10 },   // ACTIONS
    ],

    // Set kolom mana yg selalu tampil (all) dan mana yang pindah ke child (none)
    columns: [
      { data: 'rownum', orderable:false, searchable:false, className:'all' }, // NO (control)
      { data: 'product_code', className:'all' },   // all = selalu tampil
      { data: 'name', className:'all' },
      { data: 'category', className:'none' },      // none = pindah ke child row saat sempit
      { data: 'package', className:'none' },
      { data: 'supplier', className:'none' },
      { data: 'description', className:'none' },
      { data: 'total_stock', className:'none text-end' },
      { data: 'purchasing_price', className:'none text-end' },
      { data: 'selling_price', className:'none text-end' },
      { data: 'actions', orderable:false, searchable:false, className:'all' }
    ]
  });

  $('#searchProduct').on('keyup change', function(){ table.search(this.value).draw(); });
  $('#pageLength').on('change', function(){ table.page.len(parseInt(this.value||10,10)).draw(); });
  $('#product_code').on('input', function(){ this.value = this.value.toUpperCase(); });

  // ADD
  $('#btnShowAdd').on('click', function(){
    $('#modalTitle').text('Add Product');
    $('#formProduct').attr('action', baseUrl);
    $('#method').val('POST');
    $('#btnSubmit').text('Submit');
    $('#formProduct').trigger('reset');
    $('#category_id, #package_id, #supplier_id').val('');

    $.get(nextCodeUrl, function(res){
      $('#product_code').val(res?.next_code || $('#product_code').data('default'));
    });
  });

  // SUBMIT
  $('#formProduct').on('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fd.set('_method', $('#method').val());
    $.ajax({
      url: $(this).attr('action') || baseUrl,
      method: 'POST',
      data: fd, processData: false, contentType: false,
      success: function(res){
        $('#mdlProduct').modal('hide');
        table.ajax.reload(null, false);
        Swal.fire({ title: res.success, icon: 'success', timer: 1200, showConfirmButton:false });
      },
      error: function(xhr){
        let msg = 'Something went wrong!';
        if (xhr.status === 422 && xhr.responseJSON?.errors) {
          msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
        } else if (xhr.responseJSON?.error) {
          msg = xhr.responseJSON.error;
        }
        Swal.fire({ title: 'Error!', html: msg, icon:'error' });
      }
    });
  });

  // EDIT
  $(document).on('click', '.js-edit', function(){
    const d = $(this).data();
    $('#modalTitle').text('Edit Product');
    $('#formProduct').attr('action', baseUrl + '/' + d.id);
    $('#method').val('PUT');
    $('#btnSubmit').text('Update');

    $('#product_code').val(d.product_code);
    $('#name').val(d.name);
    $('#category_id').val(d.category_id || '');
    $('#package_id').val(d.package_id || '');
    $('#supplier_id').val(d.supplier_id || '');
    $('#description').val(d.description || '');
    $('#purchasing_price').val(d.purchasing_price);
    $('#selling_price').val(d.selling_price);
    $('#stock_minimum').val(d.stock_minimum || '');

    $('#mdlProduct').modal('show');
  });

  // DELETE
  $(document).on('click', '.js-del', function(){
    const id = $(this).data('id');
    Swal.fire({
      title: 'Delete product?',
      text: 'Tindakan ini tidak bisa dibatalkan.',
      icon: 'warning',
      showCancelButton: true
    }).then((res) => {
      if (!res.isConfirmed) return;
      $.post(baseUrl + '/' + id, {_method:'DELETE'}, function (r) {
        table.ajax.reload(null, false);
        Swal.fire('Deleted!', r.success, 'success');
      }).fail(function(){
        Swal.fire('Error!', 'Could not delete product!', 'error');
      });
    });
  });
});
</script>
@endpush
