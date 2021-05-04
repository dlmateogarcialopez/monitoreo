<?php
require "../lib.php";

$api = new sgcbApi();
$json = $api->getDatosBolsas();
echo json_encode($json);
