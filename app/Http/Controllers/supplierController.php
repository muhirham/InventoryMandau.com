<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class supplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('id','asc')->get();
        return view('admin.masterdata.suppliers', compact('suppliers'));
    }

    public function store(Request $r)
    {
        $v = Validator::make($r->all(), [
            'company_name'   => 'required|string|max:150',
            'address'        => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone_number'   => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:100',
            'bank_account'   => 'nullable|string|max:100',
        ]);

        if ($v->fails()) {
            return response()->json(['ok'=>false, 'errors'=>$v->errors()], 422);
        }

        try {
            $row = Supplier::create($v->validated());
            return response()->json(['ok'=>true, 'row'=>$row], 201);
        } catch (QueryException $e) {
            return response()->json(['ok'=>false, 'message'=>'Failed to create data'], 500);
        }
    }

    public function show(Supplier $supplier)
    {
        return response()->json(['ok'=>true, 'row'=>$supplier]);
    }

    public function update(Request $r, Supplier $supplier)
    {
        $v = Validator::make($r->all(), [
            'company_name'   => 'required|string|max:150',
            'address'        => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone_number'   => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:100',
            'bank_account'   => 'nullable|string|max:100',
        ]);

        if ($v->fails()) {
            return response()->json(['ok'=>false, 'errors'=>$v->errors()], 422);
        }

        try {
            $supplier->update($v->validated());
            return response()->json(['ok'=>true, 'row'=>$supplier->fresh()]);
        } catch (QueryException $e) {
            return response()->json(['ok'=>false, 'message'=>'Failed to update data'], 500);
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
            return response()->json(['ok'=>true]);
        } catch (QueryException $e) {
            return response()->json(['ok'=>false, 'message'=>'Cannot delete (in use)'], 409);
        }
    }

    public function search(Request $r)
    {
        $q = trim((string)$r->query('q',''));
        $rows = Supplier::query()
            ->when($q, fn($x)=>$x->where('company_name','like',"%$q%")
                                  ->orWhere('address','like',"%$q%")
                                  ->orWhere('contact_person','like',"%$q%"))
            ->orderBy('id','asc')
            ->limit(50)
            ->get();

        return response()->json(['rows'=>$rows]);
    }
}
