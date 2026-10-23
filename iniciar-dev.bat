@echo off
chcp 65001 > nul
echo ========================================================
echo   PORTAL DO FUNCIONÁRIO VILA BOA (MODO DESENVOLVIMENTO)
echo ========================================================
echo Iniciando Backend PHP (porta 8000) e Frontend Vite (porta 5173)...
echo.

start "Backend PHP API" cmd /k "php -S 127.0.0.1:8000 -t backend/public backend/public/index.php"
start "Frontend Vite" cmd /k "cd frontend && npm run dev"

echo Aguarde 2 segundos para abrir o navegador...
timeout /t 2 /nobreak > nul
start http://localhost:5173
