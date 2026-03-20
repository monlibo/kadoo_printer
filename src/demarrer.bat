@echo off
echo Agent d'impression Kadoo...
echo Ne pas fermer cette fenetre.
cd /d "%~dp0"
php -S localhost:8765 agent.php
pause