@echo off
:: Vérifier si on est administrateur, sinon redemander
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Relancement en administrateur...
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit
)

title Installation Kadoo Printer
echo =============================
echo   Installation Kadoo Printer
echo =============================

xcopy /E /I /Y "%~dp0" "C:\KadooPrinter\"

netsh advfirewall firewall add rule name="Kadoo Printer" dir=in action=allow protocol=TCP localport=8765

set STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
copy "C:\KadooPrinter\DEMARRER.bat" "%STARTUP%\kadoo-printer.bat"

echo.
echo Installation terminee !
echo.
pause

start "" "C:\KadooPrinter\DEMARRER.bat"