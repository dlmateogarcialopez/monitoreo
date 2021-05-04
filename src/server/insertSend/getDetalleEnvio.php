<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if(isset($input["idDetalleEnvio"]))
{   
    $json = $api->getDetalleEnvio($input["idDetalleEnvio"]);
}
echo json_encode($json);
