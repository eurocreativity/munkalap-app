@echo off
echo Composer telepitese...
echo.

REM Composer telepito letoltese
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

echo.
echo Composer telepites kesz!
echo.

pause


