<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if(isset($input["buscarCuentas"]))
{   
    $json = $api->getUsersNIU($input["buscarCuentas"]);
    
}

echo json_encode($json);
