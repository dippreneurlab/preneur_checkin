import os
import platform
import shutil
import subprocess
from pathlib import Path

def install_requirements():
    print("Installing dependencies...")
    try:
        subprocess.check_call(["pip", "install", "-r", "requirements.txt"])
        print("Dependencies installed successfully!")
    except subprocess.CalledProcessError:
        print("Failed to install dependencies. Ensure pip is installed and try again.")
        exit(1)

def setup_env():
    if not Path(".env.example").exists():
        print(".env.example file is missing!")
        exit(1)
    
    if not Path(".env").exists():
        shutil.copy(".env.example", ".env")
    
    email = input("Enter your email: ")
    password = input("Enter your password: ")

    with open(".env", "r") as file:
        lines = file.readlines()
    
    with open(".env", "w") as file:
        for line in lines:
            if line.startswith("EMAIL="):
                file.write(f"EMAIL={email}\n")
            elif line.startswith("PASSWORD="):
                file.write(f"PASSWORD={password}\n")
            else:
                file.write(line)
    print(".env file updated with your credentials!")

def create_login_log():
    log_file = Path("login.log")
    if not log_file.exists():
        log_file.touch()
        print("log file created.")
    else:
        print("log file already exists.")

def setup_autorun():
    os_name = platform.system().lower()
    print(f"Setting up auto-run for {os_name}...")

    if os_name == "windows":
        bat_file_content = f"""@echo off
python "{os.path.abspath('checkin.py')}"
"""
        with open("autorun.bat", "w") as bat_file:
            bat_file.write(bat_file_content)
        print("Auto-run setup for Windows created. Add `autorun.bat` to Task Scheduler.")

    elif os_name == "darwin":
        plist_content = f"""<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.user.checkin</string>
    <key>ProgramArguments</key>
    <array>
        <string>/usr/bin/python3</string>
        <string>{os.path.abspath('checkin.py')}</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
</dict>
</plist>
"""
        plist_file = Path("~/Library/LaunchAgents/com.user.checkin.plist").expanduser()
        with open(plist_file, "w") as plist:
            plist.write(plist_content)
        print("Auto-run setup for Mac created. Load the plist using `launchctl load`.")

    elif os_name == "linux":
        cron_command = f"@reboot python3 {os.path.abspath('checkin.py')}\n"
        os.system(f"(crontab -l; echo '{cron_command}') | crontab -")
        print("Auto-run setup for Linux added to crontab.")

    else:
        print("Unsupported OS for auto-run setup.")
        exit(1)

def test_login():
    from dotenv import load_dotenv
    import requests

    load_dotenv()
    email = os.getenv("EMAIL")
    password = os.getenv("PASSWORD")
    login_url = os.getenv("LOGIN_URL")

    if not all([email, password, login_url]):
        print("Environment variables for login are missing!")
        exit(1)
    print("Attempting login...")
    response = requests.post(login_url, data={"email": email, "password": password})
    if response.status_code == 200:
        print("Login successful!")
        print("Installed!")
    else:
        print("Login failed. Please check your credentials or server configuration.")
        exit(1)

def main():
    install_requirements()
    setup_env()
    create_login_log()
    setup_autorun()
    test_login()

if __name__ == "__main__":
    main()
