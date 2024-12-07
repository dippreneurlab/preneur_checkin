<?php
date_default_timezone_set('Asia/Dhaka');

//setup functions
function copy_env_file(){
    $envExampleFile = __DIR__ . '/.env.example';
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        if (file_exists($envExampleFile)) {
            if (copy($envExampleFile, $envFile)) {
                echo ".env file has been created from .env.example.\n";
            } else {
                echo "Failed to copy .env.example to .env.\n";
            }
        } else {
            echo ".env.example file not found.\n";
        }
    } else {
        echo ".env file already exists.\n";
    }
}
function update_env_file($db_host, $db_user, $db_password, $db_name) {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        $envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=' . $db_host, $envContent);
        $envContent = preg_replace('/DB_USER=.*/', 'DB_USER=' . $db_user, $envContent);
        $envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . $db_password, $envContent);
        $envContent = preg_replace('/DB_NAME=.*/', 'DB_NAME=' . $db_name, $envContent);
        file_put_contents($envFile, $envContent);
        echo ".env file has been updated successfully.\n";
    } else {
        echo ".env file not found to update.\n";
    }
}
function load_env($file = '.env') {
    if (!file_exists($file)) {
        throw new Exception("Environment file ($file) not found.");
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
function get_db() {
    load_env();
    $servername = getenv('DB_HOST');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASS');
    $dbname = getenv('DB_NAME');
    
    $charset = 'utf8';
    try {
        $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=$charset", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        return null;
    }
}
function close_db($db) {
    $db = null;
}

function setup_db(){
    $sqlFile = __DIR__ . '/plab_checkin.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found: $sqlFile\n");
    }
    $sql = file_get_contents($sqlFile);
    try {
        $db = get_db();
        $db->exec($sql);
        echo "SQL script executed successfully.\n";
        close_db($db);
    } catch (PDOException $e) {
        die("Error executing SQL: " . $e->getMessage());
    }
}
function addCronJob($cronCommand) {
    $output = [];
    exec("crontab -l", $output, $returnVar);
    if ($returnVar !== 0) {
        $output = [];
    }
    $currentCronJobs = implode("\n", $output);
    if (strpos($currentCronJobs, $cronCommand) !== false) {
        return "Cron job already exists.";
    }
    $newCronJobs = $currentCronJobs . "\n" . $cronCommand . "\n";
    $tmpFile = tempnam(sys_get_temp_dir(), 'cron');
    file_put_contents($tmpFile, $newCronJobs);
    exec("crontab $tmpFile", $output, $returnVar);
    unlink($tmpFile);
    if ($returnVar === 0) {
        return "Cron job added successfully.";
    } else {
        return "Failed to add cron job.";
    }
}
function setup_cron(){
    $cron_file = "get_user_details.php";
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $currentUri = $_SERVER['REQUEST_URI'];
    $folderPath = rtrim(dirname($currentUri), '/');
    $cron_path = $protocol . $host. $folderPath . '/' . $cron_file;
    $cronCommand = "0 0 10 * * curl -O $cron_path";
    $result = addCronJob($cronCommand);
    echo $result;
}


//app functions
function is_govt_holiday($db) {
    $today = date("Y-m-d");
    $stmt = $db->prepare("SELECT type, name FROM plab_holidays WHERE date_time = :today");
    if ($stmt->execute([':today' => $today])) {
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($holiday) {
            return "No need to check in today, today is {$holiday['type']} - {$holiday['name']}, enjoy your holiday!";
        }
    }
    return false;
}

function is_friday() {
    return date('N') == 5;
}

function get_user_data($db) {
    $stmt = $db->prepare("SELECT json_data FROM plab_active_users WHERE is_updated = 1 ORDER BY id DESC LIMIT 1");
    if ($stmt->execute()) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result['json_data'])) {
            return json_decode($result['json_data'], true);
        }
    }
    return [];
}

function validate_status($status) {
    $allowed_statuses = ["REGULAR OFFICE", "WORK FROM HOME"];
    if (!isset($status) || trim($status) === '' || strtolower($status) == 'regular') $status = "Regular Office";
    elseif(strtolower($status) == 'wfh') $status = "Work from Home";
    return in_array(strtoupper($status), $allowed_statuses) ? $status : false;
}

