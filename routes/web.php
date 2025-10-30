<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\categorieController;
use App\Http\Controllers\supplierController;
use App\Http\Controllers\warehouseController;
use App\Http\Controllers\RestockRequestController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionDetailController;
use App\Http\Controllers\stockController;
use App\Http\Controllers\requestFormController;
use App\Http\Controllers\Auth\Authenticate;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminDsController;


// --- LOGIN & LOGOUT ---


Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt'); // POST login
Route::post('/logout', [LoginController::class, 'logout'])->name('logout'); // POST logout

// --- DASHBOARD ---
Route::get('/admin/indexAdmin', [AdminDsController::class, 'index'])->name('admin');
Route::get('/admin/stats', [AdminDsController::class, 'stats'])->name('admin.index.stats');



Route::get('/welcome', function () {
    return view('welcome');
})->middleware('auth.custom:warehouse') ->name('welcome');


// ------------------------
// USERS
// ------------------------
Route::get('/users', [UserController::class, 'index'])->name('users');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');

// ------------------------
// PRODUCTS
// ------------------------
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
Route::resource('products', ProductController::class)->except(['show', 'create', 'edit']);

// ------------------------
// CATEGORIES
// ------------------------
Route::get('/categories/datatable', [categorieController::class, 'datatable'])->name('categories.datatable');
Route::resource('categories', categorieController::class)->except(['show', 'create', 'edit']);

// ------------------------
// SUPPLIERS
// ------------------------
Route::get('/suppliers/search', [supplierController::class, 'search'])->name('suppliers.search');
Route::resource('suppliers', supplierController::class)->except(['create', 'edit']);

// ------------------------
// WAREHOUSES
// ------------------------
Route::get('/warehouses/search', [warehouseController::class, 'search'])->name('warehouses.search');
Route::resource('warehouses', warehouseController::class)->except(['create', 'edit']);

// ------------------------
// STOCK & RESTOCK
// ------------------------
Route::get('/stock/json', [stockController::class, 'json'])->name('stock.json');
Route::post('/stock/bulk-delete', [stockController::class, 'bulkDestroy'])->name('stock.bulkDestroy');
Route::resource('stock', stockController::class)->except(['create', 'edit', 'show']);

Route::get('/restocks/json', [RestockRequestController::class, 'json'])->name('restocks.json');
Route::post('/restocks/{id}/approve', [RestockRequestController::class,'approve'])->name('restocks.approve');
Route::post('/restocks/{id}/reject', [RestockRequestController::class,'reject'])->name('restocks.reject');
Route::resource('restocks', RestockRequestController::class)->except(['create','edit','show']);

// ------------------------
// REQUEST FORM
// ------------------------
Route::get('/requestForm/json', [requestFormController::class, 'json'])->name('requestForm.json');
Route::resource('requestForm', requestFormController::class)->except(['create','edit','show']);

Route::middleware(['auth'])->group(function () {

    // Transactions (list/create/show/delete)
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::get('/transactions/json', [TransactionController::class, 'json'])->name('transactions.json');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

// Transaction details (page + json + store + delete)
Route::get('/transactions/{transaction}/details', [TransactionDetailController::class, 'index'])
    ->name('transactionDetails.index'); // page view
Route::get('/transactions/{transaction}/details/json', [TransactionDetailController::class, 'json'])
    ->name('transaction_details.json'); // json list
Route::post('/transactions/{transaction}/details', [TransactionDetailController::class, 'store'])
    ->name('transaction_details.store'); // add detail(s)
Route::delete('/transaction-details/{detail}', [TransactionDetailController::class, 'destroy'])
    ->name('transaction_details.destroy');
});





Route::get('reports', [ReportController::class,'index'])->name('reports.index');
Route::post('reports/generate', [ReportController::class,'generate'])->name('reports.generate');
Route::get('reports/{id}/export/csv', [ReportController::class,'exportCsv'])->name('reports.export.csv');
Route::get('reports/{id}/export/print', [ReportController::class,'exportPrintable'])->name('reports.export.print');




/*
Route::view('/transactionDetail', 'admin.placeholder', ['title' =>
'TransactionDetail'])->name('transactionDetail.index');
Route::view('/transactions', 'admin.placeholder', ['title' => 'Transactions'])->name('transactions.index');

|--------------------------------------------------------------------------
| Reports (placeholder dulu)
|--------------------------------------------------------------------------
Route::view('/reports', 'admin.placeholder', ['title' => 'Reports'])->name('reports.index');
*/