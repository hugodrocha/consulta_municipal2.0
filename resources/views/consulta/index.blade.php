@extends('layouts.app')

@section('title', 'Consulta Municipal')

@section('content')
@php
    $txt = static function ($v): string {
        $s = trim((string) ($v ?? ''));
        return $s === '' ? '—' : $s;
    };
    $pecCount = count($pec_atendimentos);
    $poliCount = count($poli_rows);
@endphp

<div class="consulta-page">
    <section class="busca-card">
        <h1>Consulta Municipal</h1>
        <p class="busca-sub">Pesquise por CPF, CNS ou nome do paciente.</p>

        <form method="get" action="{{ route('consulta') }}" class="busca-form">
            <input type="text" name="q" value="{{ $q }}" placeholder="CPF, CNS ou nome" autocomplete="off" autofocus>
            <button type="submit">Consultar</button>
        </form>

        @if($q !== '')
            <p class="busca-meta">
                Base PEC: <strong>{{ $pecCount }}</strong> registro(s)
                — Policlínicas: <strong>{{ $poliCount }}</strong> registro(s)
            </p>
        @endif
    </section>

    @if($pec_erro)
        <div class="alert err">{{ $pec_erro }}</div>
    @endif
    @if($poli_erro)
        <div class="alert err">{{ $poli_erro }}</div>
    @endif

    @if($q !== '')
        @if($pecCount === 0 && $poliCount === 0 && !$pec_erro && !$poli_erro)
            <p class="empty">Nenhum atendimento encontrado para esse critério.</p>
            @if(($poli_fonte ?? '') === 'sem_cache')
                <p class="hint">O histórico das Policlínicas depende do cache. Peça ao superusuário para atualizar o cache.</p>
            @elseif(($poli_fonte ?? '') === 'vazio')
                <p class="hint">A conexão com o e-SUS foi consultada, mas não há registro para este paciente com os dados informados.</p>
            @endif
        @else
            <section class="bloco">
                <div class="bloco-head">
                    <h2>Base PEC</h2>
                    <span>{{ $pecCount }} registro(s)</span>
                </div>

                @if($paciente)
                    <div class="ident-grid">
                        <div><span>Nome do paciente</span><strong>{{ $txt($paciente['nome'] ?? '') }}</strong></div>
                        <div><span>Nome da mãe</span><strong>{{ $txt($paciente['nome_mae'] ?? '') }}</strong></div>
                        <div><span>Telefone</span><strong>{{ $txt($paciente['telefone'] ?? '') }}</strong></div>
                        <div><span>Data de nascimento</span><strong>{{ $txt($paciente['nascimento'] ?? '') }}</strong></div>
                        <div><span>CPF</span><strong>{{ $txt($paciente['cpf'] ?? '') }}</strong></div>
                        <div><span>CNS</span><strong>{{ $txt($paciente['cns'] ?? '') }}</strong></div>
                        <div class="wide"><span>Endereço completo</span><strong>{{ $txt($paciente['endereco'] ?? '') }}</strong></div>
                    </div>
                @endif

                @if($pecCount > 0)
                    <div class="metricas">
                        <div class="metrica">
                            <span>Quantidade de consulta (CID / CIAP)</span>
                            <strong>{{ (int) $pec_resumo['com_cid_ciap'] }}</strong>
                            <small>Atendimentos com CID/CIAP (total {{ (int) $pec_resumo['atendimentos'] }} na Base PEC).</small>
                        </div>
                        <div class="metrica">
                            <span>Qtd. exames</span>
                            <strong>{{ (int) $pec_resumo['itens_exame'] }}</strong>
                            <small>Itens de exame/procedimento nos atendimentos PEC.</small>
                        </div>
                        <div class="metrica">
                            <span>QTD Consulta no Dia</span>
                            <strong>{{ (int) $pec_resumo['consulta_no_dia'] }}</strong>
                            <small>Tipo no DW indica consulta no dia / demanda espontânea.</small>
                        </div>
                        <div class="metrica">
                            <span>QTD Consulta Agendada</span>
                            <strong>{{ (int) $pec_resumo['consulta_agendada'] }}</strong>
                            <small>Tipo no DW indica consulta agendada / marcada.</small>
                        </div>
                    </div>

                    <h3 class="hist-title">Histórico do Paciente</h3>
                    <p class="hist-sub">Clínicas — atenção básica (PEC)</p>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data da consulta</th>
                                    <th>Especialidade</th>
                                    <th>Profissional</th>
                                    <th>Unidade</th>
                                    <th>Consulta (CID / CIAP)</th>
                                    <th>Exames e procedimentos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pec_atendimentos as $row)
                                    <tr>
                                        <td>{{ $txt($row['data']) }}</td>
                                        <td>{{ $txt($row['especialidade']) }}</td>
                                        <td>{{ $txt($row['profissional']) }}</td>
                                        <td>{{ $txt($row['unidade']) }}</td>
                                        <td>{{ $txt($row['cid_ciap']) }}</td>
                                        <td>{{ $txt($row['exames']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(!$paciente)
                    <p class="empty">Nenhum atendimento na atenção básica (Base PEC) para este paciente.</p>
                @endif
            </section>

            <section class="bloco">
                <div class="bloco-head">
                    <h2>Policlínicas</h2>
                    <span>{{ $poliCount }} registro(s)</span>
                </div>

                @if($poliCount === 0)
                    <p class="empty">Nenhum registro nas Policlínicas para este paciente.</p>
                    @if(($poli_fonte ?? '') === 'sem_cache')
                        <p class="hint">Atualize o cache (menu lateral → Cache) para carregar o histórico das Policlínicas.</p>
                    @elseif(($poli_fonte ?? '') === 'vazio')
                        <p class="hint">Nenhum atendimento foi localizado no e-SUS para este paciente.</p>
                    @endif
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Consulta / Exame</th>
                                    <th>Paciente</th>
                                    <th>Telefone</th>
                                    <th>Unidade</th>
                                    <th>Profissional</th>
                                    <th>Data inclusão</th>
                                    <th>Data agenda</th>
                                    <th>Situação</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($poli_rows as $row)
                                    @php
                                        $tipoKey = mb_strtoupper(trim((string) ($row['tipo'] ?? '')), 'UTF-8');
                                        $statusKey = mb_strtoupper(trim((string) ($row['status'] ?? '')), 'UTF-8');
                                        $tipoClasse = str_contains($tipoKey, 'CONSULT') ? 'tipo-consulta' : (str_contains($tipoKey, 'EXAME') ? 'tipo-exame' : 'tipo-outro');
                                        $statusClasse = str_contains($statusKey, 'AGEND') ? 'status-agendado' : (str_contains($statusKey, 'NAO EFETIV') || str_contains($statusKey, 'NÃO EFETIV') ? 'status-nao-efetivado' : (str_contains($statusKey, 'EFETIV') ? 'status-efetivado' : 'status-outro'));
                                    @endphp
                                    <tr>
                                        <td><span class="badge {{ $tipoClasse }}">{{ $txt($row['tipo']) }}</span></td>
                                        <td>{{ $txt($row['procedimento']) }}</td>
                                        <td>{{ $txt($row['paciente']) }}</td>
                                        <td>{{ $txt($row['telefone']) }}</td>
                                        <td>{{ $txt($row['unidade']) }}</td>
                                        <td>{{ $txt($row['profissional']) }}</td>
                                        <td>{{ $txt($row['data_inclusao']) }}</td>
                                        <td>{{ $txt($row['data_agenda']) }}</td>
                                        <td>{{ $txt($row['situacao']) }}</td>
                                        <td><span class="status {{ $statusClasse }}">{{ $txt($row['status']) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    @endif
</div>
@endsection
