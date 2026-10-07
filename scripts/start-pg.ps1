# Starts the local PostgreSQL 17 server instance
$pgBin = "C:\Users\Aditya Choudhary\.pgsql\pgsql\bin"
$pgData = "C:\Users\Aditya Choudhary\.pgsql\data"
$pgLog = "C:\Users\Aditya Choudhary\.pgsql\postgres.log"

if (-not (Test-Path $pgData)) {
    Write-Host "Initializing PostgreSQL cluster at $pgData..." -ForegroundColor Yellow
    & "$pgBin\initdb.exe" -D $pgData -U postgres -E UTF8 --locale=C -A trust
}

Write-Host "Starting PostgreSQL server..." -ForegroundColor Green
& "$pgBin\pg_ctl.exe" -D $pgData -l $pgLog start
Start-Sleep -Seconds 2

& "$pgBin\pg_isready.exe" -h 127.0.0.1 -p 5432
