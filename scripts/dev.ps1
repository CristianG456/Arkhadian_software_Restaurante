<#
.SYNOPSIS
  Starts the full local dev environment: Restaurant Core in its own
  window, waits for it to actually report healthy, then launches Admin
  Desktop as a native Tauri window in this one.

  Never opens a web browser - localhost:1420 is internal Vite/Tauri
  plumbing, not how Restaurant Admin is used or tested. Closing Admin
  Desktop (or Ctrl+C here) does not stop Core - close its window
  separately, mirroring how the two are independent processes in
  production.
#>
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'resolve-php.ps1')
$null = Resolve-ProjectPhp
$root = Split-Path -Parent $PSScriptRoot
$core = Join-Path $root 'services\restaurant-core'
$admin = Join-Path $root 'apps\admin-desktop'

function Test-CommandAvailable {
    param([string]$Name)
    return [bool](Get-Command $Name -ErrorAction SilentlyContinue)
}

function Fail-Startup {
    param([string]$Reason)
    Write-Host ""
    Write-Host "ERROR: No se pudo iniciar el entorno de desarrollo." -ForegroundColor Red
    Write-Host ""
    Write-Host $Reason -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Ejecuta .\scripts\install.ps1 o revisa docs\development.md" -ForegroundColor Yellow
    exit 1
}

Write-Host "==> Verificando prerrequisitos..." -ForegroundColor Cyan

$missingTools = @()
foreach ($tool in @('php', 'node', 'npm', 'cargo')) {
    if (-not (Test-CommandAvailable $tool)) { $missingTools += $tool }
}
if ($missingTools.Count -gt 0) {
    Fail-Startup "No disponible(s) en PATH: $($missingTools -join ', ')"
}

if (-not (Test-Path (Join-Path $core 'vendor'))) {
    Fail-Startup "services\restaurant-core\vendor no existe (dependencias de Composer sin instalar)."
}
if (-not (Test-Path (Join-Path $core '.env'))) {
    Fail-Startup "services\restaurant-core\.env no existe."
}
if (-not (Test-Path (Join-Path $admin 'node_modules'))) {
    Fail-Startup "apps\admin-desktop\node_modules no existe (dependencias de npm sin instalar)."
}

Write-Host "==> Prerrequisitos OK." -ForegroundColor Green

Write-Host "==> Iniciando Restaurant Core en una ventana separada..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList '-NoExit', '-Command', "& '$PSScriptRoot\start-core.ps1'"

Write-Host "==> Esperando a que Restaurant Core responda (/api/health)..." -ForegroundColor Cyan

$envFile = Get-Content (Join-Path $core '.env') -Raw
$hostMatch = [regex]::Match($envFile, '(?m)^CORE_HOST=(.*)$')
$portMatch = [regex]::Match($envFile, '(?m)^CORE_PORT=(.*)$')
$coreHost = if ($hostMatch.Success) { $hostMatch.Groups[1].Value.Trim() } else { '127.0.0.1' }
$corePort = if ($portMatch.Success) { $portMatch.Groups[1].Value.Trim() } else { '8000' }
$healthUrl = "http://${coreHost}:${corePort}/api/health"

$ready = $false
for ($i = 0; $i -lt 40; $i++) {
    try {
        $response = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 1
        if ($response.StatusCode -eq 200) { $ready = $true; break }
    } catch {
        Start-Sleep -Milliseconds 500
    }
}

if (-not $ready) {
    Write-Host ""
    Write-Host "ERROR: Restaurant Core no pudo iniciarse." -ForegroundColor Red
    Write-Host ""
    Write-Host "Sin respuesta en $healthUrl tras 20 segundos." -ForegroundColor Yellow
    Write-Host "Revisa la ventana de Restaurant Core y services\restaurant-core\storage\logs\" -ForegroundColor Yellow
    exit 1
}

Write-Host "==> Restaurant Core activo." -ForegroundColor Green
Write-Host "==> Iniciando Restaurant Admin (ventana nativa Tauri)..." -ForegroundColor Cyan
& "$PSScriptRoot\start-admin.ps1"
