<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;

// ===== ADMIN =====
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PreOController;
use App\Http\Controllers\Admin\RestockApprovalController; // <<— APPROVAL RESTOCK (ADMIN)

// ===== WAREHOUSE =====
use App\Http\Controllers\Warehouse\WarehouseDashboardController;
use App\Http\Controllers\Warehouse\SalesController as WhSalesController;
use App\Http\Controllers\Warehouse\SalesHandoverController;
use App\Http\Controllers\Warehouse\StockWhController;     // <<— RESTOCK dari sisi WAREHOUSE

// ===== OTHERS =====
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\StockController; // kalau masih dipakai resource('/stock', ...)

use App\Models\SalesHandover;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;

/* =========================
|  AUTH & ROOT
========================= */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'attempt'])->name('login.attempt');
});

Route::get('/', function () {
    return redirect()->route('dashboard'); // pastikan ada route 'dashboard'
})->middleware('auth');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

/* =========================
|  ADMIN AREA
========================= */
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');

    // Master Data
    Route::resource('warehouses', WarehouseController::class)->only(['index','store','update','destroy']);

    Route::get('categories/datatable', [CategoryController::class,'datatable'])->name('categories.datatable');
    Route::resource('categories', CategoryController::class)->only(['index','store','update','destroy']);

    Route::get('products/datatable',  [ProductController::class, 'datatable'])->name('products.datatable');
    Route::get('products/next-code',  [ProductController::class, 'nextCode'])->name('products.next_code');
    Route::resource('products', ProductController::class)->except(['create','edit','show']);

    Route::get('/suppliers/datatable', [SupplierController::class, 'datatable'])->name('suppliers.datatable');
    Route::get('/suppliers/next-code', [SupplierController::class, 'nextCode'])->name('suppliers.next_code');
    Route::resource('suppliers', SupplierController::class)->only(['index','store','update','destroy']);

    Route::get('packages/datatable', [PackageController::class, 'datatable'])->name('packages.datatable');
    Route::resource('packages', PackageController::class)->except(['create','edit','show']);

    Route::resource('users', UserController::class)->except(['create','show','edit']);
    Route::post('users/bulk-destroy', [UserController::class,'bulkDestroy'])->name('users.bulk-destroy');

    // Stock Level (admin view)
    Route::get('/StockLevel',            [StockLevelController::class, 'index'])->name('StockLevel.index');
    Route::get('/StockLevel/datatable',  [StockLevelController::class, 'datatable'])->name('StockLevel.datatable');

    // Purchase Orders
    Route::get('/po',                 [PreOController::class, 'index'])->name('po.index');
    Route::post('/po/from-requests',  [PreOController::class, 'createFromRequests'])->name('po.fromRequests');
    Route::get('/po/{po}',            [PreOController::class, 'edit'])->name('po.edit');
    Route::put('/po/{po}',            [PreOController::class, 'update'])->name('po.update');
    Route::post('/po/{po}/order',     [PreOController::class, 'order'])->name('po.order');
    Route::post('/po/{po}/cancel',    [PreOController::class, 'cancel'])->name('po.cancel');
    Route::get('/po/{po}/pdf',        [PreOController::class, 'exportPdf'])->name('po.pdf');
    Route::get('/po/{po}/excel',      [PreOController::class, 'exportExcel'])->name('po.excel');

    // Restock Approval (Admin)
    Route::get('stockRequest',                 [RestockApprovalController::class, 'index'])->name('stockRequest.index');
    Route::get('stockRequest/json',            [RestockApprovalController::class, 'json'])->name('stockRequest.json');
    Route::post('stockRequest/{id}/approve',   [RestockApprovalController::class, 'approve'])->name('stockRequest.approve');
    Route::post('stockRequest/{id}/reject',    [RestockApprovalController::class, 'reject'])->name('stockRequest.reject');
    Route::post('stockRequest/bulk-po',        [RestockApprovalController::class, 'bulkPO'])->name('stockRequest.bulkpo'); 
});

