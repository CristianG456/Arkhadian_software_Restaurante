<#
.SYNOPSIS
  Quick command-line check of Restaurant Core, without opening Admin Desktop.
  Useful for troubleshooting the local stack directly.
#>
$ErrorActionPreference = 'Stop'
$baseUrl = if ($env:CORE_BASE_URL) { $env:CORE_BASE_URL } else { 'http://127.0.0.1:8000' }

Write-Host "==> GET $baseUrl/api/health" -ForegroundColor Cyan
try {
    $health = Invoke-RestMethod -Uri "$baseUrl/api/health" -TimeoutSec 3
    $health | ConvertTo-Json
} catch {
    Write-Host "Restaurant Core is not reachable at $baseUrl" -ForegroundColor Red
    exit 1
}

Write-Host "`n==> GET $baseUrl/api/system/status" -ForegroundColor Cyan
$status = Invoke-RestMethod -Uri "$baseUrl/api/system/status" -TimeoutSec 3
$status.data | ConvertTo-Json -Depth 5
