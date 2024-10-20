from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from webdriver_manager.chrome import ChromeDriverManager

# Setup ChromeDriver
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

# Open the login page
driver.get('https://preneurlab.com/work/login.php')
target_url_after_login = "https://preneurlab.com/work/index.php"
attendance_url = "https://preneurlab.com/work/attendance.php"

# Find and interact with the username and password fields
username_field = driver.find_element(By.NAME, 'email')
password_field = driver.find_element(By.NAME, 'password')

username_field.send_keys('dip.preneurlab@gmail.com')
password_field.send_keys('preneurMAIL*2023')

# Click the login button
login_button = driver.find_element(By.NAME, 'login')
login_button.click()

# Wait until the URL changes to the target URL after login
WebDriverWait(driver, 10).until(
    EC.url_to_be(target_url_after_login)
)

# Check if the login was successful
if driver.current_url == target_url_after_login:
    print("Login successful!")
    
    # Navigate to the attendance page
    driver.get(attendance_url)
    
    # Wait until the dropdown is present and interact with it
    work_modality_dropdown = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, 'status'))
    )
    select = Select(work_modality_dropdown)
    select.select_by_visible_text('Regular Office')
    
    # Click the check-in button
    checkin_button = driver.find_element(By.NAME, 'checkButton')
    checkin_button.click()
    
    # Wait until the success message is present
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, 'success-message'))  # Adjust based on the actual success message element
    )
    print("Check-in successful!")
else:
    print("Login failed. Current URL:", driver.current_url)

# Close the browser
driver.quit()
