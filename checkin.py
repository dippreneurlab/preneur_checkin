from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from webdriver_manager.chrome import ChromeDriverManager
from dotenv import load_dotenv
import os

def login_try(login_url, home_url, email, password):
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
    if driver.current_url == home_url: return True
    else: return False

def attendance_try(attendance_url):
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

load_dotenv()
email = os.getenv('EMAIL')
password = os.getenv('PASSWORD')
login_url = os.getenv('LOGIN_URL')
home_url = os.getenv('HOME_URL')
attendance_url = os.getenv('ATTENDANCE_URL')

service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

new_login = login_try(login_url, home_url, email, password)
if new_login == True:
    new_attendance_url = attendance_try(attendance_url)
driver.quit()
