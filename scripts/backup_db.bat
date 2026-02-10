@echo off
echo [INFO] Exporting database 'erp_global'...
c:\xampp\mysql\bin\mysqldump.exe -u root --skip-extended-insert erp_global > "%~dp0..\sql\schema_dump.sql"
if %errorlevel% neq 0 (
    echo [ERROR] Export failed!
    pause
    exit /b %errorlevel%
)
echo [SUCCESS] Database exported to sql\schema_dump.sql
pause
