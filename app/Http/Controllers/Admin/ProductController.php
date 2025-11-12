<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private string $codePrefix = 'PRD-';

    public function index()
    {
        $categories = Category::select('id','category_name')->orderBy('category_name')->get();
        $suppliers  = Supplier::select('id','name')->orderBy('name')->get();
        $packages   = Package::select('id','package_name')->orderBy('package_name')->get();
        $nextProductCode = $this->generateNextCode();

        return view('admin.masterdata.products', compact('categories','suppliers','packages','nextProductCode'));
    }

    public function datatable(Request $request)
    {
        try {
            $draw        = (int) $request->input('draw', 1);
            $start       = (int) $request->input('start', 0);
            $length      = (int) $request->input('length', 10);
            $orderColIdx = (int) $request->input('order.0.column', 1);
            $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
            $search      = trim((string) $request->input('search.value', ''));

            // ==== STOCK SEMENTARA: pakai MIN STOCK agar konsisten ====
            // NOTE: Kalau mau pakai stock_levels nanti, ganti baris total_stock di select() di bawah:
            // DB::raw('COALESCE(st.total_stock, COALESCE(p.stock_minimum,0)) AS total_stock')
            // 1) Aktifkan subquery:
//    $stockSub = DB::table('stock_levels as sl')
//        ->selectRaw("sl.product_id, SUM(sl.quantity) AS total_stock")
//        ->groupBy('sl.product_id');

// 2) Join subquery dan ubah field ini:
// DB::raw('COALESCE(st.total_stock, COALESCE(p.stock_minimum,0)) AS total_stock')


            $q = DB::table('products as p')
                ->leftJoin('categories as c','c.id','=','p.category_id')
                ->leftJoin('packages  as g','g.id','=','p.package_id')
                ->leftJoin('suppliers as s','s.id','=','p.supplier_id')
                ->select([
                    'p.id','p.product_code','p.name',
                    'p.category_id','p.package_id','p.supplier_id',
                    'p.description','p.purchasing_price','p.selling_price','p.stock_minimum',
                    DB::raw('COALESCE(p.stock_minimum,0) AS total_stock'), // <<— di sini
                    'c.category_name',
                    DB::raw('g.package_name AS package_name'),
                    DB::raw('s.name AS supplier_name'),
                ]);

            if ($search !== '') {
                $q->where(function($w) use ($search){
                    $w->where('p.product_code','like',"%{$search}%")
                      ->orWhere('p.name','like',"%{$search}%")
                      ->orWhere('c.category_name','like',"%{$search}%")
                      ->orWhere('g.package_name','like',"%{$search}%")
                      ->orWhere('s.name','like',"%{$search}%")
                      ->orWhere('p.description','like',"%{$search}%");
                });
            }

            $orderMap = [
                1 => 'p.product_code',
                2 => 'p.name',
                3 => 'c.category_name',
                4 => 'g.package_name',
                5 => 's.name',
                6 => 'p.description',
                7 => 'total_stock',
                8 => 'p.purchasing_price',
                9 => 'p.selling_price',
            ];
            $orderCol = $orderMap[$orderColIdx] ?? 'p.product_code';

            $recordsTotal    = DB::table('products')->count();
            $recordsFiltered = (clone $q)->select('p.id')->distinct()->count('p.id');

            if ($orderCol === 'total_stock') $q->orderByRaw('total_stock '.$orderDir);
            else $q->orderBy($orderCol, $orderDir);

            $data = $q->offset($start)->limit($length)->get();

            $rows = $data->map(function($p,$i) use ($start){
                $actions = sprintf(
                    '<div class="d-flex gap-1">
                        <button class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                            data-id="%1$d"
                            data-product_code="%2$s"
                            data-name="%3$s"
                            data-category_id="%4$s"
                            data-package_id="%5$s"
                            data-supplier_id="%6$s"
                            data-description="%7$s"
                            data-purchasing_price="%8$d"
                            data-selling_price="%9$d"
                            data-stock_minimum="%10$s">
                            <i class="bx bx-edit-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-outline-danger js-del" data-id="%1$d">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>',
                    $p->id, e($p->product_code), e($p->name),
                    $p->category_id ?? '', $p->package_id ?? '', $p->supplier_id ?? '',
                    e($p->description ?? ''), (int)$p->purchasing_price, (int)$p->selling_price, e($p->stock_minimum ?? '')
                );

                return [
                    'rownum'           => $start + $i + 1,
                    'product_code'     => e($p->product_code),
                    'name'             => e($p->name),
                    'category'         => e($p->category_name ?? '-'),
                    'package'          => e($p->package_name ?? '-'),
                    'supplier'         => e($p->supplier_name ?? '-'),
                    'description'      => e(Str::limit($p->description ?? '-', 80)),
                    'total_stock'      => number_format((int)$p->total_stock, 0, ',', '.'),
                    'purchasing_price' => 'Rp'.number_format((int)$p->purchasing_price, 0, ',', '.'),
                    'selling_price'    => 'Rp'.number_format((int)$p->selling_price, 0, ',', '.'),
                    'actions'          => $actions,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('DT Products error: '.$e->getMessage());
            return response()->json([
                'draw' => (int)$request->input('draw',1),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $code = strtoupper(trim((string)$request->input('product_code','')));
        if ($code === '') $code = $this->generateNextCode();
        $request->merge(['product_code' => $code]);

        $data = $request->validate([
            'product_code'     => ['required','max:50','unique:products,product_code'],
            'name'             => ['required','max:150'],
            'category_id'      => ['required','exists:categories,id'],
            'package_id'       => ['nullable','exists:packages,id'],
            'supplier_id'      => ['nullable','exists:suppliers,id'],
            'description'      => ['nullable','string'],
            'purchasing_price' => ['required','integer','min:0'],
            'selling_price'    => ['required','integer','min:0'],
            'stock_minimum'    => ['nullable','integer','min:0'],
        ]);

        Product::create($data);
        return response()->json(['success' => 'Product created successfully.']);
    }

    public function update(Request $request, Product $product)
    {
        $code = strtoupper(trim((string)$request->input('product_code','')));
        if ($code === '') $code = $product->product_code;
        $request->merge(['product_code' => $code]);

        $data = $request->validate([
            'product_code'     => ['required','max:50', Rule::unique('products','product_code')->ignore($product->id)],
            'name'             => ['required','max:150'],
            'category_id'      => ['required','exists:categories,id'],
            'package_id'       => ['nullable','exists:packages,id'],
            'supplier_id'      => ['nullable','exists:suppliers,id'],
            'description'      => ['nullable','string'],
            'purchasing_price' => ['required','integer','min:0'],
            'selling_price'    => ['required','integer','min:0'],
            'stock_minimum'    => ['nullable','integer','min:0'],
        ]);

        $product->update($data);
        return response()->json(['success' => 'Product updated successfully.']);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => 'Product deleted successfully.']);
    }

    public function nextCode()
    {
        return response()->json(['next_code' => $this->generateNextCode()]);
    }

    private function generateNextCode(): string
    {
        $prefix = $this->codePrefix;
        $latest = Product::where('product_code','like',$prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(product_code, '.(strlen($prefix)+1).') AS UNSIGNED) DESC')
            ->value('product_code');

        $num = 0;
        if ($latest && preg_match('/^'.preg_quote($prefix,'/').'(\d+)$/i',$latest,$m)) $num = (int)$m[1];
        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}