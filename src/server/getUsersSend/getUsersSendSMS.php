<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if(isset($input["camposBuscar"], $input["cantidad_usuarios"]))
{   
    $json = $api->getUsersSendSMS($input["camposBuscar"], $input["cantidad_usuarios"]);
    
}

echo json_encode($json);
