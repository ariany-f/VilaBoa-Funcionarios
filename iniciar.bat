@echo off
chcp 65001 > nul
echo ========================================================
echo       PORTAL DO FUNCIONÁRIO - VILA BOA CONSTRUÇÕES
echo ========================================================
echo Iniciando o servidor PHP embutido na porta 8000...
echo Acesse no navegador ou no celular: http://localhost:8000
echo Pressione Ctrl+C para encerrar o servidor.
echo ========================================================

start http://localhost:8000
php -S 0.0.0.0:8000 -t backend/public backend/public/index.php
pause
