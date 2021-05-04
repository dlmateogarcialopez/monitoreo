<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["nombreBolsa"])) {
  $nombreBolsa = $api->sanitize_string($input["nombreBolsa"]);
  $json = $api->getBolsaMensajesUsuario($nombreBolsa);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);

