@echo off

@setlocal

set PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=%PATH%bin\php.exe

"%PHP_COMMAND%" "%PATH%app.php" %*

@endlocal
@pause
