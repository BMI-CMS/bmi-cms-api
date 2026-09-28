param (
    [int]$Port = 8000,
    [switch]$Serve
)

$ErrorActionPreference = "Stop"
$projectRoot = $PSScriptRoot
$envFile = Join-Path $projectRoot ".env"
$envExample = Join-Path $projectRoot ".env.example"

if (-not (Test-Path $envFile)) {
    if (Test-Path $envExample) {
        Write-Host "Creating .env from .env.example..." -ForegroundColor Cyan
        Copy-Item $envExample $envFile
    } else {
        Write-Error ".env or .env.example not found."
        exit 1
    }
}

# Find active IPv4 address connected to the default gateway
$route = Get-NetRoute -DestinationPrefix '0.0.0.0/0' -ErrorAction SilentlyContinue | Select-Object -First 1
$ip = $null

if ($route) {
    $ip = (Get-NetIPAddress -InterfaceIndex $route.InterfaceIndex -AddressFamily IPv4 -ErrorAction SilentlyContinue).IPAddress
}

# Fallback if no default route
if (-not $ip) {
    $ipObj = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object {
        $_.InterfaceAlias -notmatch 'Loopback|vEthernet|Virtual|WSL' -and
        $_.IPAddress -notmatch '^127\.|^169\.254\.'
    } | Select-Object -First 1
    if ($ipObj) { $ip = $ipObj.IPAddress }
}

if (-not $ip) {
    Write-Error "Could not automatically detect local IP address. Check your network connection."
    exit 1
}

$newUrl = "http://${ip}:${Port}"

Write-Host "Detected Local IP : $ip" -ForegroundColor Green
Write-Host "Target APP_URL    : $newUrl" -ForegroundColor Green

$content = [System.IO.File]::ReadAllText($envFile)
if ($content -match "(?m)^APP_URL=.*$") {
    $content = $content -replace "(?m)^APP_URL=.*$", "APP_URL=$newUrl"
} else {
    $content += "`nAPP_URL=$newUrl"
}

[System.IO.File]::WriteAllText($envFile, $content)
Write-Host "Successfully updated APP_URL in .env to $newUrl" -ForegroundColor Green

if ($Serve) {
    Write-Host "Starting Laravel server on ${ip}:$Port..." -ForegroundColor Cyan
    php artisan serve --host=$ip --port=$Port
}
