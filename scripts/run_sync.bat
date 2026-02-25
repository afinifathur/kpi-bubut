@echo off
set "SCRIPT_DIR=C:\laragon\www\kpi-bubut\scripts"
set "LOG_FILE=%SCRIPT_DIR%\sync_log.txt"

echo [BATCH] Starting Sync at %date% %time%...
powershell.exe -ExecutionPolicy Bypass -File "%SCRIPT_DIR%\sync_production_db.ps1"

if %ERRORLEVEL% EQU 0 (
    echo Sync Finished at %date% %time% [SUCCESS] >> "%LOG_FILE%"
    echo [BATCH] Sync completed successfully.
) else (
    echo Sync Finished at %date% %time% [FAILED] >> "%LOG_FILE%"
    echo [BATCH] Sync failed! Check sync_log_detail.txt for details.
    exit /b %ERRORLEVEL%
)
