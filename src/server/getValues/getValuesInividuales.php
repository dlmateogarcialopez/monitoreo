<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if(isset($input["camposBuscar"]))
{   
    $salida = $api->findCampos($input["camposBuscar"]);
    $json = [];
    foreach ($salida as $key => $value) {
        array_push($json, $value->_id);
    }
}

echo json_encode($json);
