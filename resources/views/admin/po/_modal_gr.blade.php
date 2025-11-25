{{-- Modal Goods Received dari PO --}}
<div class="modal fade" id="mdlReceiveGR" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Goods Received – {{ $po->po_code }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('po.gr.store', $po) }}"
            method="POST"
            enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <p class="mb-3">
            Supplier: <strong>{{ $po->supplier->name ?? '-' }}</strong><br>
            Warehouse: <strong>Central Stock</strong>
          </p>

          <div class="table-responsive mb-3">
            <table class="table table-sm align-middle">
              <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Qty Ordered</th>
                <th>Qty Received</th>
                <th>Qty Remaining</th>
                <th>Qty Good</th>
                <th>Qty Damaged</th>
                <th>Notes</th>
              </tr>
              </thead>
              <tbody>
              @foreach($po->items as $idx => $item)
                @php
                  $ordered   = (int)($item->qty_ordered ?? 0);
                  $received  = (int)($item->qty_received ?? 0);
                  $remaining = max(0, $ordered - $received);
                  $key       = $item->id; // <-- PENTING: pakai ID item sebagai key array
                @endphp
                <tr>
                  <td>{{ $idx + 1 }}</td>
                  <td>
                    {{ $item->product->name ?? '-' }}<br>
                    <small class="text-muted">{{ $item->product->product_code ?? '' }}</small>
                  </td>
                  <td>{{ $ordered }}</td>
                  <td>{{ $received }}</td>
                  <td>{{ $remaining }}</td>

                  <td style="width:120px">
                    <input type="number"
                           class="form-control form-control-sm"
                           name="receives[{{ $key }}][qty_good]"
                           min="0"
                           max="{{ $remaining }}"
                           value="{{ $remaining }}">
                  </td>

                  <td style="width:120px">
                    <input type="number"
                           class="form-control form-control-sm"
                           name="receives[{{ $key }}][qty_damaged]"
                           min="0"
                           max="{{ $remaining }}"
                           value="0">
                  </td>

                  <td style="width:180px">
                    <input type="text"
                           class="form-control form-control-sm"
                           name="receives[{{ $key }}][notes]"
                           placeholder="Catatan (opsional)">
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>

          <small class="text-muted d-block mb-3">
            Qty Good + Qty Damaged tidak boleh lebih besar dari Qty Remaining.
          </small>

          {{-- Upload foto, nempel ke GR pertama --}}
          <div class="mb-3">
            <label class="form-label">Upload Foto (opsional)</label>
            <input type="file" name="photos[]" class="form-control" multiple>
            <small class="text-muted">
              Bisa upload DO, faktur, atau foto kondisi barang. Maks 2MB per file.
            </small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save"></i> Simpan Goods Received
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
