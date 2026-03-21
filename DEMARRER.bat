@echo off
title Agent Impression Kadoo

:: Ajouter au démarrage Windows automatiquement (une seule fois)
set STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
if not exist "%STARTUP%\kadoo-printer.bat" (
    copy "%~dp0DEMARRER.bat" "%STARTUP%\kadoo-printer.bat"
    echo Agent ajouté au démarrage automatique.
)

:: Lancer l'agent
cd /d "%~dp0src"
..\php\php.exe -S localhost:8765 agent.php
pause