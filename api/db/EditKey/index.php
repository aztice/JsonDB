<?php
header('Content-Type: application/json; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'] . '/JsonDB.php');
WebAPIAuth();
$json = [
    "status" => "error",
    "code" => 400,
    "message" => "Invalid format,Supported format are json,array,str",
];
http_response_code(400);
$list = $_GET['list'];
$jsonDB = new jsonDB();
$jsonDB->Connect($_GET['dbname']);
$jsonDB->WebAPI();
if($_GET['format']!=='json' || $_GET['format']!=='array' || $_GET['format']!=='str' || $_GET['format']=='num'){
    if (!isset($_GET['key']) || $_GET['key'] == '') {
        $json = [
            "status" => "error",
            "code" => 400,
            "message" => "Invalid key",
        ];
        http_response_code(400);
        echo json_encode($json);
        exit;
    }
    if($_GET['format']=='json'){
        $data=json_decode($_GET['value'],true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $json = [
                "status" => "error",
                "code" => 400,
                "message" => "Invalid format,please check your Json format",
            ];
            http_response_code(400);
            echo json_encode($json);
            exit;
        }
        
    }
    else if($_GET['format']=='array'){
        $data=json_decode($_GET['value']);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            $json = [
                "status" => "error",
                "code" => 400,
                "message" => "Invalid format,please check your Array format",
            ];
            http_response_code(400);
            echo json_encode($json);
            exit;
        }
    }
    else if($_GET['format']=='num'){
        if(!is_numeric($_GET['value'])){
            $json = [
                "status" => "error",
                "code" => 400,
                "message" => "Invalid format,please check your Number format",
            ];
            http_response_code(400);
            echo json_encode($json);
            exit;
        }
        $data = intval($_GET['value']);
    }
    else{
        $data = $_GET['value'];
    }
    $jsonDB->EditKey($_GET['list'],$_GET['key'],$data);
    $json = [
        "status" => "success",
        "code" => 200,
        "message" => null,
    ];
    http_response_code(200);
    echo json_encode($json);
    exit;
}
echo json_encode($json);
