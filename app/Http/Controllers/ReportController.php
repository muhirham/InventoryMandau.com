<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('user')->latest()->get();
        return view('admin.reports.indexReports', compact('reports'));
    }

    /**
     * Generate report for types: stock (includes products master), restock, sales, purchases
     */
    public function generate(Request $req)
    {
        $req->validate([
            'report_type'  => 'required|in:sales,purchases,stock,restock',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $start = $req->period_start;
        $end = $req->period_end;
        $startDt = $start . ' 00:00:00';
        $endDt = $end . ' 23:59:59';
        $type = $req->report_type;

        $summary = [
            'type' => $type,
            'count' => 0,
            'total' => 0,
            'rows' => [],
            'meta' => [],
        ];

        try {
            if ($type === 'stock') {
                // PRODUCTS MASTER
                $productsRows = [];
                if (Schema::hasTable('products')) {
                    $prods = DB::table('products')
                        ->select('id','product_code','product_name','category_id','supplier_id','purchase_price','selling_price','package_type','stock')
                        ->get();

                    // Determine which column names exist in categories/suppliers/warehouses/users
                    $categoryCandidateCols = ['name','category_name','category'];
                    $supplierCandidateCols = ['name','supplier_name','company_name'];
                    $warehouseCandidateCols = ['warehouse_name','name'];

                    $categoryCol = $this->firstExistingColumn('categories', $categoryCandidateCols);
                    $supplierCol = $this->firstExistingColumn('suppliers', $supplierCandidateCols);

                    foreach ($prods as $p) {
                        $categoryName = null;
                        if ($categoryCol && $p->category_id) {
                            $categoryName = DB::table('categories')->where('id', $p->category_id)->value($categoryCol);
                        }

                        $supplierName = null;
                        if ($supplierCol && $p->supplier_id) {
                            $supplierName = DB::table('suppliers')->where('id', $p->supplier_id)->value($supplierCol);
                        }

                        // compute stock total via product_stock if exists
                        $stockTotal = (float)($p->stock ?? 0);
                        if (Schema::hasTable('product_stock')) {
                            try {
                                $s = DB::table('product_stock')->where('product_id', $p->id)->sum('final_stock');
                                if ($s !== null) $stockTotal = (float)$s;
                            } catch (\Throwable $e) {
                                Log::debug("product_stock sum failed for product {$p->id}: ".$e->getMessage());
                            }
                        }

                        $productsRows[] = [
                            'code' => $p->product_code,
                            'product_name' => $p->product_name,
                            'category' => $categoryName,
                            'supplier' => $supplierName,
                            'stock' => $stockTotal,
                            'purchase' => (float)$p->purchase_price,
                            'selling' => (float)$p->selling_price,
                            'package' => $p->package_type,
                        ];
                    }
                } else {
                    $summary['meta'][] = 'products table not found';
                }

                // STOCK PER WAREHOUSE
                $stockRows = [];
                if (Schema::hasTable('product_stock')) {
                    // get warehouse display column
                    $warehouseCol = $this->firstExistingColumn('warehouses', ['warehouse_name','name']);

                    $stocks = DB::table('product_stock')
                        ->select('product_stock.id','product_stock.product_id','product_stock.initial_stock','product_stock.stock_in','product_stock.stock_out','product_stock.final_stock','product_stock.last_update')
                        ->get();

                    // We need product name & warehouse name per row -> fetch caches to minimize queries
                    $productIds = $stocks->pluck('product_id')->unique()->filter()->values()->all();
                    $productMap = [];
                    if (!empty($productIds) && Schema::hasTable('products')) {
                        $productMap = DB::table('products')->whereIn('id', $productIds)->pluck('product_name','id')->toArray();
                    }

                    // product_stock table has warehouse_id, but we didn't select it above - include now:
                    // Re-fetch including warehouse_id to avoid ambiguity
                    $stocks2 = DB::table('product_stock')
                        ->leftJoin('warehouses','warehouses.id','=','product_stock.warehouse_id')
                        ->leftJoin('products','products.id','=','product_stock.product_id')
                        ->select(
                            'product_stock.id as id',
                            'product_stock.product_id as product_id',
                            DB::raw("COALESCE(products.product_name, '') as product_name"),
                            DB::raw($warehouseCol ? "COALESCE(warehouses.{$warehouseCol}, '')" : "'' as warehouse_name"),
                            'product_stock.initial_stock','product_stock.stock_in','product_stock.stock_out','product_stock.final_stock','product_stock.last_update'
                        )
                        ->get();

                    foreach ($stocks2 as $s) {
                        $stockRows[] = [
                            'id' => $s->id,
                            'product_id' => $s->product_id,
                            'product_name' => $s->product_name ?? ($productMap[$s->product_id] ?? null),
                            'warehouse' => $s->warehouse_name ?? '',
                            'initial' => (float)$s->initial_stock,
                            'in' => (float)$s->stock_in,
                            'out' => (float)$s->stock_out,
                            'final' => (float)$s->final_stock,
                            'last_update' => $s->last_update,
                        ];
                    }
                } else {
                    $summary['meta'][] = 'product_stock table not found';
                }

                $summary['rows'] = [
                    'products_master' => $productsRows,
                    'stock_per_warehouse' => $stockRows,
                ];
                $summary['count'] = count($productsRows) + count($stockRows);
                $summary['total'] = array_sum(array_map(fn($r)=> ($r['stock'] * $r['selling']), $productsRows));
            }

            elseif ($type === 'restock') {
                // Approved restock requests
                if (!Schema::hasTable('restock_requests')) {
                    $summary['meta'][] = 'restock_requests table not found';
                } else {
                    $hasRequestDate = Schema::hasColumn('restock_requests','request_date');

                    // fetch restock rows raw
                    $restocks = DB::table('restock_requests')
                        ->select('*')
                        ->where('status','approved')
                        ->when($hasRequestDate, function($q) use ($start, $end) {
                            $q->whereBetween('request_date', [$start, $end]);
                        }, function($q) use ($startDt, $endDt) {
                            $q->whereBetween('created_at', [$startDt, $endDt]);
                        })
                        ->get();

                    $rows = [];
                    // determine supplier/warehouse/user column names
                    $supplierCol = $this->firstExistingColumn('suppliers',['name','supplier_name','company_name']);
                    $warehouseCol = $this->firstExistingColumn('warehouses',['warehouse_name','name']);
                    $userCol = $this->firstExistingColumn('users',['name','username']);

                    foreach ($restocks as $r) {
                        $productName = null;
                        if (Schema::hasTable('products') && $r->product_id) {
                            $productName = DB::table('products')->where('id',$r->product_id)->value('product_name');
                        }

                        $supplierName = null;
                        if ($supplierCol && $r->supplier_id) {
                            $supplierName = DB::table('suppliers')->where('id',$r->supplier_id)->value($supplierCol);
                        }

                        $warehouseName = null;
                        if ($warehouseCol && $r->warehouse_id) {
                            $warehouseName = DB::table('warehouses')->where('id',$r->warehouse_id)->value($warehouseCol);
                        }

                        $requestedBy = null;
                        if ($userCol && $r->user_id) {
                            $requestedBy = DB::table('users')->where('id',$r->user_id)->value($userCol);
                        }

                        $rows[] = [
                            'id' => $r->id,
                            'date' => $hasRequestDate ? $r->request_date : $r->created_at,
                            'product' => $productName,
                            'supplier' => $supplierName,
                            'warehouse' => $warehouseName,
                            'qty' => (float)$r->quantity_requested,
                            'total_cost' => (float)$r->total_cost,
                            'status' => $r->status,
                            'description' => $r->description,
                            'requested_by' => $requestedBy,
                        ];
                    }

                    $summary['rows'] = $rows;
                    $summary['count'] = count($rows);
                    $summary['total'] = array_sum(array_column($rows,'total_cost'));
                }
            }

            else { // sales / purchases
                $statusAllowed = ['completed'];
                $typeMap = ['sales'=>'sale','purchases'=>'purchase'];
                $txType = $typeMap[$type] ?? $type;

                $txs = DB::table('transactions')
                    ->leftJoin('users','users.id','=','transactions.user_id')
                    ->leftJoin('warehouses','warehouses.id','=','transactions.warehouse_id')
                    ->select('transactions.id','transactions.transaction_date','transactions.transaction_type','transactions.status','transactions.total','transactions.user_id','transactions.warehouse_id')
                    ->where('transactions.transaction_type',$txType)
                    ->whereIn('transactions.status',$statusAllowed)
                    ->whereBetween('transactions.transaction_date',[$startDt,$endDt])
                    ->get();

                // resolve names defensively
                $userCol = $this->firstExistingColumn('users',['name','username']);
                $warehouseCol = $this->firstExistingColumn('warehouses',['warehouse_name','name']);

                $rows = [];
                foreach ($txs as $t) {
                    $userName = null;
                    if ($userCol && $t->user_id) $userName = DB::table('users')->where('id',$t->user_id)->value($userCol);
                    $warehouseName = null;
                    if ($warehouseCol && $t->warehouse_id) $warehouseName = DB::table('warehouses')->where('id',$t->warehouse_id)->value($warehouseCol);

                    $rows[] = [
                        'id' => $t->id,
                        'user' => $userName,
                        'warehouse' => $warehouseName,
                        'date' => $t->transaction_date,
                        'type' => $t->transaction_type,
                        'status' => $t->status,
                        'total' => (float)$t->total,
                    ];
                }

                $summary['rows'] = $rows;
                $summary['count'] = count($rows);
                $summary['total'] = array_sum(array_column($rows,'total'));
            }

            // create report
            $report = Report::create([
                'report_type' => $type,
                'user_id' => Auth::id(),
                'period_start' => $start,
                'period_end' => $end,
                'summary' => $summary,
                'created_at' => now(),
            ]);

            if (empty($summary['rows'])) {
                return redirect()->route('reports.index')->with('success','Report saved but no data matched the filters.')->with('report_debug',$summary['meta']);
            }

            return redirect()->route('reports.index')->with('success','Report generated successfully!');
        } catch (\Throwable $e) {
            Log::error("ReportController::generate failed ({$type}): ".$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            $summary['meta']['exception'] = $e->getMessage();
            try {
                Report::create([
                    'report_type' => $type,
                    'user_id' => Auth::id(),
                    'period_start' => $start,
                    'period_end' => $end,
                    'summary' => $summary,
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e2) {
                Log::error('Also failed to save error-report: '.$e2->getMessage());
            }
            return redirect()->route('reports.index')->with('error','Failed to generate report (see logs).')->with('report_debug',$summary['meta']);
        }
    }

    /**
     * Utility: return first existing column from candidates on a table, or null.
     */
    protected function firstExistingColumn(string $table, array $candidates)
    {
        if (!Schema::hasTable($table)) return null;
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) return $col;
        }
        return null;
    }

    /**
     * Export CSV
     */
    public function exportCsv($id)
    {
        $report = Report::with('user')->findOrFail($id);
        $summary = $report->summary ?? [];
        $type = $report->report_type ?? 'report';
        $filename = "report_{$type}_{$report->id}.csv";

        $response = new StreamedResponse(function() use ($report, $summary, $type) {
            $handle = fopen('php://output','w');

            // header meta
            fputcsv($handle, ['Report ID','Type','Period Start','Period End','Generated By','Created At']);
            $createdAt = optional($report->created_at) ? Carbon::parse($report->created_at)->format('n/j/Y G:i') : '-';
            $ps = optional($report->period_start) ? Carbon::parse($report->period_start)->format('n/j/Y') : '-';
            $pe = optional($report->period_end) ? Carbon::parse($report->period_end)->format('n/j/Y') : '-';
            fputcsv($handle, [$report->id, ucfirst($type), $ps, $pe, $report->user->name ?? '-', $createdAt]);
            fputcsv($handle, []);

            if ($type === 'stock') {
                // products master
                fputcsv($handle, ['Products master']);
                fputcsv($handle, ['CODE','PRODUCT NAME','CATEGORY','SUPPLIER','STOCK','PURCHASE','SELLING','PACKAGE']);
                $pm = $summary['rows']['products_master'] ?? [];
                foreach ($pm as $r) {
                    fputcsv($handle, [
                        $r['code'] ?? '',
                        $r['product_name'] ?? '',
                        $r['category'] ?? '',
                        $r['supplier'] ?? '',
                        $r['stock'] ?? 0,
                        $r['purchase'] ?? 0,
                        $r['selling'] ?? 0,
                        $r['package'] ?? '',
                    ]);
                }
                fputcsv($handle, []);
                // stock per warehouse
                fputcsv($handle, ['Stock per Warehouse']);
                fputcsv($handle, ['ID','Product ID','Product Name','Warehouse','Initial','In','Out','Final','Last Update']);
                $stk = $summary['rows']['stock_per_warehouse'] ?? [];
                foreach ($stk as $r) {
                    fputcsv($handle, [
                        $r['id'] ?? '',
                        $r['product_id'] ?? '',
                        $r['product_name'] ?? '',
                        $r['warehouse'] ?? '',
                        $r['initial'] ?? 0,
                        $r['in'] ?? 0,
                        $r['out'] ?? 0,
                        $r['final'] ?? 0,
                        $r['last_update'] ?? '',
                    ]);
                }
            } elseif ($type === 'restock') {
                fputcsv($handle, ['ID','Date','Product','Supplier','Warehouse','Qty','Total Cost','Status','Description','Requested By']);
                $rows = $summary['rows'] ?? [];
                foreach ($rows as $r) {
                    $dateOut = $r['date'] ? (Carbon::parse($r['date'])->format('n/j/Y')) : '';
                    fputcsv($handle, [
                        $r['id'] ?? '',
                        $dateOut,
                        $r['product'] ?? '',
                        $r['supplier'] ?? '',
                        $r['warehouse'] ?? '',
                        $r['qty'] ?? 0,
                        $r['total_cost'] ?? 0,
                        $r['status'] ?? '',
                        $r['description'] ?? '',
                        $r['requested_by'] ?? '',
                    ]);
                }
            } else { // transactions
                fputcsv($handle, ['ID','User','Warehouse','Date','Type','Status','Total']);
                $rows = $summary['rows'] ?? [];
                foreach ($rows as $r) {
                    $dateOut = $r['date'] ? (Carbon::parse($r['date'])->format('n/j/Y G:i')) : '';
                    fputcsv($handle, [
                        $r['id'] ?? '',
                        $r['user'] ?? '',
                        $r['warehouse'] ?? '',
                        $dateOut,
                        $r['type'] ?? '',
                        $r['status'] ?? '',
                        $r['total'] ?? 0,
                    ]);
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type','text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="'.$filename.'"');

        return $response;
    }

    public function exportPrintable($id)
    {
        $report = Report::with('user')->findOrFail($id);
        $summary = $report->summary ?? [];
        $rows = $summary['rows'] ?? [];
        return view('reports.export_printable', compact('report','summary','rows'));
    }
}
