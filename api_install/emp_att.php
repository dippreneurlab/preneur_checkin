<?php
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
function fetchAttendanceHtml() { 
    $url = "https://preneurlab.com/work/employe_attendance.php";
    $ch = curl_init($url);
    $cookieString = "auth=tanvir.preneurlab%40gmail.com; uml=tanvir.preneurlab%40gmail.com; user_id=46";
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_COOKIE    => $cookieString, 
    ]);
    $html = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    setcookie("uml", "", time() - 3600, "/");
    setcookie("user_id", "", time() - 3600, "/");
    setcookie("auth", "", time() - 3600, "/");
    if ($error || $httpCode !== 200) {
        echo json_encode(['message' => "something went wrong"]);
        exit;
    }
    return $html;
}
function parseAttendanceData($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query("//tr[contains(@class, 'row100')]");
    if ($rows->length === 0) {
        echo json_encode(['message' => "something went wrong"]);
    }
    $result = [];
    foreach ($rows as $row) {
        $columns = $row->getElementsByTagName('td');
        if ($columns->length >= 3) {
            $nameNode = $columns->item(0);
            $checkInNode = $columns->item(1);
            $name = trim(explode("\n", $nameNode->nodeValue)[0]);
            $checkIn = trim($checkInNode->childNodes->item(0)->nodeValue ?? '');
            if ($checkIn !== '') {
                $result[] = [
                    'name' => $name,
                    'check_in' => $checkIn
                ];
            }
        }
    }
    return $result;
}

$html = fetchAttendanceHtml();
$checkinData = parseAttendanceData($html);
usort($checkinData, function ($a, $b) {
    return strtotime($a['check_in']) - strtotime($b['check_in']);
});
echo json_encode($checkinData, JSON_PRETTY_PRINT);
exit; 
