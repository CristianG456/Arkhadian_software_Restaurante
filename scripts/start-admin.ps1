<#
.SYNOPSIS
  Launches Admin Desktop as a native window via Tauri (not a browser tab).
  Does not start Restaurant Core - run scripts\start-core.ps1 separately,
  or use scripts\dev.ps1 to start both.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$admin = Join-Path $root 'apps\admin-desktop'

Push-Location $admin
npm run tauri -- dev
Pop-Location
