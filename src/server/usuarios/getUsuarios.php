<?php
require "../lib.php";

$api = new sgcbApi();
$json = $api->getUsuarios();

echo json_encode($json);
