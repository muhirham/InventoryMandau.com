<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected int $perPage = 10;

    public function index(Request $request): View
    {
        $q = trim((string)$request->query('q', ''));

        $query = Product::with(['category','supplier','warehouse'])
            ->select(
                'id','product_code','product_name','category_id','supplier_id','warehouse_id',
                'purchase_price','selling_price','stock','package_type','product_group',
                'registration_number','updated_at'
            );

        if ($q !== '') {
            $query->where(function($qq) use ($q) {
                $qq->where('product_code','like', "%{$q}%")
                   ->orWhere('product_name','like', "%{$q}%")
                   ->orWhere('package_type','like', "%{$q}%")
                   ->orWhere('product_group','like', "%{$q}%");
            })->orWhereHas('category', function($c) use ($q) {
                $c->where('category_name','like', "%{$q}%");
            })->orWhereHas('supplier', function($s) use ($q) {
                $s->where('company_name','like', "%{$q}%");
            })->orWhereHas('warehouse', function($w) use ($q) {
                $w->where('warehouse_name','like', "%{$q}%");
            });
        }

        $products = $query->orderBy('id','desc')->paginate($this->perPage)->withQueryString();

        // dropdown lists: alias label as "name" to simplify blade
        $categories = DB::table('categories')
            ->select('id', DB::raw('category_name as name'))
            ->orderBy('category_name')->get();

        $suppliers = DB::table('suppliers')
            ->select('id', DB::raw('company_name as name'))
            ->orderBy('company_name')->get();

        $warehouses = DB::table('warehouses')
            ->select('id', DB::raw('warehouse_name as name'))
            ->orderBy('warehouse_name')->get();

        return view('admin.masterdata.products', compact('products','categories','suppliers','warehouses'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'product_code'        => ['required','string','max:80'],
            'product_name'        => ['required','string','max:180'],
            'category_id'         => ['required','integer','exists:categories,id'],
            'supplier_id'         => ['required','integer','exists:suppliers,id'],
            'warehouse_id'        => ['required','integer','exists:warehouses,id'],
            'purchase_price'      => ['nullable','numeric','min:0'],
            'selling_price'       => ['nullable','numeric','min:0'],
            'stock'               => ['nullable','numeric','min:0'],
            'package_type'        => ['nullable','string','max:100'],
            'product_group'       => ['nullable','string','max:100'],
            'registration_number' => ['nullable','string','max:100'],
        ]);

        Product::create($data);
        return redirect()->route('products.index')->with('success','Produk berhasil ditambahkan.');
    }

    public function update(Request $r, Product $product)
    {
        $data = $r->validate([
            'product_code'        => ['required','string','max:80'],
            'product_name'        => ['required','string','max:180'],
            'category_id'         => ['required','integer','exists:categories,id'],
            'supplier_id'         => ['required','integer','exists:suppliers,id'],
            'warehouse_id'        => ['required','integer','exists:warehouses,id'],
            'purchase_price'      => ['nullable','numeric','min:0'],
            'selling_price'       => ['nullable','numeric','min:0'],
            'stock'               => ['nullable','numeric','min:0'],
            'package_type'        => ['nullable','string','max:100'],
            'product_group'       => ['nullable','string','max:100'],
            'registration_number' => ['nullable','string','max:100'],
        ]);

        $product->update($data);
        return redirect()->route('products.index')->with('success','Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success','Produk berhasil dihapus.');
    }

    /**
     * AJAX search with pagination. Returns JSON:
     * { data: [...], pagination: '<html>...</html>' }
     */
    public function search(Request $req): JsonResponse
    {
        $q = trim((string)$req->query('q',''));
        $page = max(1, (int)$req->query('page', 1));

        $query = Product::with(['category','supplier','warehouse'])
            ->select(
                'id','product_code','product_name','category_id','supplier_id','warehouse_id',
                'purchase_price','selling_price','stock','package_type','product_group',
                'registration_number','updated_at'
            );

        if ($q !== '') {
            $query->where(function($qq) use ($q) {
                $qq->where('product_code','like', "%{$q}%")
                   ->orWhere('product_name','like', "%{$q}%")
                   ->orWhere('package_type','like', "%{$q}%")
                   ->orWhere('product_group','like', "%{$q}%");
            })->orWhereHas('category', function($c) use ($q) {
                $c->where('category_name','like', "%{$q}%");
            })->orWhereHas('supplier', function($s) use ($q) {
                $s->where('company_name','like', "%{$q}%");
            })->orWhereHas('warehouse', function($w) use ($q) {
                $w->where('warehouse_name','like', "%{$q}%");
            });
        }

        $paginator = $query->orderBy('id','desc')->paginate($this->perPage, ['*'], 'page', $page);

        $items = $paginator->items();

        $data = collect($items)->map(function($p){
            return [
                'id' => $p->id,
                'product_code' => $p->product_code,
                'product_name' => $p->product_name,
                'product_group' => $p->product_group,
                'category_id' => $p->category_id,
                'supplier_id' => $p->supplier_id,
                'warehouse_id' => $p->warehouse_id,
                'category' => $p->category->category_name ?? ($p->category->name ?? '-'),
                'supplier' => $p->supplier->company_name ?? ($p->supplier->name ?? '-'),
                'warehouse' => $p->warehouse->warehouse_name ?? ($p->warehouse->name ?? '-'),
                'purchase_price' => (float) ($p->purchase_price ?? 0),
                'selling_price'  => (float) ($p->selling_price ?? 0),
                'stock' => (int) ($p->stock ?? 0),
                'package_type' => $p->package_type,
                'registration_number' => $p->registration_number,
            ];
        })->values()->all();

        // Render pagination HTML using bootstrap view (fall back to manual if links empty)
        $paginationHtml = $paginator->withQueryString()->links('pagination::bootstrap-5')->render();

        // if Laravel returns empty string for links for some reason, build small manual html
        if (!trim($paginationHtml)) {
            $last = $paginator->lastPage() ?: 1;
            $cur = $paginator->currentPage() ?: 1;
            $from = max(1, $cur - 3);
            $to = min($last, $cur + 3);

            $html = '<nav aria-label="Products pagination"><ul class="pagination">';
            $html .= '<li class="page-item '.($cur==1?'disabled':'').'"><a class="page-link" href="'.$paginator->url(1).'" data-page="1">&laquo;</a></li>';

            for ($i=$from;$i<=$to;$i++){
                if ($i==$cur) {
                    $html .= '<li class="page-item active" aria-current="page"><span class="page-link">'.$i.'</span></li>';
                } else {
                    $html .= '<li class="page-item"><a class="page-link" href="'.$paginator->url($i).'" data-page="'.$i.'">'.$i.'</a></li>';
                }
            }

            $html .= '<li class="page-item '.($cur==$last?'disabled':'').'"><a class="page-link" href="'.$paginator->url($last).'" data-page="'.$last.'">&raquo;</a></li>';
            $html .= '</ul></nav>';

            $paginationHtml = $html;
        }

        return response()->json([
            'data' => $data,
            'pagination' => $paginationHtml,
        ]);
    }
}