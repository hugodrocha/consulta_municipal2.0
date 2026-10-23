<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Consulta Municipal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-brand">
            <img class="login-crest" src="https://prefeitura.mesquita.rj.gov.br/wp-content/uploads/2025/11/Brasao-Cidade-de-Mesquita-Colorido-1.png" alt="Brasão da Prefeitura de Mesquita">
            <div>
                <p class="login-eyebrow">Prefeitura de Mesquita</p>
                <h1>Consulta Municipal</h1>
                <p class="login-sub">Acesso ao sistema municipal de saúde</p>
            </div>
        </div>

        @if($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('login.store') }}" class="login-form">
            @csrf
            <label>
                Usuário
                <input type="text" name="usuario" value="{{ old('usuario') }}" autocomplete="username" placeholder="Seu usuário" required autofocus>
            </label>
            <label>
                Senha
                <input type="password" name="senha" autocomplete="current-password" placeholder="Sua senha" required>
            </label>
            <button type="submit"><span>Entrar</span><span aria-hidden="true">→</span></button>
        </form>
        <p class="login-footer">Acesso restrito a usuários autorizados</p>
    </div>
</body>
</html>
