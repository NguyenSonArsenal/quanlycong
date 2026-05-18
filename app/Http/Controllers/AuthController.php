<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
    public function showLogin() {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->role === 'admin') {
                return redirect('/staff-shift-kpi/kpi-config');
            }
            return redirect('/staff-shift-kpi/daily');
        }
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user && $user->role === 'admin') {
                return redirect('/staff-shift-kpi/kpi-config');
            }
            return redirect()->intended('/staff-shift-kpi/daily');
        }

        return back()->withErrors(['username' => 'Thông tin đăng nhập không chính xác.'])->withInput($request->only('username'));
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/staff-shift-kpi/login');
    }
}
