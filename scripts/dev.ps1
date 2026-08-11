<#
.SYNOPSIS
  Starts the full local dev environment: Restaurant Core in its own window,
  then Admin Desktop in this one. Closing Admin Desktop (or Ctrl+C here)
  does not stop Core — close its window separately, mirroring how the two
  are independent processes in production.
#>
$ErrorActionPreference = 'Stop'

Write-Host "==> Starting Restaurant Core in a separate window..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList '-NoExit', '-Command', "& '$PSScriptRoot\start-core.ps1'"

Write-Host "==> Waiting for Restaurant Core to come up..." -ForegroundColor Cyan
$ready = $false
for ($i = 0; $i -lt 20; $i++) {
    try {
        $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/api/health' -UseBasicParsing -TimeoutSec 1
        if ($response.StatusCode -eq 200) { $ready = $true; break }
    } catch {
        Start-Sleep -Milliseconds 500
    }
}
if ($ready) {
    Write-Host "==> Restaurant Core is up." -ForegroundColor Green
} else {
    Write-Host "==> Restaurant Core did not respond yet — Admin Desktop will keep retrying on its own." -ForegroundColor Yellow
}

Write-Host "==> Starting Admin Desktop..." -ForegroundColor Cyan
& "$PSScriptRoot\start-admin.ps1"
