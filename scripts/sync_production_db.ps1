<#
.SYNOPSIS
    Sync Production Databases (kpi_bubut & masterdata_kpi) to Local Laragon
    
.DESCRIPTION
    Script ini akan:
    1. SSH ke server production (10.88.8.46)
    2. Export database kpi_bubut & masterdata_kpi dari container Docker
    3. Download file backup ke lokal
    4. Import otomatis ke MySQL Laragon (Local)
    5. Membersihkan file temporary di server
    
.NOTES
    Server: peroniks@10.88.8.46
    Local DB: root / 123456788
#>

param(
    [int]$KeepDays = 7
)

# ============================================
# KONFIGURASI
# ============================================
$ServerUser = "peroniks"
$ServerHost = "10.88.8.46"
$ServerSSH = "$ServerUser@$ServerHost"

# Path di Server
$ServerAppPath_Bubut = "/srv/docker/apps/kpi-bubut"
$ServerAppPath_Master = "/srv/docker/apps/masterdatakpi"
$ServerBackupDir = "/tmp"

# Konfigurasi Lokal
$LocalDbUser = "root"
$LocalDbPass = "123456788"
$LocalBackupDir = "C:\laragon\www\kpi-bubut\backups"

# Cari path MySQL Laragon secara dinamis (atau gunakan default jika tidak ketemu)
$LaragonMysql = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
if (!(Test-Path $LaragonMysql)) {
    # Coba cari versi lain di folder bin\mysql
    $MysqlPaths = Get-ChildItem "C:\laragon\bin\mysql\*\bin\mysql.exe"
    if ($MysqlPaths) {
        $LaragonMysql = $MysqlPaths[0].FullName
    }
    else {
        $LaragonMysql = "mysql" # Berharap ada di PATH
    }
}

# ============================================
# FUNGSI
# ============================================
function Log-Info($message) { Write-Host "[INFO] $message" -ForegroundColor Cyan }
function Log-Success($message) { Write-Host "[SUCCESS] $message" -ForegroundColor Green }
function Log-Error($message) { Write-Host "[ERROR] $message" -ForegroundColor Red }
function Log-Warning($message) { Write-Host "[WARNING] $message" -ForegroundColor Yellow }

# ============================================
# MAIN SCRIPT
# ============================================
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

# Target Sync
$Databases = @(
    @{ Name = "kpi_bubut"; Container = "masterdatakpi-db"; User = "root" },
    @{ Name = "masterdata_kpi"; Container = "masterdatakpi-db"; User = "root" }
)

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "   PRODUCTION TO LOCAL DB SYNC SCRIPT" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta

# Step 1: Buat direktori backup lokal
if (!(Test-Path $LocalBackupDir)) {
    New-Item -ItemType Directory -Path $LocalBackupDir -Force | Out-Null
}

foreach ($Db in $Databases) {
    $DbName = $Db.Name
    $Container = $Db.Container
    $DbUser = $Db.User
    
    $backupFileName = "prod_$( $DbName )_$timestamp.sql"
    $serverBackupPath = "$ServerBackupDir/$backupFileName"
    $localBackupPath = "$LocalBackupDir\$backupFileName"

    Write-Host "`n>>> SINKRONISASI DATABASE: $DbName" -ForegroundColor Yellow
    
    # 1. Export di Server (Direct Docker Exec)
    Log-Info "Mengekspor $DbName dari container $Container di server..."
    # Kita gunakan docker exec langsung ke container name untuk menghindari issue path/compose
    $exportCmd = "docker exec -i $Container mysqldump -u$DbUser -p123456788 $DbName > $serverBackupPath"
    
    ssh $ServerSSH $exportCmd
    if ($LASTEXITCODE -ne 0) {
        Log-Error "Gagal ekspor $DbName. Pastikan SSH key terpasang dan sudo tidak butuh password."
        continue
    }

    # 2. Download File
    Log-Info "Mendownload $backupFileName..."
    scp "${ServerSSH}:$serverBackupPath" $localBackupPath
    if ($LASTEXITCODE -ne 0) {
        Log-Error "Gagal mendownload file."
        continue
    }

    # 3. Import ke Lokal
    Log-Info "Menginput data ke MySQL Lokal (Laragon)..."
    try {
        if (Test-Path $localBackupPath) {
            Get-Content -Raw $localBackupPath | & $LaragonMysql -u$LocalDbUser -p$LocalDbPass $DbName
            if ($LASTEXITCODE -eq 0) {
                Log-Success "Sinkronisasi $DbName SELESAI!"
            }
            else {
                Log-Error "Gagal mengimport ke database lokal (Exit Code: $LASTEXITCODE)."
            }
        }
        else {
            Log-Error "File backup lokal tidak ditemukan: $localBackupPath"
        }
    }
    catch {
        Log-Error "Exception saat import: $_"
    }

    # 4. Cleanup Server
    Log-Info "Membersihkan file temporary di server..."
    ssh $ServerSSH "rm -f $serverBackupPath"
}

# Step Final: Bersihkan backup lama di lokal
Log-Info "`nMembersihkan backup lama di lokal ( > $KeepDays hari)..."
Get-ChildItem "$LocalBackupDir\prod_*.sql" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays) } | Remove-Item -Force

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "   SINKRONISASI SELESAI - DATA TERBARU!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