function get_email_and_user_id($username, $user_data) {
    $possible_domains = ["@gmail.com", "@preneurlab.com", "@preneurlab.org"];
    foreach ($possible_domains as $domain) {
        $email = $username . $domain;
        foreach ($user_data as $id => $user_row) {
            $user_email = $user_row['email'];
            $user_id = $user_row['id'];
            if ($email === $user_email) {
                return ['email' => $user_email, 'user_id' => (int)$user_id];
            }
        }
    }
    return false;
}

function check_in($email, $user_id, $status) {
    $attendance_url = "https://preneurlab.com/work/attendance.php";
    $email_encoded = str_replace("@", "%40", $email);

    $postData = [
        'status' => $status,
        'checkButton' => 'checkinoffice',
    ];
    $cookieString = "uml=$email_encoded; user_id=$user_id; auth=$email_encoded";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $attendance_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return json_encode(["Message" => "Error! $error", "Code" => 503]);
    } else {
        $date = date("Y-m-d");
        $time = date("H:i:s");
        if (strpos($response, 'Already Attendance Successfully..!') !== false) {
            return json_encode(["Message" => "You have already checked in for today!", "Code" => 200]);
        }
        return json_encode(["Message" => "Check-in Successful for $date at $time. Get back to work!", "Code" => 200]);
    }
}
function decodeCfEmail($cfEmail) {
    $key = hexdec(substr($cfEmail, 0, 2));
    $email = '';
    for ($i = 2; $i < strlen($cfEmail); $i += 2) {
        $email .= chr(hexdec(substr($cfEmail, $i, 2)) ^ $key);
    }
    return $email;
}
function get_user_info_dom(){
    $url = "https://preneurlab.com/work/all_users.php";
    $email = "tanvir.preneurlab%40gmail.com";
    $user_id_key = "46";
    $cookieString = "uml=$email; user_id=$user_id_key; auth=$email";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    setcookie("uml", "", time() - 3600, "/");
    setcookie("user_id", "", time() - 3600, "/");
    setcookie("auth", "", time() - 3600, "/");
    curl_close($ch);
    return ($response === false) ? false : $response;

}
function user_info_extract($response){
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($response);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $userCards = $xpath->query('//div[contains(@class, "single-user")]');
    $activeUsers = [];

    foreach ($userCards as $card) {
        $inactiveCheck = $xpath->query('.//p[contains(@class, "position")]/span[@class="inactive"]', $card);
        if ($inactiveCheck->length > 0) {
            continue;
        }

        $idNode = $xpath->query('.//h6/a[contains(@href, "assign_user_edit.php?id=")]', $card);
        $userId = null;
        if ($idNode->length > 0) {
            preg_match('/id=(\d+)/', $idNode[0]->getAttribute('href'), $matches);
            $userId = $matches[1] ?? null;
        }

        $nameNode = $xpath->query('.//h6/a[contains(@href, "assign_user_edit.php?id=")]', $card);
        $userName = $nameNode->length > 0 ? trim($nameNode[0]->nodeValue) : null;

        $emailNode = $xpath->query('.//a[@class="__cf_email__"]', $card);
        $userEmail = null;
        if ($emailNode->length > 0) {
            $cfEmail = $emailNode[0]->getAttribute('data-cfemail');
            $userEmail = decodeCfEmail($cfEmail);
        }
        if ($userId && $userName && $userEmail) {
            $activeUsers[$userId] = [
                'id' => $userId,
                'name' => $userName,
                'email' => $userEmail,
            ];
        }
    }
    $activeUsers = array_values($activeUsers);
    $jsonData = json_encode($activeUsers, JSON_PRETTY_PRINT);
    return empty($jsonData) ? false : $jsonData;
}
function store_active_user_info($db, $jsonData){
    $dateTime = date('Y-m-d H:i:s');
    try {
        $db->exec("UPDATE plab_active_users SET is_updated = 0");
        $stmt = $db->prepare("INSERT INTO plab_active_users (json_data, date_time, is_updated) VALUES (:json_data, :date_time, 1)");
        $stmt->execute([
            ':json_data' => $jsonData,
            ':date_time' => $dateTime
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>