# Serve the Laravel app on your local network for phone testing.
# Usage: .\scripts\mobile-serve.ps1   OR   composer mobile

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $projectRoot

if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env not found. Copy .env.example to .env first." -ForegroundColor Red
    exit 1
}

function Get-LocalIPv4 {
    $candidates = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notmatch '^127\.' -and
            $_.IPAddress -notmatch '^169\.254\.' -and
            $_.PrefixOrigin -ne 'WellKnown'
        } |
        Sort-Object -Property InterfaceMetric

    if ($candidates) {
        return ($candidates | Select-Object -First 1).IPAddress
    }

    # Fallback for older setups
    $fallback = (Get-NetIPConfiguration -ErrorAction SilentlyContinue |
        Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -eq 'Up' } |
        Select-Object -First 1).IPv4Address.IPAddress

    if ($fallback) {
        return $fallback
    }

    return $null
}

$port = if ($env:MOBILE_SERVE_PORT) { $env:MOBILE_SERVE_PORT } else { "8080" }
$ip = Get-LocalIPv4

if (-not $ip) {
    Write-Host "ERROR: Could not detect your local IP. Connect to Wi-Fi and try again." -ForegroundColor Red
    exit 1
}

$url = "http://${ip}:${port}"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Mobile UI testing" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1. Phone must be on the SAME Wi-Fi as this PC" -ForegroundColor Yellow
Write-Host "  2. Open this URL on your phone:" -ForegroundColor Yellow
Write-Host ""
Write-Host "     $url" -ForegroundColor Green
Write-Host ""
Write-Host "  Press Ctrl+C to stop the server." -ForegroundColor DarkGray
Write-Host ""

$env:APP_URL = $url
& php artisan config:clear --ansi | Out-Null

Write-Host "  Starting server on port $port ..." -ForegroundColor DarkGray
Write-Host ""

& php artisan serve --host=0.0.0.0 --port=$port
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Could not bind to port $port. Try another port:" -ForegroundColor Red
    Write-Host "  `$env:MOBILE_SERVE_PORT=8888; composer mobile" -ForegroundColor Yellow
    exit $LASTEXITCODE
}
