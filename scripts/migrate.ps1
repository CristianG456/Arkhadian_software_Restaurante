<#
.SYNOPSIS
  Runs Restaurant Core's database migrations against PostgreSQL.
#>
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'resolve-php.ps1')
$php = Resolve-ProjectPhp
$root = Split-Path -Parent $PSScriptRoot
$core = Join-Path $root 'services\restaurant-core'

Push-Location $core
& $php artisan migrate
Pop-Location
