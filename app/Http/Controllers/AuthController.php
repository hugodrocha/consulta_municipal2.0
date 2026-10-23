<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('consulta');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'usuario' => ['required', 'string'],
            'senha' => ['required', 'string'],
        ]);

        $user = User::query()->where('usuario', $dados['usuario'])->first();

        if ($user === null) {
            return back()->withErrors(['usuario' => 'Usuário não encontrado.'])->onlyInput('usuario');
        }

        if ($user->bloqueado) {
            return back()->withErrors(['usuario' => 'Usuário bloqueado.'])->onlyInput('usuario');
        }

        if (! Auth::attempt(['usuario' => $dados['usuario'], 'password' => $dados['senha']], $request->boolean('remember'))) {
            return back()->withErrors(['senha' => 'Senha incorreta.'])->onlyInput('usuario');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('consulta'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
