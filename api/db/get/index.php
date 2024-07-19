<?php
header('Content-Type: application/json; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'] . '/JsonDB.php');
WebAPIAuth();
$json = [
    "status" => "success",
    "code" => 200,
    "message" => null,
];
http_response_code(200);
$list = $_REQUEST['list'] ?? null;
$jsonDB = new jsonDB();
$jsonDB->Connect($_REQUEST['dbname']);
$jsonDB->WebAPI();
$value = $jsonDB->get($list, $_REQUEST['key']);
if (isset($value)) {
    $json = [
        "status" => "success",
        "code" => 200,
        "message" => $value
    ];
}
echo json_encode($json);
?>
