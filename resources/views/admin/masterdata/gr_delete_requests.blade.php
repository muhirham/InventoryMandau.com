    @extends('layouts.home')

    @section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0 fw-bold">Permohonan Delete GR</h4>
    </div>

    {{-- FILTER --}}
    <div class="card mb-3">
        <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('goodreceived.delete-requests.index') }}">
            <div class="col-lg-6 col-md-6">
            <label class="form-label mb-1">Search</label>
            <input type="text"
                    name="q"
                    class="form-control"
                    value="{{ $q }}"
                    placeholder="Cari PO code, GR code, requester...">
            </div>
            <div class="col-lg-3 col-md-4">
            <label class="form-label mb-1">Status</label>
            <select name="status" class="form-select">
                <option value="">All status</option>
                <option value="pending"  {{ $status === 'pending'  ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            </div>
            <div class="col-lg-3 col-md-2 d-flex align-items-end justify-content-end">
            <div class="d-flex gap-2 w-100 justify-content-end">
                <button type="submit" class="btn btn-primary">
                <i class="bx bx-search"></i> Filter
                </button>
                <a href="{{ route('goodreceived.delete-requests.index') }}" class="btn btn-outline-secondary">
                Reset
                </a>
            </div>
            </div>
        </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th style="width:60px;">#</th>
                <th>PO Code</th>
                <th>GR Code</th>
                <th>Requested By</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Approval Note</th>
                <th style="width:180px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $i => $r)
                @php
                $poCode  = $r->purchaseOrder?->po_code ?? '-';
                $grCode  = $r->restockReceipt?->code ?? '-';
                $reqName = $r->requester?->name ?? '-';

                $badgeClass = match($r->status) {
                    'pending'  => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'cancelled'=> 'secondary',
                    default    => 'secondary',
                };
                @endphp
                <tr>
                <td>{{ $requests->firstItem() + $i }}</td>
                <td>{{ $poCode }}</td>
                <td>{{ $grCode }}</td>
                <td>{{ $reqName }}</td>
                <td>
                    <span class="badge bg-label-{{ $badgeClass }} text-uppercase">
                    {{ $r->status }}
                    </span>
                </td>
                <td>{{ $r->reason ?: '-' }}</td>
                <td>{{ $r->approval_note ?: '-' }}</td>
                <td>
                    @if($r->status === 'pending')
                    <div class="d-flex gap-1">
                        <button type="button"
                                class="btn btn-sm btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#mdlApprove-{{ $r->id }}">
                        Approve
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#mdlReject-{{ $r->id }}">
                        Reject
                        </button>
                    </div>
                    @else
                    <span class="text-muted small">No action</span>
                    @endif
                </td>
                </tr>
            @empty
                <tr>
                <td colspan="8" class="text-center text-muted">
                    Belum ada permohonan.
                </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>

        @if($requests->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $requests->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

    </div>

    {{-- MODALS APPROVE / REJECT --}}
    @foreach($requests as $r)
    @php
        $poCode = $r->purchaseOrder?->po_code ?? '-';
        $grCode = $r->restockReceipt?->code ?? '-';
        $prod   = $r->restockReceipt?->product->name ?? '-';
    @endphp

    {{-- APPROVE --}}
    <div class="modal fade" id="mdlApprove-{{ $r->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST"
                action="{{ route('goodreceived.delete-requests.approve', $r) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Approve Delete GR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                Yakin ingin <strong>MENYETUJUI</strong> pembatalan GR ini?
                </p>
                <ul class="small mb-3">
                <li>PO Code: <strong>{{ $poCode }}</strong></li>
                <li>GR Code: <strong>{{ $grCode }}</strong></li>
                <li>Product: <strong>{{ $prod }}</strong></li>
                </ul>
                <div class="mb-2">
                <label class="form-label">Catatan Approval (opsional)</label>
                <textarea name="approval_note" class="form-control" rows="3"
                            placeholder="Misal: stok dikoreksi, hati-hati saat input GR berikutnya"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Approve</button>
            </div>
            </form>
        </div>
        </div>
    </div>

    {{-- REJECT --}}
    <div class="modal fade" id="mdlReject-{{ $r->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST"
                action="{{ route('goodreceived.delete-requests.reject', $r) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Reject Delete GR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                Yakin ingin <strong>MENOLAK</strong> permohonan ini?
                </p>
                <ul class="small mb-3">
                <li>PO Code: <strong>{{ $poCode }}</strong></li>
                <li>GR Code: <strong>{{ $grCode }}</strong></li>
                <li>Product: <strong>{{ $prod }}</strong></li>
                </ul>
                <div class="mb-2">
                <label class="form-label">Alasan Reject (disarankan diisi)</label>
                <textarea name="approval_note" class="form-control" rows="3"
                            placeholder="Misal: GR sudah benar, tidak perlu dihapus"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
            </form>
        </div>
        </div>
    </div>
    @endforeach

    {{-- SweetAlert flash (tanpa alert bootstrap di atas) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: @json(session('success')),
            timer: 2500,
            showConfirmButton: false
        });
        @endif

        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: @json(session('error'))
        });
        @endif
    });
    </script>
    @endsection
