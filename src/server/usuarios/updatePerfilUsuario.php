<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["idUsuario"], $input["nombres"], $input["apellidos"], $input["correo"])) {
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $nombres = $api->sanitize_string($input["nombres"]);
  $apellidos = $api->sanitize_string($input["apellidos"]);
  $cargo = $api->sanitize_string($input["cargo"]);
  $correo = $api->sanitize_string($input["correo"]);

  $datosPerfilUsuario = array(
    "idUsuario" => $idUsuario,
    "nombres" => $nombres,
    "apellidos" => $apellidos,
    "cargo" => $cargo,
    "correo" => $correo,
  );

  $json = $api->updatePerfilUsuario($datosPerfilUsuario);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
