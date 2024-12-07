<?php
require_once "all_functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $db = get_db();
    if (!$db) {
        echo json_encode(["Message" => "Error connecting to the database.", "Code" => 500]);
        exit;
    }

    if (is_friday()) {
        echo json_encode(["Message" => "No need to check in today, it's Friday. Enjoy your holiday!", "Code" => 403]);
        exit;
    }

    $holidayMessage = is_govt_holiday($db);
    if ($holidayMessage) {
        echo json_encode(["Message" => $holidayMessage, "Code" => 403]);
        exit;
    }

    $username = filter_var($_GET['username'], FILTER_SANITIZE_STRING);
    if (empty($username)) {
        echo json_encode(["Message" => "Username Required!", "Code" => 400]);
        exit;
    }

    $status = htmlspecialchars($_GET['status'], ENT_QUOTES, 'UTF-8');
    $validated_status = validate_status($status);
    if (!$validated_status) {
        echo json_encode(["Message" => "Error! Invalid status. Allowed statuses are: REGULAR OFFICE, WORK FROM HOME", "Code" => 400]);
        exit;
    }

    $user_data = get_user_data($db);
    $user_info = get_email_and_user_id($username, $user_data);
    if (!$user_info) {
        echo json_encode(["Message" => "Error! Sorry! You don't exist in the PreneurLab database anymore!", "Code" => 401]);
        exit;
    }

    $email = $user_info['email'];
    $user_id = $user_info['user_id'];
    echo check_in($email, $user_id, $validated_status);
    close_db($db);
    exit;
}
