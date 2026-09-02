function Resolve-ProjectPhp {
    $candidates = @(Get-Command php -All -ErrorAction SilentlyContinue |
        Select-Object -ExpandProperty Source -Unique)

    foreach ($candidate in $candidates) {
        try {
            $versionId = & $candidate -r 'echo PHP_VERSION_ID;' 2>$null
            if ([int]$versionId -ge 80400) {
                $env:Path = (Split-Path -Parent $candidate) +
                    [IO.Path]::PathSeparator + $env:Path
                return $candidate
            }
        }
        catch {
            continue
        }
    }

    throw 'Restaurant Core requiere PHP 8.4 o superior. Instálalo o agrégalo al PATH.'
}
