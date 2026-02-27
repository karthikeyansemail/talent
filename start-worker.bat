@echo off
title Talent Platform - Queue Worker
echo ============================================
echo  Talent Intelligence Platform Queue Worker
echo  Keep this window open while developing.
echo  Press Ctrl+C to stop.
echo ============================================
echo.
cd /d C:\xampp\htdocs\talent
C:\xampp\php\php.exe artisan queue:work --timeout=300 --tries=2 --verbose
pause
