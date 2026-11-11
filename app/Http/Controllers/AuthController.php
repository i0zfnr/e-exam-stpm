<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identity = $credentials['identity'];
        $query = User::query()->where('email', $identity);

        if (Schema::hasColumn('users', 'username')) {
            $query->orWhere('username', $identity);
        }

        if (Schema::hasColumn('users', 'user_id')) {
            $query->orWhere('user_id', $identity);
        }

        $user = $query->first();

        if ($user && (! Schema::hasColumn('users', 'status') || $user->status === 'Aktif') && $this->verifyPassword($user, $credentials['password'])) {
            Auth::login($user, $request->boolean('remember'));
        } else {
            return back()->withErrors(['identity' => 'ID pengguna atau kata laluan tidak sah.'])->onlyInput('identity');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function verifyPassword(User $user, string $plain): bool
    {
        $stored = (string) $user->password;
        $info = password_get_info($stored);

        if ($info['algo'] !== null) {
            return Hash::check($plain, $stored);
        }

        if (! hash_equals($stored, $plain)) {
            return false;
        }

        $user->forceFill(['password' => Hash::make($plain)])->save();

        return true;
    }
}
