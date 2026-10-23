<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'menu' => 'usuarios',
            'usuarios' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', [
            'menu' => 'usuarios',
            'usuario' => new User(['perfil' => User::PERFIL_CONSULTA]),
            'editando' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        User::query()->create([
            'name' => $dados['name'],
            'usuario' => $dados['usuario'],
            'email' => $dados['email'] ?? ($dados['usuario'].'@local'),
            'password' => $dados['password'],
            'perfil' => $dados['perfil'],
            'setor' => $dados['setor'] ?? null,
            'bloqueado' => false,
        ]);

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário criado.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', [
            'menu' => 'usuarios',
            'usuario' => $usuario,
            'editando' => true,
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $dados = $this->validar($request, $usuario);

        $usuario->name = $dados['name'];
        $usuario->usuario = $dados['usuario'];
        $usuario->email = $dados['email'] ?? $usuario->email;
        $usuario->perfil = $dados['perfil'];
        $usuario->setor = $dados['setor'] ?? null;
        $usuario->bloqueado = $request->boolean('bloqueado');

        if (! empty($dados['password'])) {
            $usuario->password = $dados['password'];
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário atualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?User $atual = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'usuario' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'usuario')->ignore($atual?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => [$atual ? 'nullable' : 'required', 'string', 'min:6', 'max:72'],
            'perfil' => ['required', Rule::in([User::PERFIL_SUPER, User::PERFIL_CONSULTA])],
            'setor' => ['nullable', 'string', 'max:100'],
            'bloqueado' => ['sometimes', 'boolean'],
        ]);
    }
}
