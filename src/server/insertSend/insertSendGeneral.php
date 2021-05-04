<?php

require '../lib.php';
$api = new sgcbApi();

$data = $_POST;
$response = $api->insertSendParaleloGeneral($data);
// $mensaje = json_decode($data['mensajes']);
echo json_encode($response);
