<?php
require_once "all_functions.php";
$response = get_user_info_dom();
if ($response === false) {
    echo json_encode(["Message" => "Error! something went wrong!", "Code" => 503]);
    exit;
    
}
$jsonData = user_info_extract($response);
$db = get_db();
if(!store_active_user_info($db, $jsonData)){
    echo json_encode(["Message" => "Database Error!", "Code" => 500]);
    exit;
}
close_db($db);
echo $jsonData;
exit;
?>
