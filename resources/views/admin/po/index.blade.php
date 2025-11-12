    @extends('layouts.home')

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

    <div class="card mb-3">
        <div class="card-body d-flex gap-2 align-items-center">
        <form class="d-flex gap-2 ms-auto" method="get">
            <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="Cari PO code...">
            <button class="btn btn-primary">Cari</button>
        </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Purchase Orders</h5>
        </div>

        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>PO CODE</th>
                <th>Supplier</th>
                <th>Status</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Grand</th>
                <th>Lines</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($pos as $po)
            <tr>
                <td class="fw-bold">{{ $po->po_code }}</td>
                <td>{{ $po->supplier->supplier_name ?? '-' }}</td>
                <td><span class="badge bg-label-info text-uppercase">{{ $po->status }}</span></td>
                <td class="text-end">{{ number_format($po->subtotal,0,',','.') }}</td>
                <td class="text-end">{{ number_format($po->discount_total,0,',','.') }}</td>
                <td class="text-end">{{ number_format($po->grand_total,0,',','.') }}</td>
                <td>{{ $po->items_count }}</td>
                <td class="text-end">
                <a class="btn btn-sm btn-primary" href="{{ route('admin.po.edit',$po->id) }}">Open</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted">Belum ada PO.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>

        <div class="card-footer">
        {{ $pos->withQueryString()->links() }}
        </div>
    </div>
    </div>
    @endsection
