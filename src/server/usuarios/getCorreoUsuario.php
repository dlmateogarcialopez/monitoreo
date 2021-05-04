<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["correo"])) {
  $correo = $api->sanitize_string($input["correo"]);
  $json = $api->getCorreoUsuario($correo);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);