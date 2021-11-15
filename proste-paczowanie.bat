@echo off

@setlocal

set APP_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=%APP_PATH%bin\php.exe

"%PHP_COMMAND%" "%APP_PATH%app.php" -s

@endlocal
@pause
