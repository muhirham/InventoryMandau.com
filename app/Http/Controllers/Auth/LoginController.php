<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Authenticat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        // Jika sudah login, redirect sesuai role
        if (Auth::check()) {
            $user = Auth::user();
            return match($user->role) {
                'admin' => redirect('/admin/indexAdmin'),
                'warehouse' => redirect('/welcome'),
                default => redirect('/login'),
            };
        }

        return view('auth.login'); // sesuaikan dengan blade lo
    }

    protected function throttleKey(Request $request)
    {
        return Str::lower($request->input('login')) . '|' . $request->ip();
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'login' => ['Too many login attempts. Please try again later.'],
            ])->status(429);
        }

        $login = $request->input('login');
        $password = $request->input('password');
        $remember = (bool) $request->filled('remember');

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [$field => $login, 'password' => $password];

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();
            return match($user->role) {
                'admin' => redirect()->intended('/admin/indexAdmin'),
                'warehouse' => redirect()->intended('/welcome'),
                default => redirect()->intended('/login'),
            };
        }

        RateLimiter::hit($throttleKey, 60);

        throw ValidationException::withMessages([
            'login' => ['Name/email atau password salah.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Kamu sudah logout.');
    }
}
