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
$LaragonMysql = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
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
$DetailedLogFile = "C:\laragon\www\kpi-bubut\scripts\sync_log_detail.txt"

function Log-Message($level, $message) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$level] $message"
    Write-Host $logEntry -ForegroundColor ($ifelse = if ($level -eq "ERROR") { "Red" } elseif ($level -eq "SUCCESS") { "Green" } elseif ($level -eq "WARNING") { "Yellow" } else { "Cyan" })
    Add-Content -Path $DetailedLogFile -Value $logEntry
}

function Log-Info($message) { Log-Message "INFO" $message }
function Log-Success($message) { Log-Message "SUCCESS" $message }
function Log-Error($message) { Log-Message "ERROR" $message }
function Log-Warning($message) { Log-Message "WARNING" $message }

# ============================================
# MAIN SCRIPT
# ============================================
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

# Target Sync
$Databases = @(
    @{ Name = "kpi_bubut"; Container = "masterdatakpi-db"; User = "root" },
    @{ Name = "masterdata_kpi"; Container = "masterdatakpi-db"; User = "root" }
)

Log-Info "============================================"
Log-Info "   PRODUCTION TO LOCAL DB SYNC SCRIPT"
Log-Info "============================================"

# Step 1: Buat direktori backup lokal
if (!(Test-Path $LocalBackupDir)) {
    New-Item -ItemType Directory -Path $LocalBackupDir -Force | Out-Null
}

$GlobalSuccess = $true

foreach ($Db in $Databases) {
    $DbName = $Db.Name
    $Container = $Db.Container
    $DbUser = $Db.User
    
    $backupFileName = "prod_$( $DbName )_$timestamp.sql"
    $serverBackupPath = "$ServerBackupDir/$backupFileName"
    $localBackupPath = "$LocalBackupDir\$backupFileName"

    Log-Info ""
    Log-Info ">>> SINKRONISASI DATABASE: $DbName"
    
    # 1. Export di Server (Direct Docker Exec)
    Log-Info "Mengekspor $DbName dari container $Container di server..."
    $exportCmd = "docker exec -i $Container mysqldump -u$DbUser -p123456788 $DbName > $serverBackupPath"
    
    ssh $ServerSSH $exportCmd 2>&1 | Add-Content -Path $DetailedLogFile
    if ($LASTEXITCODE -ne 0) {
        Log-Error "Gagal ekspor $DbName. Pastikan SSH key terpasang dan sudo tidak butuh password."
        $GlobalSuccess = $false
        continue
    }

    # 2. Download File
    Log-Info "Mendownload $backupFileName..."
    scp "${ServerSSH}:$serverBackupPath" $localBackupPath 2>&1 | Add-Content -Path $DetailedLogFile
    if ($LASTEXITCODE -ne 0) {
        Log-Error "Gagal mendownload file."
        $GlobalSuccess = $false
        continue
    }

    # 3. Import ke Lokal
    Log-Info "Menginput data ke MySQL Lokal (Laragon)..."
    if (Test-Path $localBackupPath) {
        # Gunakan CMD redirection untuk stabilitas lebih baik pada file besar
        Log-Info "Menjalankan import menggunakan CMD redirection..."
        $importCmd = "cmd /c `"`"$LaragonMysql`" -u$LocalDbUser -p$LocalDbPass $DbName < `"$localBackupPath`"`""
        Invoke-Expression $importCmd 2>&1 | Add-Content -Path $DetailedLogFile
        
        if ($LASTEXITCODE -eq 0) {
            Log-Success "Sinkronisasi $DbName SELESAI!"
        }
        else {
            Log-Error "Gagal mengimport ke database lokal (Exit Code: $LASTEXITCODE). Cek sync_log_detail.txt untuk detail."
            $GlobalSuccess = $false
        }
    }
    else {
        Log-Error "File backup lokal tidak ditemukan: $localBackupPath"
        $GlobalSuccess = $false
    }

    # 4. Cleanup Server
    Log-Info "Membersihkan file temporary di server..."
    ssh $ServerSSH "rm -f $serverBackupPath" 2>&1 | Add-Content -Path $DetailedLogFile
}

# Step Final: Bersihkan backup lama di lokal
Log-Info ""
Log-Info "Membersihkan backup lama di lokal ( > $KeepDays hari)..."
Get-ChildItem "$LocalBackupDir\prod_*.sql" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays) } | Remove-Item -Force

Log-Info ""
Log-Info "============================================"
if ($GlobalSuccess) {
    Log-Success "   SINKRONISASI SELESAI - DATA TERBARU!"
} else {
    Log-Error "   SINKRONISASI SELESAI DENGAN BEBERAPA KEGAGALAN."
    exit 1
}
Log-Info "============================================"
Log-Info ""
