<# Starts the persistent local print-job worker. #>
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'resolve-php.ps1')
$null = Resolve-ProjectPhp
$projectRoot = Split-Path -Parent $PSScriptRoot
$core = Join-Path $projectRoot 'services\restaurant-core'

Push-Location $core
try {
    Write-Host 'Worker de impresión activo. Ctrl+C para detenerlo.' -ForegroundColor Cyan
    & php artisan production:work --sleep=2
}
finally {
    Pop-Location
}
