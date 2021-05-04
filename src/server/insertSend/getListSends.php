<?php
require "../lib.php";

$api = new sgcbApi();
// $input = $api->detectRequestBody();
$json = $api->getListSends();

echo json_encode($json);
