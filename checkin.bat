@echo off
REM Set the log file path
set logfile=C:\Users\devic\Documents\login.log

echo Script started...

for /f %%a in ('powershell -command "([System.TimeZoneInfo]::FindSystemTimeZoneById('Asia/Dhaka')).ConvertTime([DateTime]::UtcNow, [System.TimeZoneInfo]::FindSystemTimeZoneById('Asia/Dhaka')).ToString('yyyy-MM-dd HH:mm:ss')"') do set currentdatetime=%%a

if "%currentdatetime%"=="" (
    echo Failed to retrieve current date and time from PowerShell. Exiting...
    exit /b 1
)

echo Current Dhaka DateTime: %currentdatetime%

for /f "tokens=1 delims= " %%a in ("%currentdatetime%") do set currentdate=%%a

echo Current Dhaka Date: %currentdate%

for /f %%a in ('powershell -command "(Get-Date).DayOfWeek"') do set dayofweek=%%a

if "%dayofweek%"=="" (
    echo Failed to retrieve the day of the week from PowerShell. Exiting...
    exit /b 1
)

echo Current Dhaka Day of the Week: %dayofweek%

if not exist %logfile% (
    echo Log file does not exist, creating a new log entry for %currentdate%
    echo %currentdate% >> %logfile%
) else (
    set /p lastdate=<%logfile%
    echo Last Logged Date: %lastdate%
    if not "%currentdate%"=="%lastdate%" (
        echo New day detected, appending to the log file and checking if today is not Friday.
        echo %currentdate% >> %logfile%
        if /i not "%dayofweek%"=="Friday" (
            echo Running Python script...
            python C:\Users\devic\Documents\checkin.py
            if errorlevel 1 (
                echo Python script failed to run. Exiting...
            ) else (
                echo Python script executed successfully.
            )
        ) else (
            echo It's Friday, not running the Python script.
        )
    ) else (
        echo Same date as last log entry, no action taken.
    )
)
echo Script completed.
exit
