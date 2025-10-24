<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        // Eager load tanpa batasi kolom (biar gak minta 'name')
        $products = Product::with(['category','supplier','warehouse'])
            ->select(
                'id','product_code','product_name','category_id','supplier_id','warehouse_id',
                'purchase_price','selling_price','stock','package_type','product_group',
                'registration_number','updated_at'
            )
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        // Dropdown: alias-kan kolom label ke 'name' supaya blade simpel
        $categories = DB::table('categories')
            ->select('id', DB::raw('category_name as name'))   // kolom asli: category_name
            ->orderBy('category_name')->get();                 // ✔ categories.category_name

        $suppliers = DB::table('suppliers')
            ->select('id', DB::raw('company_name as name'))    // kolom asli: company_name
            ->orderBy('company_name')->get();                  // ✔ suppliers.company_name

        $warehouses = DB::table('warehouses')
            ->select('id', DB::raw('warehouse_name as name'))  // kolom asli: warehouse_name
            ->orderBy('warehouse_name')->get();                // ✔ warehouses.warehouse_name

        return view('admin.masterdata.products', compact('products','categories','suppliers','warehouses'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'product_code'        => ['required','string','max:80'],
            'product_name'        => ['required','string','max:180'],
            // FK di DB NOT NULL → required
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

        public function search(Request $req)
    {
        $q = trim($req->q ?? '');

        $products = \App\Models\Product::with(['category','supplier','warehouse'])
            ->when($q, function($qr) use ($q) {
                $qr->where(function($w) use ($q) {
                    $w->where('product_code','like',"%$q%")
                    ->orWhere('product_name','like',"%$q%")
                    ->orWhere('package_type','like',"%$q%")
                    ->orWhere('product_group','like',"%$q%");
                })
                // gunakan nama kolom asli di tabel relasi
                ->orWhereHas('category',  fn($c)=>$c->where('category_name','like',"%$q%"))
                ->orWhereHas('supplier',  fn($s)=>$s->where('company_name','like',"%$q%"))
                ->orWhereHas('warehouse', fn($w)=>$w->where('warehouse_name','like',"%$q%"));
            })
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $products->map(function($p){
                return [
                    'id'          => $p->id,
                    'product_code'=> $p->product_code,
                    'product_name'=> $p->product_name,
                    'category_id' => $p->category_id,
                    'supplier_id' => $p->supplier_id,
                    'warehouse_id'=> $p->warehouse_id,
                    'category'    => $p->category->category_name  ?? ($p->category->name  ?? null),
                    'supplier'    => $p->supplier->company_name   ?? ($p->supplier->name ?? null),
                    'warehouse'   => $p->warehouse->warehouse_name?? ($p->warehouse->name?? null),
                    'purchase_price' => (float)($p->purchase_price ?? 0),
                    'selling_price'  => (float)($p->selling_price  ?? 0),
                    'stock'          => (int)  ($p->stock          ?? 0),
                    'package_type'   => $p->package_type,
                    'product_group'  => $p->product_group,
                    'registration_number' => $p->registration_number,
                ];
            })->values(),
        ]);
    }

}