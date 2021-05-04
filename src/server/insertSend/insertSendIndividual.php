<?php

require '../lib.php';
$api = new sgcbApi();

$data = $_POST;
$response = $api->insertSendParaleloIndividual($data);
// $mensaje = json_decode($data['mensajes']);
echo json_encode($response);
