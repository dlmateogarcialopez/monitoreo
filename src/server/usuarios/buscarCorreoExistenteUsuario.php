<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["idUsuario"], $input["correo"])) {
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $correo = $api->sanitize_string($input["correo"]);
  $json = $api->buscarCorreoExistenteUsuario($idUsuario, $correo);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);