@echo off
echo [WARNING] This will OVERWRITE your local database 'erp_global' with the version from Git.
echo [WARNING] All local data changes since last backup will be LOST.
choice /m "Are you sure you want to continue?"
if errorlevel 2 goto :eof

echo [INFO] Importing database...
c:\xampp\mysql\bin\mysql.exe -u root erp_global < "%~dp0..\sql\schema_dump.sql"
if %errorlevel% neq 0 (
    echo [ERROR] Import failed!
    pause
    exit /b %errorlevel%
)
echo [SUCCESS] Database restored from sql\schema_dump.sql
pause