/* =========================
|  WAREHOUSE AREA
========================= */
Route::middleware(['auth', 'role:warehouse'])->group(function () {

    // Dashboard
    Route::get('/warehouse', [WarehouseDashboardController::class,'index'])
        ->name('warehouse.dashboard');

    // (kalau masih dipakai untuk modul lain)
    Route::resource('/stock', StockWhController::class);

    // Reports (opsional)
    Route::resource('/reports', ReportController::class)->only(['index']);

    // Stock Level (warehouse view)
    Route::get('/StockLevel',           [StockLevelController::class,'index'])->name('StockLevel.index');
    Route::get('/StockLevel/datatable', [StockLevelController::class,'datatable'])->name('StockLevel.datatable');

    // Restocks (Warehouse): create + datatable + receive
    Route::get('/restocks',                   [StockWhController::class,'index'])->name('restocks.index');
    Route::get('/restocks/datatable',         [StockWhController::class,'datatable'])->name('restocks.datatable');
    Route::post('/restocks',                  [StockWhController::class,'store'])->name('restocks.store');
    Route::post('/restocks/{restock}/receive',[StockWhController::class,'receive'])->name('restocks.receive');

    // ===== Sales Handover Morning (issue ke sales)
    Route::get('/sales/handover/morning', function () {
        $me = auth()->user();

        $whQuery = Warehouse::query();
        if ($me->warehouse_id) $whQuery->where('id', $me->warehouse_id);

        if (Schema::hasColumn('warehouses','warehouse_name')) {
            $whQuery->orderBy('warehouse_name');
            $warehouses = $whQuery->get(['id', DB::raw('warehouse_name as name')]);
        } elseif (Schema::hasColumn('warehouses','name')) {
            $whQuery->orderBy('name');
            $warehouses = $whQuery->get(['id','name']);
        } else {
            $warehouses = $whQuery->get(['id'])->map(fn($w)=> (object)['id'=>$w->id,'name'=>'Warehouse #'.$w->id]);
        }

        $salesUsers = User::where('role','sales')
            ->when($me->warehouse_id, fn($q)=>$q->where('warehouse_id',$me->warehouse_id))
            ->orderBy('name')->get(['id','name','warehouse_id']);

        $products = Product::select('id','name','product_code')->orderBy('name')->get();

        return view('wh.handover_morning', compact('me','warehouses','salesUsers','products'));
    })->name('sales.handover.morning');

    // ===== Sales Handover Evening (reconcile)
    Route::get('/sales/handover/evening', function () {
        $me = auth()->user();
        $handovers = SalesHandover::with('sales:id,name')
            ->whereIn('status',['issued','waiting_otp'])
            ->when($me->warehouse_id, fn($q)=>$q->where('warehouse_id',$me->warehouse_id))
            ->orderBy('handover_date','desc')
            ->get(['id','code','status','sales_id','handover_date','warehouse_id'])
            ->map(fn($h)=> (object)[
                'id'=>$h->id,'code'=>$h->code,'status'=>$h->status,
                'sales_id'=>$h->sales_id,'handover_date'=>$h->handover_date,
                'sales_name'=>$h->sales->name ?? null,
            ]);

        return view('wh.handover_evening', compact('me','handovers'));
    })->name('sales.handover.evening');

    // ===== Actions & API
    Route::post('/sales/handover/issue',                 [SalesHandoverController::class,'issue'])->name('sales.handover.issue');
    Route::post('/sales/handover/{handover}/generate-otp',[SalesHandoverController::class,'generateOtp'])->name('sales.handover.otp');
    Route::post('/sales/handover/{handover}/reconcile',  [SalesHandoverController::class,'reconcile'])->name('sales.handover.reconcile');

    Route::get('/sales/handover/{handover}/items', function(SalesHandover $handover){
        $me = auth()->user();
        if ($me->warehouse_id && $handover->warehouse_id != $me->warehouse_id) abort(403);

        $items = $handover->items()->with(['product:id,name'])->get()->map(function($x){
            return [
                'product_id'            => $x->product_id,
                'product_name'          => $x->product->name ?? ('Produk #'.$x->product_id),
                'qty_dispatched'        => (int)$x->qty_dispatched,
                'qty_returned_good'     => (int)$x->qty_returned_good,
                'qty_returned_damaged'  => (int)$x->qty_returned_damaged,
                'qty_sold'              => (int)$x->qty_sold,
            ];
        });
        return response()->json(['items'=>$items]);
    })->name('sales.handover.items');

    // (opsional) halaman laporan
    Route::get('/sales/report',              [SalesHandoverController::class,'index'])->name('sales.report.index');
    Route::get('/sales/report/datatable',    [SalesHandoverController::class,'reportDatatable'])->name('sales.report.dt');
});

/* =========================
|  SALES AREA (role:sales)
========================= */
Route::middleware(['auth', 'role:sales'])->group(function () {
    Route::get('/sales',          [WhSalesController::class, 'dashboard'])->name('sales.dashboard');
    Route::get('/sales/report',   [WhSalesController::class, 'report'])->name('sales.report');
    Route::get('/sales/return',   [WhSalesController::class, 'return'])->name('sales.return');
});
