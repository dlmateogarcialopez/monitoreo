<?php

require '../lib.php';

$api = new sgcbApi();
$input = $api->detectRequestBody();
$json = '';
date_default_timezone_set('America/Bogota');
$fechaHoraRegistro = (string) date('Y-m-d H:i:s');

if (isset($input['idUsuario'], $input['nombreUsuario'], $input['motivoEnvio'], $input['mensajes'])) {
    if (isset($input['mensajes']['metodoEnvio'], $input['mensajes']['tipoMensaje'], $input['mensajes']['rawMensaje'], $input['mensajes']['mensajes'])) {
        if ($input['mensajes']['tipoMensaje'] == 'individual' || $input['mensajes']['tipoMensaje'] == 'precargado') {
            $json = $api->insertSendMessageIndividual(
        $input['idUsuario'],
        $input['nombreUsuario'],
        $fechaHoraRegistro,
        $input['motivoEnvio'],
        $input['mensajes']['metodoEnvio'],
        $input['mensajes']['tipoMensaje'],
        $input['mensajes']['rawMensaje'],
        $input['mensajes']['mensajes']
      );
        } else {
            $json = $api->insertSendMessageGeneral(
        $input['idUsuario'],
        $input['nombreUsuario'],
        $fechaHoraRegistro,
        $input['motivoEnvio'],
        $input['mensajes']['metodoEnvio'],
        $input['mensajes']['tipoMensaje'],
        $input['mensajes']['rawMensaje'],
        $input['mensajes']['mensajes']
      );
        }
    } else {
        $json = 'Error, entrada de datos incorrecta';
    }
} else {
    $json = 'Error, entrada de datos incorrecta';
}

echo json_encode($json);

//     Petición:
// {
//   "idUsuario": "5db21e9144fac7421018de3d",
//   "nombreUsuario": "Lucy",
//   "fecha": "2020-05-31 16:45:43",
//   "motivoEnvio": "Interrupción",
//   "mensajes": {
//     "metodoEnvio": "Desde BD",
//     "tipoMensaje": "individual",
//     "rawMensaje": "Hola @nombre, tu cuenta @cuenta",
//     // Si se envía mensaje individual o precargado
//     "mensajes": [
//       {
//         "telefono": "3123465456",
//         "mensaje": "Hola Fabiola, tu cuenta 657445457",
//         "cantidadCaracteres": 168,
//         "cantidadMensajes": 2
//       }
//     ],
//     // Si se envía mensaje general
    // "mensajes": {
    //   "telefonos": [
    //     "3122545354",
    //     "32145475754"
    //   ],
    //   "mensaje": "Este es un mensaje general",
    //   "cantidadCaracteres": 168,
    //   "cantidadMensajes": 2
    // }
//   }
// }

// Retorna: "ok/error"
