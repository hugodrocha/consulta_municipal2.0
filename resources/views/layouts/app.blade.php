<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Consulta Municipal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('styles')
</head>
<body>
@php
    $menuAtivo = $menu ?? '';
    $user = auth()->user();
@endphp
<div class="app-shell">
    <header class="mobile-bar">
        <button type="button" class="mobile-toggle" onclick="toggleSidebar()" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebar">☰</button>
        <span class="mobile-title">Consulta Municipal</span>
    </header>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark"><img src="https://prefeitura.mesquita.rj.gov.br/wp-content/uploads/2025/11/Brasao-Cidade-de-Mesquita-Colorido-1.png" alt="Brasão de Mesquita"></div>
            <div class="sidebar-brand-copy">
                <strong>Consulta Municipal</strong>
                <small>Saúde · Mesquita</small>
            </div>
            <button type="button" class="sidebar-pin" id="sidebarPin" aria-label="Fixar menu aberto" title="Fixar menu aberto" aria-pressed="false">📌</button>
        </div>

        <div class="sidebar-user sidebar-copy">
            <strong>{{ $user->name }}</strong>
            <span>{{ $user->setor ?: '—' }}</span>
            <span class="perfil-pill">{{ $user->perfil }}</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('consulta') }}" title="Consulta" aria-label="Consulta" class="{{ $menuAtivo === 'consulta' ? 'ativo' : '' }}"><span class="nav-icon" aria-hidden="true">🔎</span><span class="nav-label">Consulta</span></a>
            @if($user->isSuper())
                <a href="{{ route('cache.index') }}" title="Cache" aria-label="Cache" class="{{ $menuAtivo === 'cache' ? 'ativo' : '' }}"><span class="nav-icon" aria-hidden="true">🗃️</span><span class="nav-label">Cache</span></a>
                <a href="{{ route('usuarios.index') }}" title="Usuários" aria-label="Usuários" class="{{ $menuAtivo === 'usuarios' ? 'ativo' : '' }}"><span class="nav-icon" aria-hidden="true">👥</span><span class="nav-label">Usuários</span></a>
            @endif
            <a href="{{ route('senha.edit') }}" title="Trocar senha" aria-label="Trocar senha" class="{{ $menuAtivo === 'senha' ? 'ativo' : '' }}"><span class="nav-icon" aria-hidden="true">🔑</span><span class="nav-label">Trocar senha</span></a>
            <form method="post" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" title="Sair" aria-label="Sair"><span class="nav-icon" aria-hidden="true">🚪</span><span class="nav-label">Sair</span></button>
            </form>
        </nav>
    </aside>

    <main class="content">
        <div class="database-status" aria-label="Estado das conexões de dados">
            <span class="database-status-item {{ ($statusConexoes['pec'] ?? false) ? 'online' : 'offline' }}" title="{{ ($statusConexoes['pec'] ?? false) ? 'Base PEC conectada' : 'Base PEC indisponível' }}">
                <span class="status-dot" aria-hidden="true"></span><span>PEC</span>
            </span>
            <span class="database-status-item {{ ($statusConexoes['esus'] ?? false) ? 'online' : 'offline' }}" title="{{ ($statusConexoes['esus'] ?? false) ? 'e-SUS conectado' : 'e-SUS indisponível' }}">
                <span class="status-dot" aria-hidden="true"></span><span>e-SUS</span>
            </span>
        </div>
        @if(session('sucesso'))
            <div class="alert ok">{{ session('sucesso') }}</div>
        @endif
        @if(session('erro'))
            <div class="alert err">{{ session('erro') }}</div>
        @endif
        @yield('content')
    </main>
</div>
<script>
function toggleSidebar(force) {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    const open = force === undefined ? !sb.classList.contains('aberto') : force;
    sb.classList.toggle('aberto', open);
    ov.classList.toggle('aberto', open);
    document.querySelector('.mobile-toggle')?.setAttribute('aria-expanded', String(open));
}

(() => {
    const sidebar = document.getElementById('sidebar');
    const pin = document.getElementById('sidebarPin');
    const storageKey = 'consulta-menu-fixado';

    function setPinned(pinned) {
        sidebar.classList.toggle('fixada', pinned);
        document.body.classList.toggle('menu-fixado', pinned);
        pin.setAttribute('aria-pressed', String(pinned));
        pin.setAttribute('aria-label', pinned ? 'Desafixar menu' : 'Fixar menu aberto');
        pin.setAttribute('title', pinned ? 'Desafixar menu' : 'Fixar menu aberto');
        localStorage.setItem(storageKey, pinned ? '1' : '0');
    }

    setPinned(localStorage.getItem(storageKey) === '1');
    pin.addEventListener('click', () => setPinned(!sidebar.classList.contains('fixada')));
})();
</script>
</body>
</html>
