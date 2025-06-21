<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');
$url = "https://preneurlab.com/work/holiday.php";
$ch = curl_init($url);
$cookieString = "auth=dip.preneurlab%40gmail.com; uml=dip.preneurlab%40gmail.com; user_id=103";
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_COOKIE => $cookieString,
]);

$html = curl_exec($ch);
curl_close($ch);
setcookie("uml", "", time() - 3600, "/");
setcookie("user_id", "", time() - 3600, "/");
setcookie("auth", "", time() - 3600, "/");
if ($html === false) {
    echo json_encode(['error' => 'Failed to fetch data from the URL.']);
    exit;
}
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);
$rows = $xpath->query("//table//tr[@class='row100']");
$currentMonth = date('Y-m');
$holidays = [];
foreach ($rows as $row) {
    $cells = $row->getElementsByTagName('td');
    if ($cells->length >= 4) {
        $date = trim($cells->item(1)->nodeValue);
        if (strpos($date, $currentMonth) === 0) {
            $holidays[] = [
                'date' => $date,
                'type' => trim($cells->item(2)->nodeValue),
                'title' => trim($cells->item(3)->nodeValue),
            ];
        }
    }
}
echo json_encode($holidays, JSON_PRETTY_PRINT);
exit; 
