<#
.SYNOPSIS
  Launches Admin Desktop as a native window via Tauri - never a browser
  tab. Does not start Restaurant Core - run scripts\start-core.ps1
  separately, or use scripts\dev.ps1 to start both.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$admin = Join-Path $root 'apps\admin-desktop'

$missingTools = @()
foreach ($tool in @('node', 'npm', 'cargo')) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { $missingTools += $tool }
}
if ($missingTools.Count -gt 0) {
    Write-Host "ERROR: No disponible(s) en PATH: $($missingTools -join ', ')" -ForegroundColor Red
    Write-Host "Ejecuta .\scripts\install.ps1 o revisa docs\development.md#requirements" -ForegroundColor Yellow
    exit 1
}
if (-not (Test-Path (Join-Path $admin 'node_modules'))) {
    Write-Host "ERROR: apps\admin-desktop\node_modules no existe (dependencias de npm sin instalar)." -ForegroundColor Red
    Write-Host "Ejecuta .\scripts\install.ps1" -ForegroundColor Yellow
    exit 1
}

Push-Location $admin
try {
    Write-Host "Iniciando Restaurant Admin (tauri dev)..." -ForegroundColor Cyan
    npm run tauri -- dev
}
finally {
    Pop-Location
}
