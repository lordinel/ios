@echo off
rem IOS Backup Utility
rem Version : 1.0
rem Updated : July 12, 2011
rem Author  : Lordinel Grajo

echo Performing database backup....
echo Do not close this window.

rem set timestamp
rem for format mm/dd/yyyy
rem set timestamp=%DATE:~10,4%-%DATE:~4,2%-%DATE:~7,2%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%
rem for format dd/mm/yyyy
set timestamp=%DATE:~6,4%-%DATE:~0,2%-%DATE:~3,2%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%

rem set backup path
rem set backuppath=C:\wamp\www\ios\dbbackups\
set backuppath="C:\Users\EDIC\Documents\IOS Backups\"

rem disable foreign key checks
echo SET FOREIGN_KEY_CHECKS=0; > "%backuppath%ios_%timestamp%.sql"

rem get data only
C:\wamp\bin\mysql\mysql5.7.14\bin\mysqldump -u root --databases ios --compact --complete-insert --extended-insert --no-create-db --no-create-info --order-by-primary --skip-comments --log-error="%backuppath%ios_dump_error.log" >> %backuppath%ios_%timestamp%.sql

rem enable foreign key checks
echo SET FOREIGN_KEY_CHECKS=1; >> "%backuppath%ios_%timestamp%.sql"
