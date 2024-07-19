<?php
header('Content-Type: application/json; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'] . '/JsonDB.php');

WebAPIAuth();
$json = [
    "status" => "error",
    "code" => 400,
    "message" => "Invalid format. Supported formats are json, array, str, num",
];
http_response_code(400);
$list = isset($_REQUEST['list']) ? $_REQUEST['list'] : null;
$jsonDB = new jsonDB();
$dbname = isset($_REQUEST['dbname']) ? $_REQUEST['dbname'] : null;
$jsonDB->Connect($dbname);
$jsonDB->WebAPI();
$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : null;
if ($format !== 'json' && $format !== 'array' && $format !== 'str' && $format !== 'num') {
    sendErrorResponse(400, "Invalid format specified");
}
$key = isset($_REQUEST['key']) ? $_REQUEST['key'] : null;
if (!$key) {
    sendErrorResponse(400, "Invalid key");
}
$value = isset($_REQUEST['value']) ? $_REQUEST['value'] : null;
switch ($format) {
    case 'json':
        $data = json_decode($value, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            sendErrorResponse(400, "Invalid format. Please check your JSON format");
        }
        break;
    case 'array':
        $data = json_decode($value);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            sendErrorResponse(400, "Invalid format. Please check your Array format");
        }
        break;
    case 'num':
        if (!is_numeric($value)) {
            sendErrorResponse(400, "Invalid format. Please check your Number format");
        }
        $data = intval($value);
        break;
    default:
        $data = $value;
        break;
}
$jsonDB->edit($list, $key, $data);
$json = [
    "status" => "success",
    "code" => 200,
    "message" => null,
];
http_response_code(200);
echo json_encode($json);
exit;
?>
