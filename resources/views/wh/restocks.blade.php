@extends('layouts.home')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Toolbar --}}
  <div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-2">
      <div class="d-flex align-items-center gap-2">
        <label class="text-muted">Show</label>
        <select id="pageLength" class="form-select" style="width:90px">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
      </div>

      <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
        <input id="searchBox" class="form-control" placeholder="Cari restock...">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mdlAdd">
          <i class="bx bx-plus"></i> Buat Request
        </button>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card">
    <div class="table-responsive">
      <table id="tblRestocks" class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>NO</th>
            <th>CODE</th>
            <th>PRODUCT</th>
            <th>SUPPLIER</th>
            <th class="text-end">REQ</th>
            <th class="text-end">RCV</th>
            <th>STATUS</th>
            <th>DATE</th>
            <th style="width:80px">ACTIONS</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div>

{{-- Modal Add --}}
<div class="modal fade" id="mdlAdd" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Buat Request Restock</h5>
      <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <form id="formAdd" method="POST" action="{{ route('restocks.store') }}" class="modal-body">
      @csrf
      <div class="mb-3">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-select" required>
          <option value="">— Pilih —</option>
          @foreach($suppliers as $s)
            <option value="{{ $s->id }}">{{ $s->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Product</label>
        <select name="product_id" class="form-select" required>
          <option value="">— Pilih —</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->product_code }})</option>
          @endforeach
        </select>
      </div>

      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Qty Request</label>
          <input id="req_qty" type="number" name="quantity_requested" class="form-control" min="1" required>
          {{-- mirror agar kompatibel dgn kolom/validasi yg beda-beda --}}
          <input id="req_qty_m1" type="hidden" name="qty">
          <input id="req_qty_m2" type="hidden" name="quantity">
        </div>
        <div class="col-md-6">
          <label class="form-label">Cost/Item (opsional)</label>
          <input type="number" name="cost_per_item" class="form-control" min="0" step="0.01">
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Catatan (opsional)</label>
        <textarea name="note" rows="2" class="form-control" placeholder="Keterangan…"></textarea>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div></div>
</div>

{{-- Modal Receive --}}
<div class="modal fade" id="mdlReceive" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Terima Barang</h5>
      <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <form id="formReceive" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
        <div class="mb-2 small text-muted">
          <div>Kode: <b id="rcv_code">-</b></div>
          <div>Product: <b id="rcv_product">-</b></div>
          <div>Supplier: <b id="rcv_supplier">-</b></div>
          <div>Qty Request: <b id="rcv_qreq">0</b></div>
        </div>

        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Qty Diterima (Good)</label>
            <input type="number" name="qty_good" id="rcv_qty_good" class="form-control" min="0" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Qty Rusak</label>
            <input type="number" name="qty_damaged" id="rcv_qty_bad" class="form-control" min="0" value="0">
          </div>
        </div>

        <div class="row g-2 mt-1">
          <div class="col-md-6">
            <label class="form-label">Cost/Item (opsional)</label>
            <input type="number" step="0.01" name="cost_per_item" class="form-control" min="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Bukti Foto (opsional)</label>
            <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
            <div class="form-text">Upload foto barang rusak / selisih.</div>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Catatan (opsional)</label>
          <textarea name="notes" rows="2" class="form-control" placeholder="Catatan penerimaan…"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary">Simpan Penerimaan</button>
      </div>
    </form>
  </div></div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function(){
  $('#formAdd').on('submit', function(){
    const v = $('#req_qty').val();
    $('#req_qty_m1').val(v);
    $('#req_qty_m2').val(v);
  });

  const dtUrl = @json(route('restocks.datatable'));

  const table = $('#tblRestocks').DataTable({
    processing: true,
    serverSide: true,
    lengthChange: false,
    dom: 'rt<"d-flex justify-content-between align-items-center p-2"ip>',
    ajax: {
      url: dtUrl,
      type: 'GET',
      error: function(xhr){
        console.error('DT ajax error:', xhr.responseText || xhr.statusText);
        Swal.fire('Error','Gagal memuat data restock.','error');
      }
    },
    order: [[1,'desc']],
    columns: [
      { data: 'rownum', orderable:false, searchable:false, defaultContent:'-' },
      { data: 'code', defaultContent:'-' },
      { data: 'product', defaultContent:'-' },
      { data: 'supplier', defaultContent:'-' },
      { data: 'qty_req', className:'text-end', defaultContent:'0' },
      { data: 'qty_rcv', className:'text-end', defaultContent:'0' },
      { data: 'status', orderable:false, searchable:false, defaultContent:'-' },
      { data: 'created_at', defaultContent:'-' },
      { data: 'actions', orderable:false, searchable:false, defaultContent:'' }
    ]
  });

  $('#searchBox').on('keyup change', function(){ table.search(this.value).draw(); });
  $('#pageLength').on('change', function(){ table.page.len(parseInt(this.value||10,10)).draw(); });

  // Buka modal Receive
  $(document).on('click', '.js-receive', function(e){
    e.preventDefault();
    const d = this.dataset;

    $('#rcv_code').text(d.code || '-');
    $('#rcv_product').text(d.product || '-');
    $('#rcv_supplier').text(d.supplier || '-');
    $('#rcv_qreq').text(d.qty_req || '0');

    const qreq = parseInt(d.qty_req || '0', 10);
    const qrcv = parseInt(d.qty_rcv || '0', 10);
    const sisa = Math.max(qreq - qrcv, 0);
    $('#rcv_qty_good').val(sisa || 0);
    $('#rcv_qty_bad').val(0);

    // set action langsung dari server
    $('#formReceive').attr('action', d.action || '#');

    new bootstrap.Modal(document.getElementById('mdlReceive')).show();
  });
});
</script>


@if (session('success'))
  <script>Swal.fire({icon:'success', title:'Berhasil', text:@json(session('success')), timer:1800, showConfirmButton:false});</script>
@endif
@if (session('error'))
  <script>Swal.fire({icon:'error', title:'Gagal', text:@json(session('error'))});</script>
@endif
@endpush
