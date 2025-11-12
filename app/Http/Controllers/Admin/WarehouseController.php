<?php
// app/Http/Controllers/WarehouseController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        // kirim semua (client-side pagination)
        $warehouses = Warehouse::orderBy('id')->get();
        return view('admin.masterdata.warehouses', compact('warehouses'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'warehouse_code' => ['required','string','max:50','unique:warehouses,warehouse_code'],
            'warehouse_name' => ['required','string','max:150'],
            'address'        => ['nullable','string','max:255'],
            'note'           => ['nullable','string','max:255'],
        ]);
        $row = Warehouse::create($data);
        return response()->json(['message'=>'created','row'=>$row], 201);
    }

    public function update(Request $r, Warehouse $warehouse)
    {
        $data = $r->validate([
            'warehouse_code' => ['required','string','max:50', Rule::unique('warehouses','warehouse_code')->ignore($warehouse->id)],
            'warehouse_name' => ['required','string','max:150'],
            'address'        => ['nullable','string','max:255'],
            'note'           => ['nullable','string','max:255'],
        ]);
        $warehouse->update($data);
        return response()->json(['message'=>'updated','row'=>$warehouse->fresh()]);
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return response()->noContent(); // 204
    }
}
