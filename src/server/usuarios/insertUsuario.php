<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["nombres"], $input["apellidos"], $input["correo"], $input["permisos"], $input["isAddUser"], $input["cuotaMensajes"])) {
  $nombres = $api->sanitize_string($input["nombres"]);
  $apellidos = $api->sanitize_string($input["apellidos"]);
  $cargo = $api->sanitize_string($input["cargo"]);
  $correo = $api->sanitize_string($input["correo"]);
  $permisos = $input["permisos"];
  $cuotaMensajes = $input["cuotaMensajes"];
  $isAddUser = $api->sanitize_integer($input["isAddUser"]);

  foreach ($permisos["checkboxPermisos"] as $key => $value) {
    $permisos["checkboxPermisos"][$key] = (bool) $api->sanitize_integer($value);
  }

  /* Se combinan los permisos de checkbox con las cantidades de mensajes por usuario y cliente para que el array `$permisos` quede de un solo nivel y sea guardado en BD */
  $permisos = array_merge($permisos["checkboxPermisos"], $permisos);
  /* Se quita la clave `checkboxPermisos` de `$permisos` */
  unset($permisos["checkboxPermisos"]);

  $cuotaMensajes["selectBolsa"] = $api->sanitize_string($input["cuotaMensajes"]["selectBolsa"]);
  $cuotaMensajes["selectPeriodoMensajesUsuario"] = $api->sanitize_string($input["cuotaMensajes"]["selectPeriodoMensajesUsuario"]);
  $cuotaMensajes["cantidadMensajesUsuario"] = (int) $api->sanitize_integer($input["cuotaMensajes"]["cantidadMensajesUsuario"]);

  // var_dump($cuotaMensajes);

  if ($isAddUser) {
    $password = $input["password"];
    $json = $api->insertUsuario($nombres, $apellidos, $cargo, $correo, $password, $permisos, $cuotaMensajes);
  } else {
    $idUsuario = $api->sanitize_string($input["idUsuario"]);
    $json = $api->updateUsuario($idUsuario, $nombres, $apellidos, $cargo, $correo, $permisos, $cuotaMensajes);
  }
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
