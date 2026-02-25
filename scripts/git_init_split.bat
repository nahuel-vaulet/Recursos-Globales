@echo off
REM ──────────────────────────────────────────────
REM  Git Init Script for Backend/Frontend Split
REM  Run this from the APP-Prueba root directory
REM ──────────────────────────────────────────────

echo.
echo === Preparación de repositorios Git ===
echo.

REM ─── Backend Repo ──────────────────────────
echo [1/4] Inicializando repositorio Backend...
cd /d "%~dp0backend"
git init
copy .env.example .env
echo [OK] Backend git init

REM ─── Install Backend Dependencies ──────────
echo [2/4] Instalando dependencias Backend (composer)...
IF EXIST "..\composer.phar" (
    php ..\composer.phar install --no-dev --optimize-autoloader
) ELSE (
    composer install --no-dev --optimize-autoloader
)
echo [OK] Backend dependencies installed

REM ─── Backend First Commit ──────────────────
echo [3/4] Staging Backend files...
git add -A
git commit -m "chore: initial backend API structure (Fase 0)"
echo [OK] Backend committed

REM ─── Frontend Repo ─────────────────────────
echo [4/4] Inicializando repositorio Frontend...
cd /d "%~dp0frontend"
git init
git add -A
git commit -m "chore: initial frontend SPA structure (Fase 0)"
echo [OK] Frontend committed

echo.
echo === Repositorios listos ===
echo.
echo Próximos pasos:
echo   1. Agregar remotes:
echo      cd backend ^&^& git remote add origin https://github.com/USUARIO/erp-backend.git
echo      cd frontend ^&^& git remote add origin https://github.com/USUARIO/erp-frontend.git
echo   2. Push:
echo      git push -u origin main
echo.
pause
