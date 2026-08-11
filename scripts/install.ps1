<#
.SYNOPSIS
  Installs all dependencies for Restaurant Core and Admin Desktop, and
  creates each app's .env from its .env.example if one doesn't exist yet.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$core = Join-Path $root 'services\restaurant-core'
$admin = Join-Path $root 'apps\admin-desktop'

Write-Host "==> Restaurant Core: composer install" -ForegroundColor Cyan
Push-Location $core
composer install
if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    php artisan key:generate
    Write-Host "Created services/restaurant-core/.env — edit DB_PASSWORD before running migrate." -ForegroundColor Yellow
}
Pop-Location

Write-Host "==> Admin Desktop: npm install" -ForegroundColor Cyan
Push-Location $admin
npm install
if (-not (Test-Path '.env') -and (Test-Path '.env.example')) {
    Copy-Item '.env.example' '.env'
}
Pop-Location

Write-Host "==> Done. Next: scripts\migrate.ps1, then scripts\dev.ps1" -ForegroundColor Green
