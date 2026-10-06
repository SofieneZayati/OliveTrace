# Optional helper for the isolated installation on Sofiene's Windows machine.
$toolsRoot = Join-Path $env:LOCALAPPDATA 'OliveTraceTools'
$server = Join-Path $toolsRoot 'mysql84/mysql-8.4.9-winx64/bin/mysqld.exe'
$configuration = Join-Path $toolsRoot 'mysql.ini'
if (-not (Test-Path $server) -or -not (Test-Path $configuration)) {
    throw 'This helper requires the local OliveTrace MySQL installation. Otherwise start your own MySQL server normally.'
}
$listener = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
if ($listener) {
    Write-Host 'Port 3306 already has a listener. No second server was started.'
    return
}
Start-Process -FilePath $server -ArgumentList ('--defaults-file="' + $configuration + '"') -WindowStyle Hidden
Write-Host 'MySQL is starting on 127.0.0.1:3306. Run mysqladmin ping to check readiness.'
