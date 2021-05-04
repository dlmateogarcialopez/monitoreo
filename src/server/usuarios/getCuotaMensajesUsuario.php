<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["idUsuario"])) {
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $json = $api->getCuotaMensajesUsuario($idUsuario);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);