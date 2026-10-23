@extends('layouts.app')

@section('title', $editando ? 'Editar usuário' : 'Novo usuário')

@section('content')
<div class="admin-page">
    <h1>{{ $editando ? 'Editar usuário' : 'Novo perfil de consulta' }}</h1>

    @if($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post"
          action="{{ $editando ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
          class="admin-form">
        @csrf
        @if($editando)
            @method('PUT')
        @endif

        <label>Nome completo
            <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required>
        </label>
        <label>Usuário (login)
            <input type="text" name="usuario" value="{{ old('usuario', $usuario->usuario) }}" required>
        </label>
        <label>E-mail (opcional)
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}">
        </label>
        <label>Senha {{ $editando ? '(deixe em branco para manter)' : '' }}
            <input type="password" name="password" {{ $editando ? '' : 'required' }} autocomplete="new-password">
        </label>
        <label>Perfil
            <select name="perfil" required>
                <option value="consulta" @selected(old('perfil', $usuario->perfil) === 'consulta')>consulta</option>
                <option value="superuser" @selected(old('perfil', $usuario->perfil) === 'superuser')>superuser</option>
            </select>
        </label>
        <label>Setor
            <input type="text" name="setor" value="{{ old('setor', $usuario->setor) }}">
        </label>
        @if($editando)
            <label class="check">
                <input type="checkbox" name="bloqueado" value="1" @checked(old('bloqueado', $usuario->bloqueado))>
                Bloqueado
            </label>
        @endif

        <div class="form-actions">
            <button type="submit" class="btn">Salvar</button>
            <a href="{{ route('usuarios.index') }}">Cancelar</a>
        </div>
    </form>
</div>
@endsection
