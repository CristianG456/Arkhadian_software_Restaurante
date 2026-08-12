<#
.SYNOPSIS
  Starts Restaurant Core's local API server using the host/port from its
  .env (CORE_HOST/CORE_PORT), independent of Admin Desktop. Only reports
  success once /api/health actually responds - never before.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$core = Join-Path $root 'services\restaurant-core'

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: PHP no esta disponible en PATH." -ForegroundColor Red
    Write-Host "Ejecuta .\scripts\install.ps1 o revisa docs\development.md#requirements" -ForegroundColor Yellow
    exit 1
}

Push-Location $core
try {
    if (-not (Test-Path '.env')) {
        Write-Host "ERROR: services\restaurant-core\.env no existe." -ForegroundColor Red
        Write-Host "Ejecuta .\scripts\install.ps1 desde la raiz del repositorio." -ForegroundColor Yellow
        exit 1
    }

    $envFile = Get-Content '.env' -Raw
    $hostMatch = [regex]::Match($envFile, '(?m)^CORE_HOST=(.*)$')
    $portMatch = [regex]::Match($envFile, '(?m)^CORE_PORT=(.*)$')
    $coreHost = if ($hostMatch.Success) { $hostMatch.Groups[1].Value.Trim() } else { '127.0.0.1' }
    $corePort = if ($portMatch.Success) { $portMatch.Groups[1].Value.Trim() } else { '8000' }
    $healthUrl = "http://${coreHost}:${corePort}/api/health"

    Write-Host "Iniciando Restaurant Core..." -ForegroundColor Cyan
    $process = Start-Process -FilePath 'php' -ArgumentList "artisan serve --host=$coreHost --port=$corePort" -NoNewWindow -PassThru

    $ready = $false
    for ($i = 0; $i -lt 30; $i++) {
        if ($process.HasExited) { break }
        try {
            $response = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 1
            if ($response.StatusCode -eq 200) { $ready = $true; break }
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }

    if ($process.HasExited) {
        Write-Host "ERROR: Restaurant Core se detuvo inesperadamente al iniciar." -ForegroundColor Red
        Write-Host "Revisa services\restaurant-core\storage\logs\ para mas detalle." -ForegroundColor Yellow
        exit 1
    }

    if (-not $ready) {
        Write-Host "ERROR: Restaurant Core no respondio en $healthUrl dentro del tiempo esperado (15s)." -ForegroundColor Red
        Write-Host "Revisa services\restaurant-core\storage\logs\ para mas detalle." -ForegroundColor Yellow
        Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
        exit 1
    }

    Write-Host "Restaurant Core activo en http://${coreHost}:${corePort}" -ForegroundColor Green
    Wait-Process -Id $process.Id
}
finally {
    Pop-Location
}
