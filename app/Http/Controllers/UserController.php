<?php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private array $fallbackRoles = ['admin','warehouse','director'];

    public function index()
    {
        $users = User::select('id','name','email','role','email_verified_at','created_at','updated_at')
            ->orderBy('id','asc')
            ->get();

        // roles dari DB (enum), fallback ke 3 role
        $roles = $this->enumRoles();

        return view('admin.users.indexUser', compact('users','roles'));
    }

   // app/Http/Controllers/UserController.php
public function store(Request $request)
{
    $data = $request->validate([
        'name' => ['required','string','max:100'],
        'email' => ['required','email','max:150','unique:users,email'],
        'role' => ['required','in:admin,warehouse,director'],
        'password' => ['required','confirmed','min:6'],
    ]);

    try {
        \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => bcrypt($data['password']),
        ]);

        return back()->with('success','User berhasil dibuat.');
    } catch (\Throwable $e) {
        // kirim balik input + buka lagi modal
        return back()
          ->withInput()
          ->with('open_modal', true)
          ->with('error', 'Gagal menyimpan: '.$e->getMessage());
    }
}

public function update(Request $r, \App\Models\User $user)
{
    $r->validate([
        'name' => ['required','string','max:100'],
        'email' => ['required','email','max:150',"unique:users,email,{$user->id}"],
        'role' => ['required','in:admin,warehouse,director'],
        'password' => ['nullable','confirmed','min:6'],
    ]);

    try {
        $data = $r->only('name','email','role');
        if ($r->filled('password')) {
            $data['password'] = bcrypt($r->password);
        }
        $user->update($data);

        return back()->with('edit_success','User berhasil diperbarui.');
    } catch (\Throwable $e) {
        return back()
            ->withInput()
            ->with('edit_open_id',$user->id)
            ->with('edit_error','Gagal memperbarui: '.$e->getMessage());
    }
}

        public function bulkDestroy(\Illuminate\Http\Request $request)
        {
            $ids = $request->input('ids', []);
            if (!is_array($ids) || empty($ids)) {
                return response()->json(['message' => 'Tidak ada data yang dipilih.'], 422);
            }

            // Amankan: hanya angka & unik
            $ids = array_values(array_unique(array_filter($ids, fn($v) => is_numeric($v))));

            // (Opsional) Lindungi akun tertentu (misal superadmin id=1) & user yang sedang login
            $protected = [1]; // ubah sesuai kebutuhan
            if (auth()->check()) $protected[] = auth()->id();
            $ids = array_diff($ids, $protected);

            if (empty($ids)) {
                return response()->json(['message' => 'Data yang dipilih tidak valid atau dilindungi.'], 422);
            }

            try {
                \DB::transaction(function() use ($ids) {
                    // Dengan FK ON DELETE CASCADE di child tables, cukup hapus users
                    \App\Models\User::whereIn('id', $ids)->delete();
                });

                return response()->json(['message' => 'Data terpilih berhasil dihapus.']);
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Gagal menghapus: '.$e->getMessage()
                ], 500);
            }
        }


        public function destroy(\App\Models\User $user)
        {
            try {
                $user->delete();
                return response()->json(['message' => 'User deleted']);
            } catch (\Throwable $e) {
                return response('Gagal menghapus: '.$e->getMessage(), 500);
            }
        }



    /**
     * Helper: ambil enum roles dari DB atau fallback.
     */
    private function enumRoles(): array
    {
        $vals = $this->getEnumValues('users','role');
        return !empty($vals) ? $vals : $this->fallbackRoles;
    }

    /**
     * Ambil nilai enum dari kolom MySQL, hasil sudah bersih tanpa kutip.
     */
    private function getEnumValues(string $table, string $column): array
    {
        $col = DB::selectOne("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$column]);
        if (!$col || empty($col->Type)) return [];

        // enum('admin','warehouse','director') -> ['admin','warehouse','director']
        preg_match_all("/'([^']+)'/", $col->Type, $m);
        return $m[1] ?? [];
    }
}
