@extends('layouts.app')

@section('title', 'Cache')

@section('content')
<div class="admin-page">
    <h1>Atualizar cache</h1>
    <p>Armazena o histórico das Policlínicas localmente para a consulta ficar rápida. Escolha o período em meses.</p>

    <div class="bloco">
        <h3>Situação</h3>
        @if($ultima && $ultima->sucesso)
            <p>Última sincronização em {{ $ultima->terminado_em?->format('d/m/Y H:i') ?? '—' }}
                — {{ number_format((int) $ultima->linhas, 0, ',', '.') }} linha(s)
                (últimos {{ $ultima->janela_meses }} mês(es)).</p>
        @elseif($ultima)
            <p class="hint">Última tentativa falhou: {{ $ultima->mensagem }}</p>
        @else
            <p class="hint">Ainda não há cache. A consulta das Policlínicas ficará vazia até a primeira atualização.</p>
        @endif

        <form method="post" action="{{ route('cache.store') }}" class="admin-form">
            @csrf
            <label>Período a armazenar
                <select name="meses">
                    @foreach($opcoes as $m)
                        <option value="{{ $m }}" @selected((int) old('meses', $padrao) === (int) $m)>
                            Últimos {{ $m }} {{ $m === 1 ? 'mês' : 'meses' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn">Atualizar cache</button>
        </form>
        <p class="hint">A sincronização pode demorar vários minutos conforme o período escolhido.</p>
    </div>
</div>
@endsection
