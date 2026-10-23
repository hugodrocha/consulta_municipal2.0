<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('senha.index', ['menu' => 'senha']);
    }

    public function update(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'senha_atual' => ['required', 'string'],
            'senha' => ['required', 'string', 'min:6', 'max:72', 'confirmed'],
        ]);

        $user = $request->user();
        if (! Hash::check($dados['senha_atual'], $user->password)) {
            return back()->withErrors(['senha_atual' => 'Senha atual incorreta.']);
        }

        $user->password = $dados['senha'];
        $user->save();

        return back()->with('sucesso', 'Senha alterada.');
    }
}
