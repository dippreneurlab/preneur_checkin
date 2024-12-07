<strong>Database Setup and Cron Job Configuration</strong>


Clone this repository to your server or local machine:

```bash
git clone https://github.com/dippreneurlab/.git
```

Set Permissions:

Ensure that the web server has write permissions to the following files:

.env
setup.php
Set the permissions using the following command:

```bash
chmod 755 .env setup.php
```

Project Structure

```bash
/project-root
  ├── all_functions.php        # Contains all PHP functions
  ├── setup.php                # Main setup page for configuring database and cron job
  ├── .env.example             # Example environment file template
  ├── plab_checkin.sql         # SQL file for setting up database tables (must be available)
  └── office_login.php         # Script for handling office login checks (API-based)
```

<strong>Files Explanation</strong>

<ol>
  <li>
    setup.php - This file handles the setup process when the form is submitted. It performs the following actions:
    <ul>
      <li>Copies the .env.example file to .env if it doesn't already exist.</li>
      <li>Updates the .env file with database connection details (host, user, password, and database name).</li>
      <li>Executes the database setup by running the SQL script plab_checkin.sql.</li>
      <li>Configures a cron job for periodic execution of a script (get_user_details.php).</li>
    </ul>
  </li>
  <li>
    all_functions.php - This file contains all the helper functions necessary for: setup and application logics
  </li>
  <li>
    office_login.php - This file is the main endpoint for checking the office login status. 
  </li>
</ol>
