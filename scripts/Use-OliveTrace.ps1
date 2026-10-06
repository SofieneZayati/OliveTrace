# Activate the isolated tools installed for this Windows machine.
# Run from the project root: . ./scripts/Use-OliveTrace.ps1
$toolsRoot = Join-Path $env:LOCALAPPDATA 'OliveTraceTools'
$phpDirectory = Join-Path $toolsRoot 'php84'
if (-not (Test-Path (Join-Path $phpDirectory 'php.exe'))) {
    throw 'The optional OliveTrace PHP runtime is missing. Install PHP 8.3+ and use the normal setup in README.md.'
}
$binDirectory = Join-Path $toolsRoot 'bin'
$mysqlDirectory = Join-Path $toolsRoot 'mysql84/mysql-8.4.9-winx64/bin'
$env:PATH = "$binDirectory;$phpDirectory;$mysqlDirectory;" + $env:PATH
Write-Host 'OliveTrace tools activated for this PowerShell session.'
php -v
composer --version
