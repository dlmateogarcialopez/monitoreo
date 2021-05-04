<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if(isset($input["camposBuscar"]))
{   
    $json = $api->totalCampos($input["camposBuscar"]);
    
}

echo json_encode($json);
