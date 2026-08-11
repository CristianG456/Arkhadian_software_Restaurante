<#
.SYNOPSIS
  Starts Restaurant Core's local API server using the host/port from its
  .env (CORE_HOST/CORE_PORT), independent of Admin Desktop.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$core = Join-Path $root 'services\restaurant-core'

Push-Location $core

$envFile = Get-Content '.env' -Raw
$hostMatch = [regex]::Match($envFile, '(?m)^CORE_HOST=(.*)$')
$portMatch = [regex]::Match($envFile, '(?m)^CORE_PORT=(.*)$')
$coreHost = if ($hostMatch.Success) { $hostMatch.Groups[1].Value.Trim() } else { '127.0.0.1' }
$corePort = if ($portMatch.Success) { $portMatch.Groups[1].Value.Trim() } else { '8000' }

Write-Host "==> Restaurant Core listening on http://${coreHost}:${corePort}" -ForegroundColor Cyan
php artisan serve --host=$coreHost --port=$corePort

Pop-Location
