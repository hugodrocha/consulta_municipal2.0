# Consulta Municipal

Sistema Laravel criado do zero para consulta unificada de pacientes na **Base PEC** (atenção básica) e nas **Policlínicas** (e-SUS).

## Subir

```bash
cd C:\laravel\consulta_municipal
php artisan serve
```

Abra http://127.0.0.1:8000

### Login inicial (superusuário)

- Usuário: `admin`
- Senha: `Cm@2026x`

## Funcionalidades

- Login com usuário e senha
- Após o login, a tela inicial é a **consulta** (CPF, CNS ou nome)
- Resultados da Base PEC (identificação, métricas, histórico)
- Resultados das Policlínicas (tabela de consultas/exames)
- Superusuário: cadastrar perfis, atualizar cache por período (meses), sair
- Layout responsivo com menu lateral no celular

## Cache das Policlínicas

A view MySQL é lenta. Em **Cache** (menu lateral), escolha o período e atualize.

Ou via terminal:

```bash
php artisan consulta:sync-cache --meses=12
```

## Credenciais de dados

Definidas no `.env` (não versionar senhas em repositório público):

| Fonte | Variáveis |
|-------|-----------|
| App local | SQLite (`database/database.sqlite`) |
| DW PEC | `PEC_DW_*` (Postgres `192.168.76.69:5433`) |
| Policlínicas | `POLI_DB_*` (MySQL `135.119.133.36`) |
