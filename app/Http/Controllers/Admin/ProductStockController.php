<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ProductStockController extends Controller
{
    /** Halaman Stock Product (All Item / Central) */
    public function index()
    {
        // Blade: resources/views/admin/operations/stockProducts.blade.php
        return view('admin.operations.stockProducts');
    }

    /** DataTables server-side untuk Stock Product (All Item / Central) */
    public function datatable(Request $r)
    {
        try {
            if (!Schema::hasTable('products')) {
                return response()->json([
                    'draw'            => (int) $r->input('draw', 1),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => 'Tabel products belum ada.',
                ]);
            }

            // ==== Param DataTables ====
            $draw        = (int) $r->input('draw', 1);
            $start       = (int) $r->input('start', 0);
            $length      = (int) $r->input('length', 10);
            $orderColIdx = (int) $r->input('order.0.column', 1);
            $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
            $search      = trim((string) $r->input('search.value', ''));

            $hasCategories = Schema::hasTable('categories');
            $hasSuppliers  = Schema::hasTable('suppliers');
            $hasStock      = Schema::hasTable('stock_levels');

            $base = DB::table('products as p');

            /*
             * ===========================
             *  STOK DARI stock_levels
             * ===========================
             * Kita ambil stok CENTRAL (superadmin) = owner_type = 'pusat'
             * owner_id = 0 (sesuai seeder yang sudah kita buat).
             */

            $stockExpr = '0';

            if ($hasStock) {
                $stockSub = DB::table('stock_levels as sl')
                    ->selectRaw('sl.product_id, SUM(sl.quantity) as qty_stock')
                    ->where('sl.owner_type', 'pusat')
                    ->groupBy('sl.product_id');

                $base->leftJoinSub($stockSub, 'st', 'st.product_id', '=', 'p.id');
                $stockExpr = 'COALESCE(st.qty_stock, 0)';
            }

            /*
             * ===========================
             *  KATEGORI & SUPPLIER
             * ===========================
             */

            $catNameExpr = "'-'";
            if ($hasCategories) {
                $base->leftJoin('categories as c', 'c.id', '=', 'p.category_id');

                if (Schema::hasColumn('categories', 'category_name')) {
                    $catNameExpr = "COALESCE(c.category_name,'-')";
                } elseif (Schema::hasColumn('categories', 'name')) {
                    $catNameExpr = "COALESCE(c.name,'-')";
                }
            }

            $supNameExpr = "'-'";
            if ($hasSuppliers && Schema::hasColumn('products', 'supplier_id')) {
                $base->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id');

                if (Schema::hasColumn('suppliers', 'name')) {
                    $supNameExpr = "COALESCE(s.name,'-')";
                } elseif (Schema::hasColumn('suppliers', 'supplier_name')) {
                    $supNameExpr = "COALESCE(s.supplier_name,'-')";
                }
            }

            /*
             * ===========================
             *  SELECT UTAMA
             * ===========================
             */

            $base->select([
                DB::raw('p.id'),
                DB::raw('p.product_code'),
                DB::raw('p.name as product_name'),
                DB::raw('COALESCE(p.stock_minimum,0) as stock_minimum'),
                DB::raw("$stockExpr as total_qty"),
                DB::raw("$catNameExpr as category_name"),
                DB::raw("$supNameExpr as supplier_name"),
            ]);

            /*
             * ===========================
             *  SEARCH
             * ===========================
             */

            if ($search !== '') {
                $like = "%{$search}%";
                $base->where(function ($q) use ($like, $hasCategories, $hasSuppliers) {
                    $q->where('p.product_code', 'like', $like)
                      ->orWhere('p.name', 'like', $like);

                    if ($hasCategories) {
                        if (Schema::hasColumn('categories', 'category_name')) {
                            $q->orWhere('c.category_name', 'like', $like);
                        } elseif (Schema::hasColumn('categories', 'name')) {
                            $q->orWhere('c.name', 'like', $like);
                        }
                    }

                    if ($hasSuppliers) {
                        if (Schema::hasColumn('suppliers', 'name')) {
                            $q->orWhere('s.name', 'like', $like);
                        } elseif (Schema::hasColumn('suppliers', 'supplier_name')) {
                            $q->orWhere('s.supplier_name', 'like', $like);
                        }
                    }
                });
            }

            $recordsTotal    = DB::table('products')->count();
            $recordsFiltered = (clone $base)->count();

            /*
             * ===========================
             *  ORDERING
             * ===========================
             * index kolom DataTables:
             * 0 = rownum
             * 1 = product_code
             * 2 = product_name
             * 3 = category_name
             * 4 = supplier_name
             * 5 = stock
             * 6 = min_stock
             * 7 = status
             */

            $orderMap = [
                1 => 'p.product_code',
                2 => 'product_name',
                3 => 'category_name',
                4 => 'supplier_name',
                5 => 'total_qty',
                6 => 'stock_minimum',
                7 => 'p.product_code',
            ];
            $orderCol = $orderMap[$orderColIdx] ?? 'p.product_code';

            $base->orderBy($orderCol, $orderDir);

            /*
             * ===========================
             *  PAGING + RENDER
             * ===========================
             */

            $rows = $base->skip($start)->take($length)->get()
                ->map(function ($r, $idx) use ($start) {
                    $qty = max((int)($r->total_qty ?? 0), 0);
                    $min = (int)($r->stock_minimum ?? 0);

                    $isLow = $min > 0 && $qty <= $min;

                    $statusBadge = '-';
                    if ($min > 0) {
                        $statusBadge = $isLow
                            ? '<span class="badge bg-danger">LOW</span>'
                            : '<span class="badge bg-success">OK</span>';
                    }

                    $stockHtml = $isLow
                        ? '<span class="text-danger fw-bold">' . number_format($qty, 0, ',', '.') . '</span>'
                        : number_format($qty, 0, ',', '.');

                    return [
                        'rownum'        => $start + $idx + 1,
                        'product_code'  => e($r->product_code),
                        'product_name'  => e($r->product_name),
                        'category_name' => e($r->category_name ?? '-'),
                        'supplier_name' => e($r->supplier_name ?? '-'),
                        'stock'         => $stockHtml,
                        'min_stock'     => number_format($min, 0, ',', '.'),
                        'status'        => $statusBadge,
                    ];
                });

            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProductStock.datatable error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'draw'            => (int) $r->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Server error: ' . $e->getMessage(),
            ]);
        }
    }
}
