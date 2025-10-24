<?php

namespace App\Http\Controllers;

    use App\Models\Warehouse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Validation\Rule;

    class warehouseController extends Controller
    {

        public function index(Request $r)
        {
            if ($r->wantsJson()) {
                $rows = Warehouse::orderBy('id')->get();
                return response()->json(['ok'=>true, 'rows'=>$rows]);
            }

            // UI (seed awal — boleh kosong juga)
            $warehouses = Warehouse::orderBy('id')->limit(60)->get();
            return view('admin.masterdata.warehouses', compact('warehouses'));
        }

        /** SHOW (API JSON) */
        public function show(Warehouse $warehouse)
        {
            return response()->json(['ok' => true, 'row' => $warehouse]);
        }

        /** STORE (API JSON) */
        public function store(Request $r)
        {
            $v = Validator::make($r->all(), [
                'warehouse_code' => ['required','string','max:50','unique:warehouses,warehouse_code'],
                'warehouse_name' => ['required','string','max:150'],
                'address'        => ['nullable','string','max:255'],
                'note'           => ['nullable','string','max:255'],
            ]);

            if ($v->fails()) {
                return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
            }

            $row = Warehouse::create($v->validated());
            return response()->json(['ok'=>true,'row'=>$row], 201);
        }

        /** UPDATE (API JSON) */
        public function update(Request $r, Warehouse $warehouse)
        {
            $v = Validator::make($r->all(), [
                'warehouse_code' => [
                    'required','string','max:50',
                    Rule::unique('warehouses','warehouse_code')->ignore($warehouse->id)
                ],
                'warehouse_name' => ['required','string','max:150'],
                'address'        => ['nullable','string','max:255'],
                'note'           => ['nullable','string','max:255'],
            ]);

            if ($v->fails()) {
                return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
            }

            $warehouse->update($v->validated());
            return response()->json(['ok'=>true,'row'=>$warehouse->fresh()]);
        }

        /** DESTROY (API JSON) */
        public function destroy(Warehouse $warehouse)
        {
            $warehouse->delete();
            return response()->json(['ok'=>true]);
        }

        /** SEARCH (API JSON, opsional) */
        public function search(Request $r)
        {
            $q = trim((string)$r->query('q',''));
            $rows = Warehouse::query()
                ->when($q, function($w) use ($q) {
                    $like = "%{$q}%";
                    $w->where(function($x) use ($like){
                        $x->where('warehouse_code','like',$like)
                        ->orWhere('warehouse_name','like',$like)
                        ->orWhere('address','like',$like)
                        ->orWhere('note','like',$like);
                    });
                })
                ->orderBy('id','asc')
                ->limit(60)
                ->get();

            return response()->json(['ok'=>true,'rows'=>$rows]);
        }
    }