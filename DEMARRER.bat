@echo off
title Agent Impression Kadoo
echo =============================
echo   Agent Impression Kadoo
echo   Ne pas fermer cette fenetre
echo =============================
cd /d "C:\KadooPrinter\src"
..\php\php.exe -S localhost:8765 agent.php
pause