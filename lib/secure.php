<?php
function CreateLock($dbname,$list){
    $path = $_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/list/'.$list.'.lock';
    touch($path);
}
function IsLock($dbname,$list){
    $path = $_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/list/'.$list.'.lock';
    if(file_exists($path)){
        return true;
    }
    else{
        return false;
    }
}
function DeleteLock($dbname,$list){
    $path = $_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/list/'.$list.'.lock';
    unlink($path);
}
function WebAPIAuth() {
    if (!isset($_REQUEST["dbname"])) {
        sendErrorResponse(400, "Missing dbname parameter");
    }
    $dbname = $_REQUEST["dbname"];
    if (empty($dbname) || !preg_match('/^[a-zA-Z0-9_-]+$/', $dbname)) {
        sendErrorResponse(400, "Invalid dbname parameter");
    }
    $db_directory = $_SERVER["DOCUMENT_ROOT"] . "/db/" . $dbname . "/";
    if (!is_dir($db_directory)) {
        sendErrorResponse(404, "Database not found");
    }
    if (isset($_REQUEST["ApiKey"])) {
        $json = json_decode(file_get_contents($db_directory . "config.json"), true);
        if ($json["WebAPI"] === false) {
            sendErrorResponse(403, "WebAPI is disabled");
        } elseif ($json["ApiKey"] !== $_REQUEST["ApiKey"]) {
            sendErrorResponse(401, "Invalid APIKey");
        }
    } else {
        sendErrorResponse(401, "Authentication failure");
    }
}

function sendErrorResponse($code, $message) {
    $json = [
        "status" => "error",
        "code" => $code,
        "message" => $message,
    ];
    http_response_code($code);
    echo json_encode($json);
    exit;
}
