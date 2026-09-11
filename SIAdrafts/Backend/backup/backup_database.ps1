<#
.SYNOPSIS
  Daily backup of the SIAdrafts MySQL database, with retention cleanup.

.NOTES
  Deliberately a standalone PowerShell script, not a PHP one that shells
  out to mysqldump -- Backend/php.ini has exec/shell_exec/system/passthru/
  popen disabled server-wide (see the security review), so a PHP script
  couldn't invoke mysqldump that way even if we wanted it to. This runs
  outside PHP entirely, driven by Windows Task Scheduler, which is also
  just the standard way to do scheduled DB backups regardless.

  Reads DB credentials from .env directly rather than hardcoding them, so
  it can't silently drift from what the app itself actually connects to.
#>

$ErrorActionPreference = 'Stop'

$AppRoot    = "C:\xampp\htdocs\SIAdrafts\SIAdrafts"
$EnvFile    = Join-Path $AppRoot ".env"
$BackupDir  = Join-Path $AppRoot "storage\backups"
$LogFile    = Join-Path $BackupDir "backup.log"
$MysqldumpExe = "C:\xampp\mysql\bin\mysqldump.exe"
$RetentionDays = 14

function Write-Log($message) {
    $line = "[{0:yyyy-MM-dd HH:mm:ss}] {1}" -f (Get-Date), $message
    Add-Content -Path $LogFile -Value $line
    Write-Output $line
}

if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

try {
    if (-not (Test-Path $EnvFile)) {
        throw ".env not found at $EnvFile"
    }

    $envVars = @{}
    Get-Content $EnvFile | ForEach-Object {
        if ($_ -match '^\s*([A-Z_]+)\s*=\s*(.*)\s*$') {
            $envVars[$matches[1]] = $matches[2]
        }
    }

    $dbHost     = if ($envVars.ContainsKey('DB_HOST')) { $envVars['DB_HOST'] } else { 'localhost' }
    $dbPort     = if ($envVars.ContainsKey('DB_PORT')) { $envVars['DB_PORT'] } else { '3306' }
    $dbUser     = if ($envVars.ContainsKey('DB_USERNAME')) { $envVars['DB_USERNAME'] } else { 'root' }
    $dbPassword = if ($envVars.ContainsKey('DB_PASSWORD')) { $envVars['DB_PASSWORD'] } else { '' }
    $dbName     = if ($envVars.ContainsKey('DB_DATABASE')) { $envVars['DB_DATABASE'] } else { 'sia_project' }

    $timestamp  = Get-Date -Format "yyyyMMdd_HHmmss"
    $outFile    = Join-Path $BackupDir "$dbName`_$timestamp.sql"

    $mysqldumpArgs = @(
        "--host=$dbHost",
        "--port=$dbPort",
        "--user=$dbUser",
        "--single-transaction",   # consistent snapshot without locking tables for writes
        "--routines",
        "--triggers",
        $dbName
    )

    # Password passed via env var, not a CLI arg -- CLI args are visible to
    # any other process on the box via the process list; MYSQL_PWD isn't.
    $prevPwd = $env:MYSQL_PWD
    $env:MYSQL_PWD = $dbPassword

    & $MysqldumpExe @mysqldumpArgs | Out-File -FilePath $outFile -Encoding utf8

    $env:MYSQL_PWD = $prevPwd

    if ($LASTEXITCODE -ne 0) {
        throw "mysqldump exited with code $LASTEXITCODE"
    }

    $sizeKb = [math]::Round((Get-Item $outFile).Length / 1KB, 1)
    Write-Log "Backup succeeded: $outFile ($sizeKb KB)"

    # Retention: delete backups older than $RetentionDays, but never let
    # cleanup delete every backup if something's gone wrong with dating --
    # only prune once at least one fresh backup exists from today.
    $todaysBackups = Get-ChildItem $BackupDir -Filter "*.sql" | Where-Object { $_.LastWriteTime.Date -eq (Get-Date).Date }
    if ($todaysBackups.Count -gt 0) {
        $cutoff = (Get-Date).AddDays(-$RetentionDays)
        $old = Get-ChildItem $BackupDir -Filter "*.sql" | Where-Object { $_.LastWriteTime -lt $cutoff }
        foreach ($f in $old) {
            Remove-Item $f.FullName -Force
            Write-Log "Pruned old backup (older than $RetentionDays days): $($f.Name)"
        }
    }
}
catch {
    Write-Log "BACKUP FAILED: $($_.Exception.Message)"
    exit 1
}
