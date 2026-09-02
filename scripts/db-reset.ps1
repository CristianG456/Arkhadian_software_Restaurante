<#
.SYNOPSIS
  DEVELOPMENT ONLY. Drops every table in the local Restaurant Core
  database, re-runs all migrations, then seeds. There is no undo - never
  run this against an installation with real data.

.PARAMETER Force
  Skip the confirmation prompt (for non-interactive use).
#>
param(
    [switch]$Force
)

$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'resolve-php.ps1')
$php = Resolve-ProjectPhp
$root = Split-Path -Parent $PSScriptRoot
$core = Join-Path $root 'services\restaurant-core'

Write-Host "This will DROP ALL TABLES in the local Restaurant Core database and reseed it." -ForegroundColor Yellow
Write-Host "Development/testing only - never point this at a real installation." -ForegroundColor Yellow

if (-not $Force) {
    $confirmation = Read-Host "Type 'reset' to continue"
    if ($confirmation -ne 'reset') {
        Write-Host "Cancelled." -ForegroundColor Cyan
        exit 0
    }
}

Push-Location $core
& $php artisan migrate:fresh --seed
Pop-Location
