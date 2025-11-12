<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $me = auth()->user();
        $isWarehouse = $me && $me->role === 'warehouse';

        $query = User::with('warehouse')->orderBy('id');
        if ($isWarehouse) {
            $query->where(function($q) use ($me) {
                $q->where('id', $me->id)
                  ->orWhere('warehouse_id', $me->warehouse_id);
            });
        }
        $users        = $query->get();
        $warehouses   = Warehouse::select('id','warehouse_name')->orderBy('warehouse_name')->get();
        $allowedRoles = $isWarehouse ? ['sales'] : ['admin','warehouse','sales'];

        return view('admin.users.indexUser', compact('users','warehouses','allowedRoles','me'));
    }

    public function store(Request $r)
    {
        $me = auth()->user();
        $isWarehouse = $me && $me->role === 'warehouse';
        $allowedRoles = $isWarehouse ? ['sales'] : ['admin','warehouse','sales'];

        $data = $r->validate([
            'name'        => ['required','string','max:150'],
            'username'    => ['required','alpha_dash','max:150','unique:users,username'],
            'email'       => ['required','email','max:190','unique:users,email'],
            'password'    => ['required','confirmed','min:6'],
            'role'        => ['required', Rule::in($allowedRoles)],
            'warehouse_id'=> ['nullable','exists:warehouses,id'],
            'status'      => ['required', Rule::in(['active','inactive'])],
        ]);

        if ($isWarehouse) {
            // Warehouse user hanya boleh buat SALES di warehouse-nya sendiri
            $data['role'] = 'sales';
            $data['warehouse_id'] = $me->warehouse_id;
        } else {
            // Admin: kalau role = warehouse/sales, wajib pilih warehouse
            if (in_array($data['role'], ['warehouse','sales'], true)) {
                $r->validate(['warehouse_id' => ['required','exists:warehouses,id']]);
            } else {
                $data['warehouse_id'] = null; // admin pusat tidak nempel warehouse
            }
        }

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $r, User $user)
    {
        $me = auth()->user();
        $isWarehouse = $me && $me->role === 'warehouse';
        $allowedRoles = $isWarehouse ? ['sales'] : ['admin','warehouse','sales'];

        $data = $r->validate([
            'name'        => ['required','string','max:150'],
            'username'    => ['required','alpha_dash','max:150', Rule::unique('users','username')->ignore($user->id)],
            'email'       => ['required','email','max:190', Rule::unique('users','email')->ignore($user->id)],
            'password'    => ['nullable','confirmed','min:6'],
            'role'        => ['required', Rule::in($allowedRoles)],
            'warehouse_id'=> ['nullable','exists:warehouses,id'],
            'status'      => ['required', Rule::in(['active','inactive'])],
        ]);

        if (empty($data['password'])) unset($data['password']); else $data['password'] = Hash::make($data['password']);

        if ($isWarehouse) {
            $data['role'] = 'sales';
            $data['warehouse_id'] = $me->warehouse_id;
        } else {
            if (in_array($data['role'], ['warehouse','sales'], true)) {
                $r->validate(['warehouse_id' => ['required','exists:warehouses,id']]);
            } else {
                $data['warehouse_id'] = null;
            }
        }

        $user->update($data);
        return back()->with('edit_success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $me = auth()->user();
        if ($me && $me->id === $user->id) {
            return response()->json(['error' => "You can't delete yourself."], 422);
        }
        $user->delete();
        return response()->json(['success' => 'User deleted.']);
    }

    public function bulkDestroy(Request $r)
    {
        $me = auth()->user();
        $ids = $r->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','distinct','exists:users,id'],
        ])['ids'];

        // cegah hapus diri sendiri
        $ids = array_values(array_filter($ids, fn($id) => $id !== ($me?->id)));

        DB::transaction(function() use ($ids) {
            User::whereIn('id',$ids)->delete();
        });

        return response()->json(['success' => 'Selected users deleted.']);
    }
}
