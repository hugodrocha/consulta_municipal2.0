@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
<div class="admin-page">
    <div class="admin-head">
        <div>
            <h1>Gestão de usuários</h1>
            <p>Crie perfis de consulta. Apenas o superusuário gerencia acessos.</p>
        </div>
        <a class="btn" href="{{ route('usuarios.create') }}">Novo usuário</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Setor</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->usuario }}</td>
                        <td>{{ $u->perfil }}</td>
                        <td>{{ $u->setor ?: '—' }}</td>
                        <td>{{ $u->bloqueado ? 'Bloqueado' : 'Ativo' }}</td>
                        <td><a href="{{ route('usuarios.edit', $u) }}">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Nenhum usuário.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
