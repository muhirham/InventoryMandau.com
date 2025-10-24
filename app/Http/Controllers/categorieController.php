<?php
// app/Http/Controllers/CategoryController.php
namespace App\Http\Controllers;

    use App\Models\Category;
    use Illuminate\Http\Request;
    use Yajra\DataTables\Facades\DataTables;


    class categorieController extends Controller
    {
        public function index()
        {
            return view('admin.masterdata.categories'); // table + ajax only
        }

        public function datatable(Request $r)
        {
            $q = Category::query();

            return DataTables::of($q)
                ->filter(function($query) use ($r){
                    if ($s = $r->get('search')['value'] ?? null) {
                        $s = trim($s);
                        $query->where(function($w) use ($s){
                            $w->where('category_name', 'like', "%{$s}%")
                            ->orWhere('description', 'like', "%{$s}%");
                        });
                    }
                })
                ->editColumn('updated_at', fn($m) => optional($m->updated_at)->format('Y-m-d H:i'))
                ->addColumn('actions', function($m){
                    $editUrl   = route('categories.index').'?edit='.$m->id; // pakai panel kanan (no modal)
                    $deleteUrl = route('categories.destroy', $m);
                    $csrf = csrf_token();
                    return <<<HTML
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="{$editUrl}" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit-alt"></i></a>
                        <button class="btn btn-sm btn-outline-danger" onclick="delCategory({$m->id})">
                        <i class="bx bx-trash"></i>
                        </button>
                        <form id="del-form-{$m->id}" action="{$deleteUrl}" method="POST" class="d-none">
                        <input type="hidden" name="_token" value="{$csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        </form>
                    </div>
                    HTML;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        public function store(Request $r)
{
    $data = $r->validate([
        'category_name' => ['required','string','max:150'],
        'description'   => ['nullable','string','max:500'],
    ]);
    Category::create($data);

    if ($r->ajax()) {
        return response()->json(['success' => 'Kategori berhasil ditambahkan!']);
    }

    return back()->with('success', 'Kategori berhasil ditambahkan!');
}

public function update(Request $r, Category $category)
{
    $data = $r->validate([
        'category_name' => ['required','string','max:150'],
        'description'   => ['nullable','string','max:500'],
    ]);
    $category->update($data);

    if ($r->ajax()) {
        return response()->json(['success' => 'Kategori berhasil diperbarui!']);
    }

    return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui!');
}

public function destroy(Category $category)
{
    $category->delete();
    return response()->json(['success' => 'Kategori berhasil dihapus!']);
}
    }