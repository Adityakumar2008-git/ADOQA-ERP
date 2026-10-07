# Stops the local PostgreSQL 17 server instance
$pgBin = "C:\Users\Aditya Choudhary\.pgsql\pgsql\bin"
$pgData = "C:\Users\Aditya Choudhary\.pgsql\data"

Write-Host "Stopping PostgreSQL server..." -ForegroundColor Yellow
& "$pgBin\pg_ctl.exe" -D $pgData stop
