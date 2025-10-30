<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Product;
use App\Models\TransactionDetail;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDsController extends Controller
{
    // Show Blade
    public function index()
    {
        // Total transaksi dan total sales
        $totalOrders = Transaction::count();

        $totalSales = TransactionDetail::select(DB::raw('SUM(quantity * price) as total'))
            ->value('total') ?? 0;

        // Penjualan berdasarkan kategori produk
        $salesByCategory = DB::table('transaction_details as td')
            ->join('products as p', 'td.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->select('c.category_name', DB::raw('SUM(td.quantity * td.price) as total_sales'))
            ->groupBy('c.category_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // Penjualan bulanan (chart)
        $monthlySales = DB::table('transactions as t')
            ->join('transaction_details as td', 't.id', '=', 'td.transaction_id')
            ->selectRaw("DATE_FORMAT(t.transaction_date, '%Y-%m') as month, SUM(td.quantity * td.price) as total")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return view('admin.indexAdmin', compact(
            'totalOrders', 'totalSales', 'salesByCategory', 'monthlySales'
        ));
    }

    // Return JSON stats used by JS (auto update)
    public function stats(Request $req)
    {
        // --- Total Counts ---
        $totalTransactions = Transaction::count();
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalWarehouses = Warehouse::count();
        $totalSuppliers = Supplier::count();
        $totalCategories = Category::count();

        // --- Sales This Month ---
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $salesThisMonth = Transaction::where('transaction_type', 'sale')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('total');

        // --- Monthly Sales Chart (6 months) ---
        $months = 6;
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $mStart = Carbon::now()->subMonths($i)->startOfMonth();
            $mEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $labels[] = $mStart->format('M Y');
            $sum = Transaction::where('transaction_type', 'sale')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('total');
            $data[] = (float) $sum;
        }

        // --- Transactions per status (donut chart) ---
        $byStatus = Transaction::select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')->get()->mapWithKeys(function ($r) {
                return [$r->status => (int)$r->cnt];
            });

        // --- Latest Products (top 10) ---
        $latestProducts = Product::select(
            'product_code',
            'product_name',
            'stock',
            'selling_price',
            'created_at'
        )
        ->orderByDesc('created_at')
        ->limit(10)
        ->get();

        return response()->json([
            'totals' => [
                'transactions' => $totalTransactions,
                'users' => $totalUsers,
                'products' => $totalProducts,
                'warehouses' => $totalWarehouses,
                'suppliers' => $totalSuppliers,
                'categories' => $totalCategories,
                'sales_this_month' => (float) $salesThisMonth,
            ],
            'monthly' => [
                'labels' => $labels,
                'data' => $data,
            ],
            'by_status' => $byStatus,
            'latest_products' => $latestProducts,
        ]);
    }
}