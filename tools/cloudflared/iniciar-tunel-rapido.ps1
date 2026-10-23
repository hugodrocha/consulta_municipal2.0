$ErrorActionPreference = 'Stop'

$app = 'C:\laravel\consulta_municipal'
$php = 'C:\xampp\php\php.exe'
$cloudflared = 'C:\xampp\htdocs\PEC\tools\cloudflared\cloudflared-windows-amd64.exe'
$originPort = 8018
$logs = Join-Path $app 'storage\logs'
$stderr = Join-Path $logs 'cloudflared_consulta_municipal_err.txt'
$stdout = Join-Path $logs 'cloudflared_consulta_municipal_out.txt'
$urlFile = Join-Path $logs 'cloudflared_consulta_municipal_url.txt'
$pidFile = Join-Path $logs 'cloudflared_consulta_municipal_pid.txt'
$desktopFile = Join-Path $env:USERPROFILE 'Desktop\ConsultaMunicipal-link-temporario.txt'

if (-not (Test-Path (Join-Path $app 'artisan'))) { throw "Projeto nao encontrado: $app" }
if (-not (Test-Path $php)) { throw "PHP nao encontrado: $php" }
if (-not (Test-Path $cloudflared)) { throw "cloudflared nao encontrado: $cloudflared" }

New-Item -ItemType Directory -Force -Path $logs | Out-Null

if (Test-Path $pidFile) {
    $oldPid = 0
    if ([int]::TryParse((Get-Content $pidFile -Raw).Trim(), [ref]$oldPid)) {
        $oldProcess = Get-Process -Id $oldPid -ErrorAction SilentlyContinue
        if ($oldProcess -and $oldProcess.Path -eq $cloudflared) {
            Stop-Process -Id $oldPid -Force -ErrorAction SilentlyContinue
        }
    }
}

$loginUrl = "http://127.0.0.1:$originPort/login"
$localReady = $false
try {
    $response = Invoke-WebRequest -Uri $loginUrl -UseBasicParsing -TimeoutSec 5
    $localReady = $response.StatusCode -eq 200 -and $response.Content.Contains('Consulta Municipal')
} catch {
    $localReady = $false
}

if (-not $localReady) {
    $listener = Get-NetTCPConnection -LocalPort $originPort -State Listen -ErrorAction SilentlyContinue
    if ($listener) { throw "A porta $originPort esta ocupada, mas nao responde como Consulta Municipal." }

    Start-Process -FilePath $php -ArgumentList @(
        'artisan', 'serve', '--host=0.0.0.0', "--port=$originPort"
    ) -WorkingDirectory $app -WindowStyle Minimized | Out-Null

    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        Start-Sleep -Seconds 1
        try {
            $response = Invoke-WebRequest -Uri $loginUrl -UseBasicParsing -TimeoutSec 3
            $localReady = $response.StatusCode -eq 200 -and $response.Content.Contains('Consulta Municipal')
            if ($localReady) { break }
        } catch {
            $localReady = $false
        }
    }
}

if (-not $localReady) { throw "Consulta Municipal nao respondeu em http://127.0.0.1:$originPort/login" }

Remove-Item $stderr, $stdout, $urlFile -ErrorAction SilentlyContinue
$tunnel = Start-Process -FilePath $cloudflared -ArgumentList @(
    'tunnel', '--url', "http://127.0.0.1:$originPort", '--protocol', 'http2', '--no-autoupdate'
) -RedirectStandardError $stderr -RedirectStandardOutput $stdout -WindowStyle Hidden -PassThru
Set-Content -Path $pidFile -Value $tunnel.Id -Encoding ASCII

Write-Host "Aguardando tunel da Consulta Municipal (origem 127.0.0.1:$originPort)..."
$url = $null
for ($attempt = 0; $attempt -lt 45; $attempt++) {
    Start-Sleep -Seconds 2
    if (Test-Path $stderr) {
        $match = Select-String -Path $stderr -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' | Select-Object -Last 1
        if ($match) {
            $url = [regex]::Match($match.Line, 'https://[a-z0-9-]+\.trycloudflare\.com').Value
            break
        }
    }
    if ($tunnel.HasExited) { break }
}

if (-not $url) {
    Write-Host 'Nao foi possivel obter uma URL temporaria. Log do tunel:'
    if (Test-Path $stderr) { Get-Content $stderr -Tail 30 }
    exit 1
}

$remoteUrl = "$url/login"
Set-Content -Path $urlFile -Value $url -Encoding UTF8
@"
Consulta Municipal - acesso remoto temporario
Gerado em: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')

TUNEL RESTABELECIDO
Acesse pelo link:
$remoteUrl

O endereco muda quando o tunel e reiniciado. A tela exige login.
"@ | Set-Content -Path $desktopFile -Encoding UTF8

Write-Host ''
Write-Host 'TUNEL RESTABELECIDO'
Write-Host "Acesse: $remoteUrl"
Write-Host "Link salvo em: $desktopFile"
Write-Host 'O tunel permanece ativo enquanto este computador estiver ligado.'