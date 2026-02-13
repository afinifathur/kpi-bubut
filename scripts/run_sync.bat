@echo off
powershell.exe -ExecutionPolicy Bypass -File "C:\laragon\www\kpi-bubut\scripts\sync_production_db.ps1"
echo Sync Finished at %date% %time% >> "C:\laragon\www\kpi-bubut\scripts\sync_log.txt"
