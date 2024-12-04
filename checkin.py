from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from webdriver_manager.chrome import ChromeDriverManager
from dotenv import load_dotenv
import os
import logging
from datetime import datetime
import pytz 

logging.basicConfig(
    filename='login.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def is_friday():
    dhaka_tz = pytz.timezone('Asia/Dhaka')
    now_in_dhaka = datetime.now(dhaka_tz)
    return now_in_dhaka.weekday() == 4

def login_try(login_url, home_url, email, password):
    try:
        driver.get(login_url)
        username_field = driver.find_element(By.NAME, 'email')
        password_field = driver.find_element(By.NAME, 'password')
        username_field.send_keys(email)
        password_field.send_keys(password)
        login_button = driver.find_element(By.NAME, 'login')
        login_button.click()

        WebDriverWait(driver, 10).until(
            EC.url_to_be(home_url)
        )
        if driver.current_url == home_url:
            logging.info("Login successful for user: %s", email)
            return True
        else:
            logging.warning("Login failed for user: %s", email)
            return False
    except Exception as e:
        logging.error("Exception during login for user: %s - %s", email, str(e))
        return False

def attendance_try(attendance_url):
    try:
        driver.get(attendance_url)
        work_modality_dropdown = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, 'status'))
        )
        select = Select(work_modality_dropdown)
        select.select_by_visible_text('Regular Office')
        checkin_button = driver.find_element(By.NAME, 'checkButton')
        checkin_button.click()
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CLASS_NAME, 'success-message'))
        )
        logging.info("Attendance marked successfully")
    except Exception as e:
        logging.error("Exception during attendance marking: %s", str(e))


load_dotenv()
email = os.getenv('EMAIL')
password = os.getenv('PASSWORD')
login_url = os.getenv('LOGIN_URL')
home_url = os.getenv('HOME_URL')
attendance_url = os.getenv('ATTENDANCE_URL')

service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

if is_friday():
    logging.info("Today is Friday. Skipping login and attendance.")
else:
    try:
        login_success = login_try(login_url, home_url, email, password)
        if login_success:
            attendance_try(attendance_url)
    finally:
        driver.quit()
