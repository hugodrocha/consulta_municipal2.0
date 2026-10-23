@extends('layouts.app')

@section('title', 'Trocar senha')

@section('content')
<div class="admin-page">
    <h1>Trocar senha</h1>

    @if($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('senha.update') }}" class="admin-form">
        @csrf
        <label>Senha atual
            <input type="password" name="senha_atual" required>
        </label>
        <label>Nova senha
            <input type="password" name="senha" required minlength="6">
        </label>
        <label>Confirmar nova senha
            <input type="password" name="senha_confirmation" required minlength="6">
        </label>
        <button type="submit" class="btn">Salvar</button>
    </form>
</div>
@endsection
