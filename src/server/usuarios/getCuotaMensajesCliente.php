<?php
require "../lib.php";

$api = new sgcbApi();
$json = $api->getCuotaMensajesCliente();

echo json_encode($json);
