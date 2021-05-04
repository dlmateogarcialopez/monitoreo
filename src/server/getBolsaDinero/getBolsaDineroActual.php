<?php
require "../lib.php";

$api = new sgcbApi();
// $input = $api->detectRequestBody();
$json = $api->getBolsaDinero();
echo json_encode($json);
