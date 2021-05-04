<?php

// ini_set('display_errors', '1');
date_default_timezone_set('America/Bogota');
define('DB_INTRA_CHEC', 'SGCB');
// define("DB_DIFUSION_CHEC", "heroku_qqkvqh3x");
define('DB_DIFUSION_CHEC', 'CHEC');
define('DB_HEROKU_CHEC', 'heroku_qqkvqh3x');
define('DB_HEROKU_CHEC_SGCB', 'SGCB');
define('PASSWORD_SALT', 'DF5g%&G!#ff9%&ev');

function verificarHashPasswordQuery($con, $idUsuario, $password)
{
  $filter = [
    '_id' => new MongoDB\BSON\ObjectId($idUsuario),
    'password' => $password,
  ];
  $options = [
    'projection' => [
      'correo' => 1,
    ],
  ];
  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);

  return $result->toArray();
}

function saveRefreshToken($con, $idUsuario, $refreshToken)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    'idUsuario' => $idUsuario,
    'refreshToken' => $refreshToken,
  ]);

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.refreshTokens', $bulk);
}

function getRefreshToken($con, $refreshToken, $idUsuarioActual)
{
  $filter = [
    'idUsuario' => $idUsuarioActual,
    'refreshToken' => $refreshToken,
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.refreshTokens', $query);

  return $result->toArray();
}

function getUsuariosQuery($con)
{
  $command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios_plataforma',
    'pipeline' => [
      [
        '$match' => ["estado" => true]
      ],
      [
        '$project' => [
          "nombres" => ['$concat' => ['$nombres', " ", '$apellidos']],
          "cargo" => 1,
          "correo" => 1,
          // "permisos" => 1,
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);

  $result = $con->executeCommand(DB_INTRA_CHEC, $command);
  return $result->toArray();
}

function getLoginQuery($con, $correo, $password)
{
  $filter = [
    'correo' => new MongoDB\BSON\Regex("^$correo$", "i"),
    'estado' => true,
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);
  $datos = '';

  foreach ($result as $datos) {
    $datos = json_decode(json_encode($datos));
  }

  if ($datos && password_verify($password . PASSWORD_SALT, $datos->password)) {
    /* Elimina claves password y estado del objeto datos */
    unset($datos->password, $datos->estado);

    return $datos;
  }

  return false;
}

function getUsuarioIndividualQuery($con, $idUsuario)
{
  $filter = [
    '_id' => new MongoDB\BSON\ObjectId($idUsuario),
    'estado' => true,
  ];

  $options = [
    'projection' => ['password' => 0, 'estado' => 0]
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);
  return $result->toArray()[0];

  // foreach ($result as $datos) {
  //   $datosUsuario = $datos;
  // }
  // return $datosUsuario;
}

function getCuotaMensajesUsuarioQuery($con, $idUsuario)
{
  $filter = ["idUsuario" => $idUsuario];
  $options = [
    'sort' => ['_id' => -1],
    'limit' => 1,
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".cuotaMensajesUsuario", $query);
  return $result->toArray()[0];

  // foreach ($result as $datos) {
  //   $datosCuotaUsuario = $datos;
  // }
  // return $datosCuotaUsuario;
}

/** Actualiza la cuota de mensajes de usuario cuando este realiza un envío de SMS */
function updateCuotaMensajesUsuarioEnvioSmsQuery($con, $idCuota, $cantidadMensajesEnviados)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    ["_id" => new MongoDB\BSON\ObjectId($idCuota)],
    ['$inc' => ["cantidadMensajesUsuario" => -$cantidadMensajesEnviados]]
  );

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.cuotaMensajesUsuario', $bulk);
}

function getDatosUsuarioQuery($con, $idUsuario)
{
  $filter = [
    '_id' => new MongoDB\BSON\ObjectId($idUsuario),
    'estado' => true,
  ];
  $options = [
    'projection' => ['permisos' => 1],
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);

  foreach ($result as $datos) {
    $datosUsuario = $datos;
  }
  return $datosUsuario;
}

function logoutQuery($con, $idUsuario, $refreshToken)
{
  // echo "refre ". ($refreshToken);
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->delete([
    'idUsuario' => $idUsuario,
    'refreshToken' => $refreshToken,
  ]);

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.refreshTokens', $bulk);
}

function getCorreoUsuarioQuery($con, $correo)
{
  $filter = [
    'correo' => new MongoDB\BSON\Regex("^$correo$", "i"),
    'estado' => true,
  ];
  $options = [
    'projection' => [
      'correo' => 1,
      'password' => 1,
    ],
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);

  return $result->toArray();
}

function buscarCorreoExistenteUsuarioQuery($con, $idUsuario, $correo)
{
  /* Si se provee el `$idUsuario` (Editar usuario, Actualizar perfil), verificar si el `$correo` está registrado en un usuario diferente al usuario editado */
  if ($idUsuario) {
    $filter = [
      "_id" => ['$ne' => new MongoDB\BSON\ObjectId($idUsuario)],
      "correo" => new MongoDB\BSON\Regex("^$correo$", "i"),
      "estado" => true,
    ];
  } else {
    /* Si el `$idUsuario` es vacío (Agregar usuario), verificar si el correo ya se encuentra registrado */
    $filter = [
      "correo" => new MongoDB\BSON\Regex("^$correo$", "i"),
      "estado" => true,
    ];
  }
  $options = [
    'projection' => [
      'correo' => 1,
    ],
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);

  return $result->toArray();
}

function getPasswordActualQuery($con, $id, $passwordActual, $passwordNuevo)
{
  $filter = [
    '_id' => new MongoDB\BSON\ObjectId($id),
    'estado' => true,
  ];
  $options = [
    'projection' => ['password' => 1],
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.usuarios_plataforma', $query);
  $datos = '';

  foreach ($result as $datos) {
    $datos = json_decode(json_encode($datos));
  }

  if ($datos && password_verify($passwordActual . PASSWORD_SALT, $datos->password)) {
    return resetPasswordUsuarioQuery($con, $id, $passwordNuevo);
  }

  return false;
}

function resetPasswordUsuarioQuery($con, $id, $password)
{
  // var_dump($password);
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    ['_id' => new MongoDB\BSON\ObjectId($id)],
    ['$set' => ['password' => password_hash($password . PASSWORD_SALT, PASSWORD_DEFAULT)]]
  );

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.usuarios_plataforma', $bulk);
}

function insertUsuarioQuery($con, $nombres, $apellidos, $cargo, $correo, $password, $permisos, $cuotaMensajes)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $insertedUsuarioId = $bulk->insert(
    [
      'nombres' => $nombres,
      'apellidos' => $apellidos,
      'cargo' => $cargo,
      'correo' => $correo,
      'password' => password_hash($password . PASSWORD_SALT, PASSWORD_DEFAULT),
      'permisos' => $permisos,
      'estado' => true, /* Indica que el usuario está activo en la plataforma */
    ]
  );
  // var_dump($cuotaMensajes);
  $insertedUsuarioId = (string) $insertedUsuarioId;
  $nombreUsuario = $nombres . " " . $apellidos;
  insertCuotaMensajesUsuarioQuery($con, $insertedUsuarioId, $nombreUsuario, $cuotaMensajes);

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.usuarios_plataforma', $bulk);
}

function updateUsuarioQuery($con, $idUsuario, $nombres, $apellidos, $cargo, $correo, $permisos, $cuotaMensajes)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $nombreUsuario = $nombres . " " . $apellidos;
  updateCuotaMensajesUsuarioQuery($con, $idUsuario, $nombreUsuario, $cuotaMensajes);

  /* Usuario administrador actualiza un usuario del sistema */
  $bulk->update(
    ['_id' => new MongoDB\BSON\ObjectId($idUsuario)],
    [
      '$set' => [
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'cargo' => $cargo,
        'correo' => $correo,
        'permisos' => $permisos,
      ],
    ]
  );
  return $con->executeBulkWrite(DB_INTRA_CHEC . '.usuarios_plataforma', $bulk);
}

function updatePerfilUsuarioQuery($con, $datosPerfilUsuario)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    ["_id" => new MongoDB\BSON\ObjectId($datosPerfilUsuario["idUsuario"])],
    [
      '$set' => [
        "nombres" => $datosPerfilUsuario["nombres"],
        "apellidos" => $datosPerfilUsuario["apellidos"],
        "cargo" => $datosPerfilUsuario["cargo"],
        "correo" => $datosPerfilUsuario["correo"],
      ],
    ]
  );
  return $con->executeBulkWrite(DB_INTRA_CHEC . ".usuarios_plataforma", $bulk);
}

function insertCuotaMensajesUsuarioQuery($con, $idUsuario, $nombreUsuario, $cuotaMensajes)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert(
    [
      "idUsuario" => $idUsuario,
      "nombreUsuario" => $nombreUsuario,
      "selectBolsa" => $cuotaMensajes["selectBolsa"],
      "selectPeriodoMensajesUsuario" => $cuotaMensajes["selectPeriodoMensajesUsuario"],
      "cantidadMensajesUsuario" => $cuotaMensajes["cantidadMensajesUsuario"],
      "fechaInicioMensajesUsuario" => $cuotaMensajes["fechaInicioMensajesUsuario"],
      "fechaFinMensajesUsuario" => $cuotaMensajes["fechaFinMensajesUsuario"],
      /* Guarda la cantidad de mensajes con la que el usuario inicia la cuota */
      "totalInicialMensajes" => $cuotaMensajes["cantidadMensajesUsuario"],
    ]
  );
  return $con->executeBulkWrite(DB_INTRA_CHEC . '.cuotaMensajesUsuario', $bulk);
}

/** Actualiza los datos de la cuota de mensajes de usuario cuando un administrador edita los datos de dicho usuario */
function updateCuotaMensajesUsuarioQuery($con, $idUsuario, $nombreUsuario, $nuevaCuotaMensajes)
{
  /** @var ultimaCuotaUsuario Retorna los datos de la última cuota registrada para `$idUsuario` */
  $ultimaCuotaUsuario = getCuotaMensajesUsuarioQuery($con, $idUsuario);

  if (strtotime($nuevaCuotaMensajes["fechaInicioMensajesUsuario"]) <= strtotime($ultimaCuotaUsuario->fechaFinMensajesUsuario)) {
    /** @var cantidadTotal Toma la diferencia entre el valor nuevo que el usuario acaba de definir y el valor anterior de la cuota */
    $cantidadTotal = $nuevaCuotaMensajes["cantidadMensajesUsuario"] - $ultimaCuotaUsuario->totalInicialMensajes;

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
      ["_id" => new MongoDB\BSON\ObjectId($ultimaCuotaUsuario->_id)],
      [
        '$set' => [
          "idUsuario" => $idUsuario,
          "nombreUsuario" => $nombreUsuario,
          "selectBolsa" => $nuevaCuotaMensajes["selectBolsa"],
          "selectPeriodoMensajesUsuario" => $nuevaCuotaMensajes["selectPeriodoMensajesUsuario"],
          "fechaInicioMensajesUsuario" => $nuevaCuotaMensajes["fechaInicioMensajesUsuario"],
          "fechaFinMensajesUsuario" => $nuevaCuotaMensajes["fechaFinMensajesUsuario"],
          "totalInicialMensajes" => $nuevaCuotaMensajes["cantidadMensajesUsuario"],
        ],
        /* Si `$cantidadTotal` es positivo, se suma `$cantidadTotal` al valor del campo `cantidadMensajesUsuario`; si `$cantidadTotal` es negativo, se resta. */
        '$inc' => ["cantidadMensajesUsuario" => $cantidadTotal]
      ]
    );
    return $con->executeBulkWrite(DB_INTRA_CHEC . '.cuotaMensajesUsuario', $bulk);
  } else {
    insertCuotaMensajesUsuarioQuery($con, $idUsuario, $nombreUsuario, $nuevaCuotaMensajes);
  }
}

function getCuotaMensajesClienteQuery($con)
{
  $filter = [];
  $options = [
    'sort' => ['_id' => -1],
    'limit' => 1,
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".cuotaMensajesCliente", $query);

  foreach ($result as $datos) {
    $datosCuotaClientes = $datos;
  }
  return $datosCuotaClientes;
}

function insertCuotaMensajesClienteQuery($con, $idUsuario, $nombreUsuario, $cuotaMensajes, $fechaRegistro)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert(
    [
      "idUsuario" => $idUsuario,
      "nombreUsuario" => $nombreUsuario,
      "fechaRegistro" => $fechaRegistro,
      "periodoMensajesCliente" => $cuotaMensajes["periodoMensajesCliente"],
      "cantidadMensajesCliente" => $cuotaMensajes["cantidadMensajesCliente"],
      "fechaInicioMensajesCliente" => $cuotaMensajes["fechaInicioMensajesCliente"],
      "fechaFinMensajesCliente" => $cuotaMensajes["fechaFinMensajesCliente"],
    ]
  );
  return $con->executeBulkWrite(DB_INTRA_CHEC . '.cuotaMensajesCliente', $bulk);
}

function updateCuotaMensajesClienteDodQuery($con, $fechaInicioMensajesCliente, $fechaFinMensajesCliente, $cantidadMensajesClienteNuevo, $cantidadMensajesClienteAnterior)
{
  /** @var cantidadTotal Toma la diferencia entre el valor nuevo que el usuario acaba de definir y el valor anterior de la cuota */
  $cantidadTotal = $cantidadMensajesClienteNuevo - $cantidadMensajesClienteAnterior;
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    [
      '$and' => [
        ["fechaEnvio" => ['$gte' => $fechaInicioMensajesCliente]],
        ["fechaEnvio" => ['$lte' => $fechaFinMensajesCliente]]
      ]
    ],
    /* Si `$cantidadTotal` es positivo, se suma `$cantidadTotal` al valor del campo `cantidadCuotaMensajesCliente`; si `$cantidadTotal` es negativo, se resta. */
    ['$inc' => ["cantidadCuotaMensajesCliente" => $cantidadTotal]],
    ['multi' => true]
  );
  return $con->executeBulkWrite(DB_INTRA_CHEC . '.log_difusion_dod', $bulk);
}

function inactivarUsuarioQuery($con, $idUsuario)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    ['_id' => new MongoDB\BSON\ObjectId($idUsuario)],
    ['$set' => ['estado' => false]]
  );

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.usuarios_plataforma', $bulk);
}

//CONSULTAS PPROCESOS DE BASE DE DATOS
function findCampos($con, $campo)
{
  $campoUpper = strtoupper($campo);
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios',
    'pipeline' => [
      [
        '$group' => [
          '_id' => "$$campoUpper",
        ],
      ],
      [
        '$match' => [
          '$and' => [
            ['_id' => ['$ne' => null]],
            ['_id' => ['$ne' => '']],
            ['_id' => ['$ne' => 'NULL']],
          ],
        ],
      ],
      [
        '$sort' => [
          '_id' => 1,
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();

  return $respuesta;
}

function totalUsuariosCelularValido($con, $campos)
{
  $campos['$and'] = [
    // ["CELULAR" => ['$ne' => null]],
    // ["CELULAR" => ['$ne' => '']],
    // ["CELULAR" => ['$ne' => '-']],
    ["CELULAR" => new MongoDB\BSON\Regex("^3")],
    ["CELULAR" => new MongoDB\BSON\Regex("^[\\s\S]{10}$")],
  ];
  // var_dump($campos);
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios',
    'pipeline' => [
      [
        '$match' => $campos,
      ],
      [
        '$group' => [
          '_id' => '$CELULAR',
        ],
      ],
      [
        '$count' => 'total_usuarios_cel_validos'
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $result = $result->toArray();

  if (count($result) > 0) {
    return $result[0];
  }
  $result = (object) [
    "total_usuarios_cel_validos" => 0
  ];
  return $result;
}

function totalUsuarios($con, $campos)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios',
    'pipeline' => [
      [
        '$match' => $campos,
      ],
      [
        '$count' => 'total',
      ],
    ],
    'cursor' => new stdClass(),
  ]);

  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $result1 = $result->toArray();
  if (count($result1) > 0) {
    return $result1[0]->total;
  } else {
    return 0;
  }
}

function getUsersSendSMSQuery($con, $campos, $total_usuarios)
{
  $campos['$and'] = [
    ["CELULAR" => new MongoDB\BSON\Regex("^3")],
    ["CELULAR" => new MongoDB\BSON\Regex("^[\\s\S]{10}$")],
  ];

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios',
    'pipeline' => [
      [
        '$match' => $campos,
      ],
      [
        '$group' => [
          '_id' => '$CELULAR',
          'doc' => [
            '$first' => '$$ROOT'
          ]
        ],
      ],
      [
        '$sample' => [
          'size' => $total_usuarios,
        ],
      ],
      [
        '$replaceRoot' => [
          'newRoot' => '$doc',
        ]
      ],
      [
        '$project' => [
          '_id' => 0,
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();

  return $respuesta;
}

function insertSendMessageUnitario($con, $datosLogDod)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    'idConsolidado' => $datosLogDod["idConsolidado"],
    'idUsuario' => $datosLogDod["idUsuario"],
    'cuenta' => $datosLogDod["cuenta"],
    'nombre' => $datosLogDod["nombre"],
    'celular' => $datosLogDod["celular"],
    'mensaje' => $datosLogDod["mensaje"],
    'cantidadCaracteres' => $datosLogDod["cantidadCaracteres"],
    'cantidadMensajes' => $datosLogDod["cantidadMensajes"],
    'tipoMensaje' => $datosLogDod["tipoMensaje"],
    'fechaEnvio' => date('Y-m-d H:i:s'),
    'valorMensajeIndividual' => (float) $datosLogDod["valorMensajeIndividual"],
    'cantidadCuotaMensajesCliente' => $datosLogDod["cantidadMensajesClienteRestantes"]
  ]);

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.log_difusion_dod', $bulk);
}

/* function insertAcuseProvisional($con, $idUsuario, $cuenta, $nombre, $celular, $mensaje, $estado, $fecha, $tipoMensaje, $_idConsolidado, $cantidadCaracteres, $cantidadMensajes)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    'idConsolidado' => $_idConsolidado,
    'idUsuario' => $idUsuario,
    'cuenta' => $cuenta,
    'nombre' => $nombre,
    'celular' => $celular,
    'mensaje' => $mensaje,
    'estado' => $estado,
    'fecha' => $fecha,
    'tipoMensaje' => $tipoMensaje,
    'cantidadCaracteres' => $cantidadCaracteres,
    'cantidadMensajes' => $cantidadMensajes,
  ]);

  return $con->executeBulkWrite(DB_DIFUSION_CHEC . '.log_acuse_recibo_dod', $bulk);
} */
function insertDodAcuse($con, $datosDodAcuse)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    'idConsolidado' => $datosDodAcuse["idConsolidado"],
    'idUsuario' => $datosDodAcuse["idUsuario"],
    'cuenta' => $datosDodAcuse["cuenta"],
    'nombre' => $datosDodAcuse["nombre"],
    'celular' => $datosDodAcuse["celular"],
    'celularSMS' => '57' . $datosDodAcuse["celular"],
    'mensaje' => $datosDodAcuse["mensaje"],
    'tipoMensaje' => $datosDodAcuse["tipoMensaje"],
    'cantidadCaracteres' => $datosDodAcuse["cantidadCaracteres"],
    'cantidadMensajes' => $datosDodAcuse["cantidadMensajes"],
    'estado' => $datosDodAcuse["estado"],
    "fecha" => date("Y-m-d H:i:s"),
    "nombreBolsa" => $datosDodAcuse['nombreBolsa'],
  ]);

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.log_acuse_recibo_dod', $bulk);
}
function insertAcuseProvisional2($con, $post)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    'post' => $post,
  ]);

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.log_acuse_recibo_dod', $bulk);
}

function insertSendMessageConsolidado($con, $idUsuario, $nombreUsuario, $fechaEnvio, $motivoEnvio, $metodoEnvio, $tipoMensaje, $rawMensaje, $countCelulares, $estado, $cantidadReal, $valorMensajeIndividual)
{
  $bulk = new MongoDB\Driver\BulkWrite();
  $_id = $bulk->insert([
    'idUsuario' => $idUsuario,
    'nombreUsuario' => $nombreUsuario,
    'fechaEnvio' => $fechaEnvio,
    'motivoEnvio' => $motivoEnvio,
    'metodoEnvio' => $metodoEnvio,
    'tipoMensaje' => $tipoMensaje,
    'rawMensaje' => $rawMensaje,
    'cantidadEnviados' => $countCelulares,
    'estado' => $estado,
    'cantidadMensajes' => $cantidadReal,
    'valor' => 0,
    'valorMensajeIndividual' => $valorMensajeIndividual,
  ]);
  $result = $con->executeBulkWrite(DB_INTRA_CHEC . '.log_difusion_consolidado_dod', $bulk);

  if ($result->getInsertedCount()) {
    return (string) $_id;
  } else {
    return 'Error';
  }
}

/** Actualiza el saldo de la bolsa de mensajes después de realizar un envío */
function updateSaldoBolsa($conDifusion, $conIntraChec, $idConsolidado, $idUsuario, $nombreBolsa)
{
  $queryBolsaMensajes = new MongoDB\Driver\Command([
    'aggregate' => 'bolsa_mensajes_difusion',
    'pipeline' => [
      [
        '$sort' => [
          'fecha_modificacion' => -1,
        ],
      ],
      [
        '$match' => [
          "nombre" => $nombreBolsa
        ]
      ],
      [
        '$limit' => 1,
      ],
      [
        '$project' => [
          '_id' => 0,
          'nombre' => '$nombre',
          'valor_actual' => '$valor_actual',
          'valor_mensaje_unidireccional' => '$valor_mensaje_unidireccional',
          'valor_mensaje_bidireccional' => '$valor_mensaje_bidireccional',
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $resultQueryBolsaMensajes = $conDifusion->executeCommand(DB_DIFUSION_CHEC, $queryBolsaMensajes);
  $respuestaBolsa = $resultQueryBolsaMensajes->toArray()[0];

  $queryLogConsolidadoDod = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_consolidado_dod',
    'pipeline' => [
      [
        '$match' => [
          '_id' => new \MongoDB\BSON\ObjectId($idConsolidado),
        ],
      ],
      [
        '$project' => [
          '_id' => 0,
          'valor' => '$valor',
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $resultQueryLogConsolidadoDod = $conIntraChec->executeCommand(DB_INTRA_CHEC, $queryLogConsolidadoDod);
  $respuestaMensajes = $resultQueryLogConsolidadoDod->toArray()[0];

  /* Si la bolsa por la que se enviaron los SMS es `UM`, se realiza la RESTA entre valor actual de la bolsa y el valor de los mensajes enviados. */
  if ($respuestaBolsa->nombre === "UM") {
    $valorActualizadoBolsa = floatval($respuestaBolsa->valor_actual) - floatval($respuestaMensajes->valor);
  } else if ($respuestaBolsa->nombre === "CHEC") {
    /* En caso de que la bolsa sea `CHEC`, en lugar de restar el valor de los SMS, este se SUMARÁ al valor actual de la bolsa CHEC, ya que esta bolsa no cuenta con un saldo del cual se pueda descontar el precio de los envíos. Se hace, además, con el fin de tener un registro del precio total de los mensajes enviados a través de esta bolsa */
    $valorActualizadoBolsa = floatval($respuestaBolsa->valor_actual) + floatval($respuestaMensajes->valor);
  }

  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    "nombre" => $respuestaBolsa->nombre,
    'valor_actual' => $valorActualizadoBolsa,
    'valor_anterior' => floatval($respuestaBolsa->valor_actual),
    'valor_mensaje_unidireccional' => floatval($respuestaBolsa->valor_mensaje_unidireccional),
    'valor_mensaje_bidireccional' => floatval($respuestaBolsa->valor_mensaje_bidireccional),
    'usuario_modifica' => $idUsuario,
    'fecha_modificacion' => date('Y-m-d H:i:s'),
  ]);
  $result = $conDifusion->executeBulkWrite(DB_DIFUSION_CHEC . '.bolsa_mensajes_difusion', $bulk);

  return $result;
}

function updateEstadoConsolidadoIndividual2($con, $_id, $estado, $totalinsert)
{
  $bulk2 = new MongoDB\Driver\BulkWrite();
  $bulk2->update(
    ['_id' => new \MongoDB\BSON\ObjectId($_id)],
    ['$set' => ['estado' => $estado, 'cantidadEnviados' => $totalinsert]],
    ['multi' => true, 'upsert' => true]
  );
  $result = $con->executeBulkWrite(DB_INTRA_CHEC . '.log_difusion_consolidado_dod', $bulk2);

  return $result;
}

function ejemploConsol($con, $_id)
{
  $bulk2 = new MongoDB\Driver\BulkWrite();
  $a = $bulk2->update(
    ['_id' => new \MongoDB\BSON\ObjectId($_id)],
    ['$set' => ['estado' => 'Enviado', 'cantidadEnviados' => 4]],
    ['multi' => true, 'upsert' => false]
  );
  $result = $con->executeBulkWrite(DB_DIFUSION_CHEC . '.log_difusion_consolidado_dod', $bulk2);

  return $result;
}

function getListSends($con)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_consolidado_dod',
    'pipeline' => [
      [
        '$project' => [
          '_id' => 0,
          'idDetalleEnvio' => [
            '$toString' => '$_id',
          ],
          'idUsuario' => '$idUsuario',
          'nombreUsuario' => '$nombreUsuario',
          'fecha' => '$fechaEnvio',
          'motivoEnvio' => '$motivoEnvio',
          'cantidadEnviados' => '$cantidadEnviados',
          'estado' => '$estado',
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  return $result->toArray();
}

function getConsolidadoDetalleEnvio($con, $idDetalleEnvio)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_consolidado_dod',
    'pipeline' => [
      [
        '$match' => [
          '_id' => new \MongoDB\BSON\ObjectId($idDetalleEnvio),
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  return $result->toArray();
}
function getIndividualDetalleEnvio($con, $idDetalleEnvio)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_dod',
    'pipeline' => [
      [
        '$match' => [
          'idConsolidado' => $idDetalleEnvio,
        ],
      ],
      [
        '$group' => [
          '_id' => '$idConsolidado',
          'total' => [
            '$sum' => '$cantidadMensajes',
          ],
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  return $result->toArray();
}
function getAcuseDetalleEnvio($con, $idDetalleEnvio)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dod',
    'pipeline' => [
      [
        '$match' => [
          'idConsolidado' => $idDetalleEnvio,
          'estado' => [
            '$exists' => true,
          ],
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);

  $respuesta = $result->toArray();

  return $respuesta;
}
function getBolsaDinero($con)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'bolsa_mensajes_difusion',
    'pipeline' => [
      [
        '$sort' => [
          'fecha_modificacion' => -1,
        ],
      ],
      [
        '$limit' => 1,
      ],
      [
        '$project' => [
          // '_id' => 0,
          'nombre' => '$nombre',
          'valor_actual' => '$valor_actual',
          'valor_anterior' => '$valor_anterior',
          'valor_mensaje_unidireccional' => '$valor_mensaje_unidireccional',
          'valor_mensaje_bidireccional' => '$valor_mensaje_bidireccional',
          'usuario_modifica' => '$usuario_modifica',
          'fecha_modificacion' => '$fecha_modificacion',
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);

  $respuesta = $result->toArray()[0];

  return $respuesta;
}

function getDatosBolsasQuery($con)
{
  $command = new MongoDB\Driver\Command([
    'aggregate' => 'bolsa_mensajes_difusion',
    'pipeline' => [
      [
        '$sort' => [
          "_id" => -1,
        ],
      ],
      [
        '$match' => [
          "nombre" => ['$exists' => true],
        ],
      ],
      [
        '$group' => [
          '_id' => '$nombre',
          'doc' => [
            '$first' => '$$ROOT'
          ]
        ]
      ],
      [
        '$replaceRoot' => [
          'newRoot' => '$doc',
        ]
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $command);

  return $result->toArray();
}

/** Obtiene los datos de la bolsa de mensajes asignada al usuario */
function getBolsaMensajesUsuarioQuery($con, $nombreBolsa)
{
  $command = new MongoDB\Driver\Command([
    'aggregate' => 'bolsa_mensajes_difusion',
    'pipeline' => [
      [
        '$match' => [
          "nombre" => $nombreBolsa
        ]
      ],
      [
        '$sort' => [
          "_id" => -1,
        ],
      ],
      [
        '$limit' => 1,
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $command);

  return $result->toArray()[0];
}

/** Actualiza el valor unitario de los mensajes uni y bidireccionales en la bolsa seleccionada por el usuario */
function updateValorMensajeUnitarioBolsaQuery($con, $datosValorMensajeUnitario)
{
  $datosBolsa = getBolsaMensajesUsuarioQuery($con, $datosValorMensajeUnitario["nombreBolsa"]);

  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    ["_id" => new MongoDB\BSON\ObjectId($datosBolsa->_id)],
    ['$set' => [
      "valor_mensaje_unidireccional" => $datosValorMensajeUnitario["valorMensajeUnidireccional"],
      "valor_mensaje_bidireccional" => $datosValorMensajeUnitario["valorMensajeBidireccional"],
      "usuario_modifica" => $datosValorMensajeUnitario["idUsuario"],
      "fecha_modificacion" => date('Y-m-d H:i:s')
    ]]
  );
  return $con->executeBulkWrite(DB_DIFUSION_CHEC . '.bolsa_mensajes_difusion', $bulk);
}

function adicionarSaldoBolsaQuery($con, $datosValorSaldoAdicionar)
{
  $datosBolsaAnterior = getBolsaMensajesUsuarioQuery($con, $datosValorSaldoAdicionar["nombreBolsa"]);

  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->insert([
    "nombre" => $datosBolsaAnterior->nombre,
    'valor_actual' => floatval($datosBolsaAnterior->valor_actual + $datosValorSaldoAdicionar["valorSaldoAdicionar"]),
    'valor_anterior' => floatval($datosBolsaAnterior->valor_actual),
    'valor_mensaje_unidireccional' => floatval($datosBolsaAnterior->valor_mensaje_unidireccional),
    'valor_mensaje_bidireccional' => floatval($datosBolsaAnterior->valor_mensaje_bidireccional),
    'usuario_modifica' => $datosValorSaldoAdicionar["idUsuario"],
    'fecha_modificacion' => date('Y-m-d H:i:s'),
  ]);
  return $con->executeBulkWrite(DB_DIFUSION_CHEC . '.bolsa_mensajes_difusion', $bulk);
}

function getUsersNIU($con, $NIUS)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios',
    'pipeline' => [
      [
        '$match' => [
          '$and' => [
            ['NIU' => ['$in' => $NIUS]],
            ["CELULAR" => new MongoDB\BSON\Regex("^3")],
            ["CELULAR" => new MongoDB\BSON\Regex("^[\\s\S]{10}$")],
          ]
        ],
      ],
      [
        '$project' => [
          '_id' => 0,
          'NIU' => '$NIU',
          'TIPO_DOC' => '$TIPO_DOC',
          'DOCUMENTO' => '$DOCUMENTO',
          'NOMBRE' => '$NOMBRE',
          'DIRECCION' => '$DIRECCION',
          'ESTRATO' => '$ESTRATO',
          'MUNICIPIO' => '$MUNICIPIO',
          'TELEFONO' => '$TELEFONO',
          'CELULAR' => '$CELULAR',
          'EMAIL' => '$EMAIL',
          'CIRCUITO' => '$CIRCUITO',
          'NODO' => '$NODO',
          'UBICACION' => '$UBICACION',
          'SEGMENTO' => '$SEGMENTO',
          'SUBSEGMENTO' => '$SUBSEGMENTO',
          'CLASE_SERVICIO' => '$CLASE_SERVICIO',
        ],
      ],
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  return $result->toArray();
}

/* function insertPrueba($con, $_idConsolidado, $cantidadMensajes)
{
  $dbname = 'CHEC';

  $Command = new MongoDB\Driver\Command(
    [
      'aggregate' => 'log_difusion_consolidado_dod',
      'pipeline' => [
        [
          '$match' => [
            '_id' => new \MongoDB\BSON\ObjectId($_idConsolidado),
          ],
        ],
        [
          '$project' => [
            'valor' => '$valor',
            'valorMensajeIndividual' => 1,
          ],
        ],
      ],
      'cursor' => new stdClass(),
    ]
  );
  $result = $con->executeCommand($dbname, $Command);
  $response = $result->toArray();

  $valor = floatval($response[0]->valor) + (floatval($cantidadMensajes) * $response[0]->valorMensajeIndividual);

  $bulk = new MongoDB\Driver\BulkWrite();
  $a = $bulk->update(
    ['_id' => new \MongoDB\BSON\ObjectId($_idConsolidado)],
    ['$set' => ['valor' => $valor]],
    ['multi' => true, 'upsert' => false]
  );
  $result = $con->executeBulkWrite($dbname . '.log_difusion_consolidado_dod', $bulk);
} */

function updateValorConsolidadoDod($con, $datosAcuseRecibo)
{
  // $dbname = "heroku_qqkvqh3x";
  /* $dbname = 'CHEC';
  $Command = new MongoDB\Driver\Command(
    [
      'aggregate' => 'log_difusion_consolidado_dod',
      'pipeline' => [
        [
          '$match' => [
            '_id' => new \MongoDB\BSON\ObjectId($datosAcuseRecibo["idConsolidado"]),
          ],
        ],
        [
          '$project' => [
            'valor' => '$valor',
            'valorMensajeIndividual' => 1,
          ],
        ],
      ],
      'cursor' => new stdClass(),
    ]
  );
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response = $result->toArray()[0]; */

  $valor = floatval($datosAcuseRecibo["cantidadMensajes"]) * $datosAcuseRecibo["valorMensajeIndividual"];

  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    ['_id' => new \MongoDB\BSON\ObjectId($datosAcuseRecibo["idConsolidado"])],
    ['$inc' => ['valor' => $valor]],
    ['multi' => true, 'upsert' => false]
  );
  return $con->executeBulkWrite(DB_INTRA_CHEC . '.log_difusion_consolidado_dod', $bulk);

  // $fechaResta = strtotime('-7 hour', strtotime($fecha));
  // $fechaResta = date('Y-m-d H:i:s', $fechaResta);
  /* $fecha = date('Y-m-d H:i:s');
  $bulk = new MongoDB\Driver\BulkWrite();
  $bulk->update(
    [
      'idConsolidado' => $datosAcuseRecibo["idConsolidado"],
      'celularSMS' => $datosAcuseRecibo["celular"]
    ],
    [
      '$set' => [
        'estado' => $datosAcuseRecibo["estado"],
        'fecha' => $fecha
      ]
    ],
    ['multi' => true, 'upsert' => false]
  );

  return $con->executeBulkWrite(DB_INTRA_CHEC . '.log_acuse_recibo_dod', $bulk); */
}

function getLogCelularesDodQuery($con, $datosFechasLogDod)
{
  $command = new MongoDB\Driver\Command(
    [
      'aggregate' => 'log_difusion_dod',
      'pipeline' => [
        [
          '$sort' => [
            "_id" => -1,
          ],
        ],
        [
          '$match' => [
            '$and' => [
              ["fechaEnvio" => ['$gte' => $datosFechasLogDod["fechaInicio"]]],
              ["fechaEnvio" => ['$lte' => $datosFechasLogDod["fechaFin"]]],
            ]
          ],
        ],
        [
          '$group' => [
            '_id' => '$celular',
            'doc' => [
              '$first' => '$$ROOT'
            ]
          ]
        ],
        [
          '$replaceRoot' => [
            'newRoot' => '$doc',
          ]
        ],
        [
          '$project' => [
            "celular" => 1,
            "cantidadCuotaMensajesCliente" => 1,
            "_id" => 0
          ]
        ]
      ],
      'cursor' => new stdClass(),
    ]
  );
  $result = $con->executeCommand(DB_INTRA_CHEC, $command);
  return $result->toArray();
}

function getLogCelularesDodExcluirQuery($con, $datosCelularesDod)
{
  $filter = [
    '$and' => [
      ["fechaEnvio" => ['$gte' => $datosCelularesDod["fechaInicioMensajesCliente"]]],
      ["fechaEnvio" => ['$lte' => $datosCelularesDod["fechaFinMensajesCliente"]]],
      ["celular" => ['$in' => $datosCelularesDod["celulares"]]],
      ["cantidadCuotaMensajesCliente" => ['$lte' => 0]]
    ]
  ];

  $options = [
    'projection' => [
      "celular" => 1,
      // "cantidadCuotaMensajesCliente" => 1,
      "_id" => 0
    ],
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".log_difusion_dod", $query);
  $arrayCelularesExcluir = [];

  foreach ($result->toArray() as $key => $value) {
    array_push($arrayCelularesExcluir, $value->celular);
  }
  return $arrayCelularesExcluir;
}

//Plataforma de monitoreo

function filterResultadoInvocar($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_invocar_lucy", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

function filterResultadoInvocarMes($con, $fechainicio, $fechafin) //qwerty
{
  $filter = [
    'FECHA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_invocar_lucy", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

function filterResultadoInvocarMes2($con)
{
  $anio = date('Y');
  $filter = [
    'FECHA' => new MongoDB\BSON\Regex($anio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

function filterResultadoMenus($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  //var_dump($respuesta);
  return $respuesta;
}

function filterResultadoMenusTotales($con, $fechainicio, $fechafin)
{
  $filter = [
    'MENU' => ['$ne' => '']
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  //var_dump($respuesta);
  return $respuesta;
}


//numero de comentarios
function countComentarios($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'calificacion_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'VOC' => ['$exists' => True]
        ]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultado = current($result->toArray());
  return $resultado;
}

//obtener los top de usuarios que mas reportes hacen de falla de energia y descargan copia de factura
function getTopDescargasReportes($con, $fechainicio, $fechafin)
{
  $resultado = array();
  $resultado['descargas'] = 0;
  $resultado['reportes'] = 0;
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          'MENU' => 'fCopia factura',
          'NIU' => [
            '$exists' => True
          ]
        ]
      ], [
        '$group' => [
          '_id' => '$NIU',
          'count' => [
            '$sum' => 1
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => '_id',
          'foreignField' => 'NIU',
          'as' => 'users'
        ]
      ], [
        '$sort' => [
          'count' => -1
        ]
      ], [
        '$limit' => 8
      ], [
        '$unwind' => [
          'path' => '$users'
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'NOMBRE' => '$users.NOMBRE',
          'NIU' => '$users.NIU',
          'NUMCONSULTAS' => '$count',
          'MUNICIPIO' => '$users.MUNICIPIO',
          'TYPE' => 'Copia factura'
        ]
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $descargas = $result->toArray();
  if ($descargas == false) {
    $descargas = 0;
  }

  $resultado['descargas'] = $descargas;

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_REPORTE' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          'NIU' => [
            '$exists' => True
          ]
        ]
      ], [
        '$group' => [
          '_id' => '$NIU',
          'count' => [
            '$sum' => 1
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => '_id',
          'foreignField' => 'NIU',
          'as' => 'users'
        ]
      ], [
        '$sort' => [
          'count' => -1
        ]
      ], [
        '$limit' => 8
      ], [
        '$unwind' => [
          'path' => '$users'
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'NOMBRE' => '$users.NOMBRE',
          'NIU' => '$users.NIU',
          'NUMCONSULTAS' => '$count',
          'MUNICIPIO' => '$users.MUNICIPIO',
          'TYPE' => 'Reporte de energía'
        ]
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $reportes = $result->toArray();
  if ($reportes == false) {
    $reportes = 0;
  }
  $resultado['reportes'] = $reportes;
  return $resultado;
}

function filterCalificaciones($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".calificacion_usuarios", $query);
  $respuesta = $result->toArray();
  return $respuesta;
}

function filterReporte($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'TELEFONO' => ['$exists' => true],
    'NOMBREUSUARIO' => ['$exists' => true],
  ];
  $Command = new MongoDB\Driver\Command(["count" => "reportes_sgo_chatbot", "query" => $filter]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = current($result->toArray());
  return $respuesta;
}


function filterResultado($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".log_resultados_usuarios", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

function filterConsultasSegmentosUbicacionMunicipio($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_RESULTADO' => [
      '$gte' => $fechainicio,
      '$lt' => $fechafin
    ],
    'NIU' => ['$exists' => true],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_resultados_usuarios", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      $filter = ['NIU' => $value->NIU];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . ".usuarios", $query);
      array_push($response, $result->toArray());
    }
  }

  return $response;
}

function filterConsultaMunicipio($con, $municipio)
{
  $filter = [
    'MUNICIPIO' => new MongoDB\BSON\Regex($municipio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".municipios_chec", $query);
  return $result->toArray();
}

function filterMunicipios($con)
{
  $filter = [];
  $options = [
    'sort' => [
      'MUNICIPIO' => 1,
    ],
  ];
  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".municipios_chec", $query);
  $resultado = $result->toArray();
  return $resultado;
}


function filterResportes($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".reportes_sgo_chatbot", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      $filter = [
        'NIU' => $value->NIU,
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_HEROKU_CHEC . ".usuarios", $query);
      array_push($response, $result->toArray());
    }
  }
  return $response;
}

function filterResportesSource($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'TELEFONO' => ['$exists' => true],
    'NOMBREUSUARIO' => ['$exists' => true],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".reportes_sgo_chatbot", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

function filterResportesSourcesMuniUbicacion($con, $fechainicio, $fechafin, $flag = true)
{

  if ($flag) {
    $filter = [
      'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'TELEFONO' => ['$exists' => true],
      'NOMBREUSUARIO' => ['$exists' => true],
    ];
  } else {
    $filter = [
      'TELEFONO' => ['$exists' => true],
      'NOMBREUSUARIO' => ['$exists' => true],
      'SOURCE' => ['$exists' => true]
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".reportes_sgo_chatbot", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      $filter = [
        'NIU' => $value->NIU,
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_HEROKU_CHEC . ".usuarios", $query);
      $resultado = $result->toArray();
      if (count($resultado) > 0) {
        $resultado[0]->SOURCE = $value->SOURCE;
        $resultado[0]->FECHA_REPORTE = $value->FECHA_REPORTE;
      }
      array_push($response, $resultado);
    }
  }
  return $response;
}


function filterCopiaFacturaSource($con, $fechainicio, $fechafin)
{
  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

/*function filterResportesCopiaFacturaMuniUbicacion($con, $fechainicio, $fechafin, $flag = true)
{
  if ($flag) {
    $filter = [
      'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU'=> ['$ne'=> '']
    ];
  } else {
    $filter = [
      '_id' => ['$ne' => '']
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      if (isset($value->SOURCE) && isset($value->NIU)) {
        if ((strcmp(strtolower($value->MENU), strtolower('fCopia factura')) == 0 || strcmp(strtolower($value->MENU), strtolower('Copia factura')) == 0) && $value->NIU != '' && $value->SOURCE != '') {
          $filter = [
            'NIU' => strval($value->NIU),
          ];
          $query = new MongoDB\Driver\Query($filter);
          $result = $con->executeQuery(DB_DIFUSION_CHEC . ".usuarios", $query);
          $resultado = $result->toArray();
          if (count($resultado) > 0) {
            $resultado[0]->SOURCE = $value->SOURCE;
            $resultado[0]->FECHA_RESULTADO = $value->FECHA_RESULTADO;
            $resultado[0]->MENU = $value->MENU;
          }
          array_push($response, $resultado);
        }
      }
    }
  }
  return $response;
}*/


function filterDifusionHoraDia($con, $fechainicio, $fechafin, $reglas)
{
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_EVENTO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'SGO' => 'indisponibilidad',
      'REGLAS' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_EVENTO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'SGO' => 'indisponibilidad',
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".log_difusion_consolidado", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}

/*function filterDifusionTendencias($con, $fechainicio, $fechafin, $reglas)
{
  $filter = '';
  if (is_array($reglas)) {
    $filter = [
      'FECHA_EVENTO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'SGO' => 'indisponibilidad',
      'REGLAS' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_EVENTO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'SGO' => 'indisponibilidad',
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . ".log_difusion_consolidado", $query);
  $respuesta = $result->toArray();

  return $respuesta;
}*/

function filterResportesGneral($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_REPORTE' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          'TELEFONO' => ['$exists' => true],
          'NOMBREUSUARIO' => ['$exists' => true],
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuario'
        ]
      ], [
        '$project' => [
          'NIU' => '$NIU',
          'MUNICIPIO' => '$usuario.MUNICIPIO',
          'UBICACION' => '$usuario.UBICACION',
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}

function filterConsultasFaltaEnergia($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          '$or' => [
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
            ]
          ]
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = current($result->toArray());
  return $response;
}

function filterConsultasFaltaEnergiaMeses($con)
{

  $anio = date('Y');
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => new MongoDB\BSON\Regex($anio, 'i'),
          '$or' => [
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
            ]
          ]
        ]
      ],
      [
        '$project' => [
          'FECHA_CONSULTA' => '$FECHA_RESULTADO'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}

function filterConsultasFaltaEnergiaFiltrados($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          '$or' => [
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'niu' => '$NIU',
          'fecha' => '$FECHA_RESULTADO',
          'usuario' => '$usuario'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}

function filterConsultasFaltaEnergiaFiltradosMeses($con, $fechainicio, $fechafin)
{
  $anio = date('Y');
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => new MongoDB\BSON\Regex($anio, 'i'),
          '$or' => [
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'niu' => '$NIU',
          'fecha' => '$FECHA_RESULTADO',
          'usuario' => '$usuario'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}

function filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($con, $fechainicio, $fechafin, $flag = true)
{
  $anio = date('Y');
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'TELEFONO' => ['$exists' => true],
          'NOMBREUSUARIO' => ['$exists' => true]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuario'
        ]
      ], [
        '$project' => [
          'NIU' => '$NIU',
          'MUNICIPIO' => '$usuario.MUNICIPIO',
          'UBICACION' => '$usuario.UBICACION',
          'FECHA_REPORTE' => '$FECHA_REPORTE',
          'SEGMENTO' => '$usuario.SEGMENTO',
          'SOURCE' => '$SOURCE',

        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response; //184
}


function difusionCantidadDifundida($con, $fechainicio, $fechafin, $reglas)
{
  $response = array();
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['aperturas'] = current($result->toArray());

  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'MENSAJE_CIERRE' => 'ok',
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'MENSAJE_CIERRE' => 'ok',
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['cierres'] = current($result->toArray());

  return $response;
}



function getAcuse_Recibo($con, $fechainicio, $fechafin, $reglas)
{
  /*  
  $filter = [
    'FECHA_ENTREGA_APERTURA' => ['$gte' => $fechaInicioSuma, '$lte' => $fechaFinSuma],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_acuse_recibo_dinp", $query);
  $respuesta = $result->toArray();*/
  //60754-248148-13570+32
  //13302


  $fechaInicioSuma = strtotime('+6 hour', strtotime($fechainicio));
  $fechaFinSuma = strtotime('+7 hour', strtotime($fechafin));

  $fechaInicioSuma = date('Y-m-d H:i:s', $fechaInicioSuma);
  $fechaFinSuma = date('Y-m-d H:i:s', $fechaFinSuma);

  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      [
        '$match' => [
          'FECHA_ENTREGA_APERTURA' => ['$gte' => $fechaInicioSuma, '$lte' => $fechaFinSuma],
        ]
      ], [
        '$lookup' => [
          'from' => 'log_acuse_recibo_dinp',
          'let' => [
            'cuenta' => '$NIU',
            'aper' => '$APERTURA',
            'regla' => '$REGLA'
          ],
          'pipeline' => [
            [
              '$match' => [
                '$expr' => [
                  '$and' => [
                    [
                      '$eq' => [
                        '$NIU', '$$cuenta'
                      ]
                    ], [
                      '$eq' => [
                        '$APERTURA', '$$aper'
                      ]
                    ]
                  ]
                ]
              ]
            ], [
              '$project' => [
                '_id' => 0
              ]
            ]
          ],
          'as' => 'user'
        ]
      ], [
        '$project' => [
          'regla' => '$user.REGLA',
          'ESTADO_APERTURA' => '$ESTADO_APERTURA',
          'ESTADO_CIERRE' => '$ESTADO_CIERRE'
        ]
      ], [
        '$match' => [
          'regla' => ['$in' => $reglas]
        ]
      ]
    ];
  } else {
    $filter = [
      [
        '$match' => [
          'FECHA_ENTREGA_APERTURA' => ['$gte' => $fechaInicioSuma, '$lte' => $fechaFinSuma],
        ]
      ], [
        '$lookup' => [
          'from' => 'log_acuse_recibo_dinp',
          'let' => [
            'cuenta' => '$NIU',
            'aper' => '$APERTURA',
            'regla' => '$REGLA'
          ],
          'pipeline' => [
            [
              '$match' => [
                '$expr' => [
                  '$and' => [
                    [
                      '$eq' => [
                        '$NIU', '$$cuenta'
                      ]
                    ], [
                      '$eq' => [
                        '$APERTURA', '$$aper'
                      ]
                    ]
                  ]
                ]
              ]
            ], [
              '$project' => [
                '_id' => 0
              ]
            ]
          ],
          'as' => 'user'
        ]
      ], [
        '$project' => [
          'regla' => '$user.REGLA',
          'ESTADO_APERTURA' => '$ESTADO_APERTURA',
          'ESTADO_CIERRE' => '$ESTADO_CIERRE'
        ]
      ]
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => $filter,
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $respuesta = $result->toArray();

  return $respuesta;
}

function difusionCantidadDifundida2($con, $fechainicio, $fechafin, $reglas)
{
  //envio apertura
  $response = array();
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$project' => [
          'FECHA_ENVIO_APERTURA' => '$FECHA_ENVIO_APERTURA'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['aperturas'] = $result->toArray();

  //-----envio cierre
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
      'MENSAJE_CIERRE' => 'ok',
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'MENSAJE_CIERRE' => 'ok',
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$project' => [
          'FECHA_ENVIO_CIERRE' => '$FECHA_ENVIO_CIERRE'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['cierres'] = $result->toArray();

  return $response;
}

function difusionCantidadDifundidaTotal2($con, $fechainicio, $fechafin, $reglas)
{
  //envio apertura
  $anio = date('Y');
  $response = array();
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_APERTURA' => new MongoDB\BSON\Regex($anio, 'i'),
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_APERTURA' => new MongoDB\BSON\Regex($anio, 'i'),
      'NIU' => ['$ne' => ''],
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$project' => [
          'FECHA_ENVIO_APERTURA' => '$FECHA_ENVIO_APERTURA'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['aperturas'] = $result->toArray();


  //-----envio cierre
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_CIERRE' => new MongoDB\BSON\Regex($anio, 'i'),
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
      'MENSAJE_CIERRE' => 'ok',
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_CIERRE' => new MongoDB\BSON\Regex($anio, 'i'),
      'NIU' => ['$ne' => ''],
      'MENSAJE_CIERRE' => 'ok',
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$project' => [
          'FECHA_ENVIO_CIERRE' => '$FECHA_ENVIO_CIERRE'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['cierres'] = $result->toArray();


  return $response;
}

function getAcuseRecibo_PromocionLucy($con, $fechainicio, $fechafin)
{
  $fechaInicioSuma = strtotime('+6 hour', strtotime($fechainicio));
  $fechaFinSuma = strtotime('+7 hour', strtotime($fechafin));

  $fechaInicioSuma = date('Y-m-d H:i:s', $fechaInicioSuma);
  $fechaFinSuma = date('Y-m-d H:i:s', $fechaFinSuma);

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENTREGA' => ['$gte' => $fechaInicioSuma, '$lte' => $fechaFinSuma],
          'NIU' => ['$ne' => ''],
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response = $result->toArray();

  $mesnajesTotales = array();
  foreach ($response as $clave => $valor) {
    if (count($valor->usuarios) >= 1) {
      $valor->FECHA_ENTREGA;
      array_push($mesnajesTotales, $valor->FECHA_ENTREGA);
    }
  }


  return $mesnajesTotales;
}

function getAcuseReciboPromocion_Programadas($con, $fechainicio, $fechafin)
{
  $fechaInicioSuma = strtotime('+6 hour', strtotime($fechainicio));
  $fechaFinSuma = strtotime('+7 hour', strtotime($fechafin));

  $fechaInicioSuma = date('Y-m-d H:i:s', $fechaInicioSuma);
  $fechaFinSuma = date('Y-m-d H:i:s', $fechaFinSuma);

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_PROMOCION_PROGRAMADAS' => ['$gte' => $fechaInicioSuma, '$lte' => $fechaFinSuma],
          'NIU' => ['$ne' => ''],
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response = $result->toArray();

  $mesnajesTotales = array();
  foreach ($response as $clave => $valor) {
    if (count($valor->usuarios) >= 1) {
      $valor->FECHA_PROMOCION_PROGRAMADAS;
      array_push($mesnajesTotales, $valor->FECHA_PROMOCION_PROGRAMADAS);
    }
  }


  return $mesnajesTotales;
}

//Cantidad de cancelaciones recibidas por día
function filterCancelacionesPorDia($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_canceladas',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_INICIO' => [
            '$gte' => $fechainicio
          ],
          'FECHA_FIN' => [
            '$lte' => $fechafin
          ]
        ]
      ], [
        '$count' => 'cantidadCancelaciones'
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}

//Cantidad de mensajes enviados por día
function filterCancelacionesMensajesEnviadosPorDia($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_canceladas',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_INICIO' => [
            '$gte' => $fechainicio
          ],
          'FECHA_FIN' => [
            '$lte' => $fechafin
          ],
          'FILE_REGISTER' => [
            '$type' => 'string'
          ]
        ]
      ], [
        '$count' => 'cantidadCancelaciones'
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response = $result->toArray();

  return $response;
}


//Cantidad de mensajes enviados por orden (tabla)
function filterCancelacionesMensajesEnviadosPorOrden($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_canceladas',
    'pipeline' => [
      [
          '$match'=> [
              'FECHA_INICIO'=> [
                  '$gte'=> $fechainicio
              ], 
              'FECHA_FIN'=> [
                  '$lte'=> $fechafin
              ], 
              'ORDEN_OP'=> [
                  '$ne'=> ''
              ]
          ]
      ], [
          '$group'=> [
              '_id'=> '$ORDEN_OP', 
              'cantidadCancelaciones'=> [
                  '$sum'=> 1
              ]
          ]
      ]
  ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response = $result->toArray();

  return $response;
}


//traer reglas de difusion
function filterReglas($con)
{

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reglas_difusion_mejorado',
    'pipeline' => [
      [
        '$project' => [
          '_id' => 0,
          'regla' => '$REGLA',
          'nombres' => '$SEGMENTO'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();


  return $response;
}

function filterReglasTotales($con)
{
  $filter = [];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".reglas_difusion_mejorado", $query);
  $response = $result->toArray();
  return $response;
}

function filterConsultasSegmentosUbicacionMunicipio2($con, $fechainicio, $fechafin, $reglas)
{
  $filter = '';
  $switches = array();
  $circuitos = array();
  $circuitosUnique = array();
  $usuarios = array();
  if (is_array($reglas)) {
    $filter = [
      'FECHA_EVENTO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'SGO' => 'indisponibilidad',
      'TIPO_SUSPENSION' => 'no programada',
      'REGLAS' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_EVENTO' => ['$gte' => $fechainicio, '$lte' => $fechafin],
      'SGO' => 'indisponibilidad',
      'TIPO_SUSPENSION' => 'no programada',
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_difusion_consolidado", $query);
  $respuesta = $result->toArray();
  return $respuesta;
}

function filterConsultasSegmentosUbicacionMunicipio3($con, $fechainicio, $fechafin, $reglas)
{
  //envio apertura
  $response = array();
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ],
      [
        '$project' => [
          'usuarios' => '$usuarios'
        ]
      ], [
        '$sort' => [
          '_id' => 1
        ]
      ]
    ],
    'allowDiskUse' => true,
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['aperturas'] = $result->toArray();

  //-----envio cierre
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'MENSAJE_CIERRE' => 'ok',
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $filter,
      ],
      [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ],
      [
        '$project' => [
          'usuarios' => '$usuarios'
        ]
      ], [
        '$sort' => [
          '_id' => 1
        ]
      ]
    ],
    'allowDiskUse' => true,
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['cierres'] = $result->toArray();

  $mesnajesTotales = array();

  foreach ($response['aperturas'] as $clave => $valor) {
    if (count($valor->usuarios) >= 1) {
      array_push($mesnajesTotales, $valor->usuarios[0]);
    }
  }

  foreach ($response['cierres'] as $clave => $valor) {
    if (count($valor->usuarios) >= 1) {
      array_push($mesnajesTotales, $valor->usuarios[0]);
    }
  }

  return $mesnajesTotales;
}


function filterConsultaUsuariosXMunicipio($con)
{

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'usuarios',
    'pipeline' => [
      [
        '$group' => [
          '_id' => '$MUNICIPIO',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

/*function filterConsultaMunicipio2($con, $municipio)
{
  $filter = [
    'MUNICIPIO' => new MongoDB\BSON\Regex($municipio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . ".municipios_chec", $query);
  return $result->toArray();
}*/

/*function getRules_Difusion($con)
{
  $filter = [];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . ".reglas_difusion", $query);
  $respuesta = $result->toArray();
  $reglas = array();
  foreach ($respuesta as $clave => $valor) {
    array_push($reglas, $valor->REGLA);
  }

  return $reglas;
}*/

function filterDifusionPorReglas($con, $fechainicio, $fechafin, $reglas)
{

  $filter = '';

  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lte' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas]
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lte' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$ne' => ''],
    ];
  }

  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".log_difusion_enviados", $query);
  $respuesta['reglas_apertura'] = $result->toArray();

  $filter = '';

  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lte' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas]
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lte' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$ne' => ''],
    ];
  }

  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . '.log_difusion_enviados', $query);
  $respuesta['reglas_cierre'] = $result->toArray();

  return $respuesta;
}

function getConsultas_LucyAno($con, $ano)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => new MongoDB\BSON\Regex($ano, 'i'),
          '$or' => [
            [
              'MENU' => 'Falta de Energia'
            ], [
              'MENU' => 'Pqr'
            ], [
              'MENU' => 'Puntos de Atencion'
            ], [
              'MENU' => 'Vacantes'
            ], [
              'MENU' => 'Pago factura'
            ], [
              'MENU' => 'Fraudes'
            ], [
              'MENU' => 'Copia factura'
            ], [
              'MENU' => 'Asesor remoto'
            ]
          ]
        ]
      ],
      [
        '$project' => [
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_RESULTADO',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$month',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function getMensajes_DifusionAno($con, $ano)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_APERTURA' => new MongoDB\BSON\Regex($ano, 'i'),
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENVIO_APERTURA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$month',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta1 = $result->toArray();


  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_CIERRE' => new MongoDB\BSON\Regex($ano, 'i'),
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENVIO_CIERRE',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$month',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta2 = $result->toArray();

  //FECHA_ENVIO_CIERRE
  foreach ($respuesta1 as $clave => $valor) {
    foreach ($respuesta2 as $clave2 => $valor2) {
      if ($clave == $clave2) {
        $valor->cantidad = $valor->cantidad + $valor2->cantidad;
      }
    }
  }

  return $respuesta1;
}


function get_TipificacionAno($con, $ano)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => new MongoDB\BSON\Regex($ano, 'i'),
        ],
      ],
      [
        '$project' => [
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$Fecha',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$month',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function get_TurnosAno($con, $ano)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'turnos',
    'pipeline' => [
      [
        '$match' => [
          'FechaImpresion' => new MongoDB\BSON\Regex($ano, 'i'),
        ],
      ],
      [
        '$project' => [
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FechaImpresion',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$month',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

/*function get_AvisosSuspensionesAno2($con, $ano)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_avisos',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO' => ['$gte' => '2020-03-10', '$lt' => '2020-03-12'],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray(); //690847

  foreach ($respuesta as $clave => $valor) {
    $fechaInicio = formatDate($valor->FECHA_INICIO);
    $fechaFinal = formatDate($valor->FECHA_FIN);

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
      ['_id' => new MongoDB\BSON\ObjectId($valor->_id)],
      ['$set' => [
        'FECHA_INICIO' => strval($fechaInicio),
        'FECHA_FIN' => strval($fechaFinal)
      ]]
    );

    $result = $con->executeBulkWrite(DB_DIFUSION_CHEC . '.susp_programadas_avisos', $bulk);
  }
  return $respuesta; //832400
}*/

function formatDate($fechaDB)
{
  $fecha = explode('/', $fechaDB);
  $fechaReverse = array_reverse($fecha);
  return implode('-', $fechaReverse);
}

function get_AvisosSuspensionesAno($con, $ano)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_avisos',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_INICIO' => new MongoDB\BSON\Regex($ano, 'i'),
        ],
      ],
      [
        '$project' => [
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_INICIO',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$month',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray(); //690847
  return $respuesta; //832400
}

function get_ConsultasLucy_Dif_llam_MesAno($con, $conChec, $ano, $mes, $campo, $coleccion)
{

  if($coleccion == 'susp_programadas_avisos'){
    $col = DB_HEROKU_CHEC_SGCB;
    $conexion = $conChec;
  }else{
    $col = DB_DIFUSION_CHEC;
    $conexion = $con;
  }

  if ($coleccion == 'log_menu_usuarios') {
    $pipeline = [
      [
        '$match' => [
          '$or' => [
            [
              'MENU' => 'Falta de Energia'
            ], [
              'MENU' => 'Pqr'
            ], [
              'MENU' => 'Puntos de Atencion'
            ], [
              'MENU' => 'Vacantes'
            ], [
              'MENU' => 'Pago factura'
            ], [
              'MENU' => 'Fraudes'
            ], [
              'MENU' => 'Copia factura'
            ], [
              'MENU' => 'Asesor remoto'
            ]
          ]
        ]
      ],
      [
        '$project' => [
          'ano' => [

            '$year' => [
              '$dateFromString' => [
                'dateString' => "$campo",
              ],
            ],
          ],
          'mes' => [
            '$month' => [
              '$dateFromString' => [
                'dateString' => "$campo",
              ],
            ],
          ],

          'dia' => [
            '$dayOfMonth' => [
              '$dateFromString' => [
                'dateString' => "$campo",
              ],
            ],
          ],

        ],
      ],
      [
        '$match' => [
          'ano' => (int)$ano,
          'mes' => (int)$mes,
        ],
      ],
      [
        '$group' => [
          '_id' => '$dia',
          'suma' => [
            '$sum' => 1,
          ],
        ],
      ],
    ];
  } else {
    $pipeline = [
      [
        '$project' => [
          'ano' => [

            '$year' => [
              '$dateFromString' => [
                'dateString' => "$campo",
              ],
            ],
          ],
          'mes' => [
            '$month' => [
              '$dateFromString' => [
                'dateString' => "$campo",
              ],
            ],
          ],

          'dia' => [
            '$dayOfMonth' => [
              '$dateFromString' => [
                'dateString' => "$campo",
              ],
            ],
          ],

        ],
      ],
      [
        '$match' => [
          'ano' => (int)$ano,
          'mes' => (int)$mes,
        ],
      ],
      [
        '$group' => [
          '_id' => '$dia',
          'suma' => [
            '$sum' => 1,
          ],
        ],
      ],
    ];
  }
  $Command = new MongoDB\Driver\Command([
    'aggregate' => $coleccion,
    'pipeline' => $pipeline,
    'cursor' => new stdClass,
  ]);
  $result = $conexion->executeCommand($col, $Command);
  $respuesta = $result->toArray();
  return $respuesta; //8:2325 - 14:1381 - 26:60 - 5:5614
}

function get_ConsultasLucy_Dif_llam_MesAnoApertura2($con, $ano, $mes, $campo, $coleccion)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => $coleccion,
    'pipeline' => [
      [
        '$project' => [
          'ano' => [
            '$year' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_APERTURA',
              ],
            ],
          ],
          'mes' => [
            '$month' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_APERTURA',
              ],
            ],
          ],

          'dia' => [
            '$dayOfMonth' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_APERTURA',
              ],
            ],
          ],

        ],
      ],
      [
        '$match' => [
          'ano' => (int)$ano,
          'mes' => (int)$mes,
        ],
      ],
      [
        '$group' => [
          '_id' => '$dia',
          'suma' => [
            '$sum' => 1,
          ],
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta1 = $result->toArray(); //3733

  return $respuesta1; //11:1558 - 20:3604
}

function get_ConsultasLucy_Dif_llam_MesAnoCierre2($con, $ano, $mes, $coleccion)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => $coleccion,
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_CIERRE' => ['$ne' => ''],
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'ano' => [
            '$year' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_CIERRE',
              ],
            ],
          ],
          'mes' => [
            '$month' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_CIERRE',
              ],
            ],
          ],

          'dia' => [
            '$dayOfMonth' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_CIERRE',
              ],
            ],
          ],

        ],
      ],
      [
        '$match' => [
          'ano' => (int)$ano,
          'mes' => (int)$mes,
        ],
      ],
      [
        '$group' => [
          '_id' => '$dia',
          'suma' => [
            '$sum' => 1,
          ],
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta1 = $result->toArray();

  return $respuesta1;
}

function prueb($con, $fechaInicio, $fechaFin)
{
  $prueba = 0;
  return $prueba;
}


function getConsultas_LucyDia($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_RESULTADO',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}


function obtenerConsultasLucyDia($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    //'aggregate' => 'log_resultados_usuarios', //4000...
    'aggregate' => 'log_menu_usuarios', //7.275 
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => ['$gte' => $fechaInicio, '$lte' => $fechaFin],
          '$or' => [
            [
              'MENU' => 'Falta de Energia'
            ], [
              'MENU' => 'Pqr'
            ], [
              'MENU' => 'Puntos de Atencion'
            ], [
              'MENU' => 'Vacantes'
            ], [
              'MENU' => 'Pago factura'
            ], [
              'MENU' => 'Fraudes'
            ], [
              'MENU' => 'Copia factura'
            ], [
              'MENU' => 'Asesor remoto'
            ]
          ]
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_RESULTADO',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

//consultar mensajes de difusin de apertura y cierre
function difusionSegementos($con, $fechainicio, $fechafin, $reglas)
{
  //envio apertura
  $response = array();
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".log_difusion_enviados", $query);
  $response['aperturas'] = $result->toArray(); //57515

  //-----envio cierre
  $filter = '';
  if (is_array($reglas) && count($reglas) > 0) {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'REGLA' => ['$in' => $reglas],
      'MENSAJE_CIERRE' => 'ok',
    ];
  } else {
    $filter = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$ne' => ''],
      'MENSAJE_CIERRE' => 'ok',
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_INTRA_CHEC . ".log_difusion_enviados", $query);
  $response['cierres'] = $result->toArray(); //42496



  return $response;
}

function difusionUbcacion($con, $fechainicio, $fechafin, $reglas)
{


  //envio apertura
  $response = array();
  $match = [];
  if (is_array($reglas) && count($reglas) > 0) {

    $match = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'REGLA' => ['$in' => $reglas],
      'NIU' => ['$regex' => new MongoDB\BSON\Regex('^[0-9]{9}$', 'i')]
    ];
  } else {
    $match = [
      'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$regex' => new MongoDB\BSON\Regex('^[0-9]{9}$', 'i')]
    ];
  }

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $match
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'UBICACION' => '$usuario.UBICACION'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['aperturas'] = $result->toArray(); //57548 - 5484



  //-----envio cierre
  $match = [];
  if (is_array($reglas) && count($reglas) > 0) {

    $match = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$regex' => new MongoDB\BSON\Regex('^[0-9]{9}$', 'i')],
      'REGLA' => ['$in' => $reglas],
      'MENSAJE_CIERRE' => 'ok',
    ];
  } else {
    $match = [
      'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'NIU' => ['$regex' => new MongoDB\BSON\Regex('^[0-9]{9}$', 'i')],
      'MENSAJE_CIERRE' => 'ok'
    ];
  }

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => $match
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'UBICACION' => '$usuario.UBICACION'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_INTRA_CHEC, $Command);
  $response['cierres'] = $result->toArray(); //42742 - 4448

  return $response;
}



function getMensajes_DifusionDia($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_APERTURA' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_APERTURA',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta1 = $result->toArray();

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_CIERRE' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_ENVIO_CIERRE',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta2 = $result->toArray();


  foreach ($respuesta1 as $clave => $valor) {
    foreach ($respuesta2 as $clave2 => $valor2) {
      if ($clave == $clave2) {
        $valor->cantidad = $valor->cantidad + $valor2->cantidad;
      }
    }
  }
  return $respuesta1;
}

function get_TipificacionDia($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$Fecha',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function get_TurnosDia($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'turnos',
    'pipeline' => [
      [
        '$match' => [
          'FechaImpresion' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$FechaImpresion',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function get_AvisosSuspensionesDia($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_avisos',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_INICIO' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'day' => [
            '$dayOfWeek' => [
              '$dateFromString' => [
                'dateString' => '$FECHA_INICIO',
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$day',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}


function getMensajes_DifusionHora($con, $fechaInicio, $fechaFin)
{
  $fechaInicioSuma = strtotime('+7 hour', strtotime($fechaInicio));
  $fechaFinSuma = strtotime('+7 hour', strtotime($fechaFin));

  $fechaInicioSuma = date('Y-m-d H:i:s', $fechaInicioSuma);
  $fechaFinSuma = date('Y-m-d H:i:s', $fechaFinSuma);

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_APERTURA' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'hour' => [
            '$dateToString' => [
              'format' => '%H',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENVIO_APERTURA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$hour',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta1 = $result->toArray();


  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_difusion_enviados',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO_CIERRE' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
          'NIU' => ['$ne' => ''],
        ],
      ],
      [
        '$project' => [
          'hour' => [
            '$dateToString' => [
              'format' => '%H',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENVIO_CIERRE',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$hour',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta2 = $result->toArray();


  foreach ($respuesta1 as $clave => $valor) {
    foreach ($respuesta2 as $clave2 => $valor2) {
      if ($clave == $clave2) {
        $valor->cantidad = $valor->cantidad + $valor2->cantidad;
      }
    }
  }
  return $respuesta1;
}
function get_TipificacionHora($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'hora' => [
            '$split' => ['$Hora', ':'],
          ],
        ],
      ],
      [
        '$unwind' => [
          'path' => '$hora',
          'includeArrayIndex' => 'indice',
        ],
      ],
      [
        '$match' => [
          'indice' => 0,
        ],
      ],
      [
        '$group' => [
          '_id' => '$hora',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}
function get_TurnosHora($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'turnos',
    'pipeline' => [
      [
        '$match' => [
          'FechaImpresion' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'hour' => [
            '$dateToString' => [
              'format' => '%H',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FechaImpresion',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$hour',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}
function get_AvisosSuspensionesHora($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'susp_programadas_avisos',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENVIO' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'hour' => [
            '$dateToString' => [
              'format' => '%H',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENVIO',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$hour',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function getConsultas_LucyHora($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => ['$gte' => $fechaInicio, '$lte' => $fechaFin],
          '$or' => [
            [
              'MENU' => 'Falta de Energia'
            ], [
              'MENU' => 'Pqr'
            ], [
              'MENU' => 'Puntos de Atencion'
            ], [
              'MENU' => 'Vacantes'
            ], [
              'MENU' => 'Pago factura'
            ], [
              'MENU' => 'Fraudes'
            ], [
              'MENU' => 'Copia factura'
            ], [
              'MENU' => 'Asesor remoto'
            ]
          ]
        ],
      ],
      [
        '$project' => [
          'hour' => [
            '$dateToString' => [
              'format' => '%H',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_RESULTADO',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$hour',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function filtrarUusarios($con, $criterio, $valor)
{
  $filter = array();
  if (strcmp(strtolower($criterio), 'nombre') == 0) {
    $filter = getNamesQuery($valor);
  } else if (strcmp(strtolower($criterio), 'direccion') == 0) {
    $filter = getAdressQuery($valor);
  } else if (strcmp(strtolower($criterio), 'documento') == 0) {
    $filter = ['DOCUMENTO' => $valor, 'TIPO_DOC' => "CC"];
  } else if (strcmp(strtolower($criterio), 'nit') == 0) {
    $filter = ['DOCUMENTO' => (string) $valor, 'TIPO_DOC' => "NI"];
  } else if (strcmp(strtolower($criterio), 'cuenta') == 0) {
    $filter = ['NIU' => (string) $valor];
  } else if (strcmp(strtolower($criterio), 'telefono') == 0) {
    if (strlen($valor) == 10) {
      $filter = ['CELULAR' => (string) $valor];
    } else {
      $filter = ['TELEFONO' => (string) $valor];
    }
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . '.usuarios', $query);
  $resultado = $result->toArray();

  return $resultado;
}

function getNamesQuery($palabras)
{
  $num = count($palabras);
  switch ($num) {
    case 1:
      $filter = [
        'NOMBRE' => new MongoDB\BSON\Regex($palabras[0], 'i'),
      ];
      break;
    case 2:
      $filter = [
        'NOMBRE' => new MongoDB\BSON\Regex($palabras[0], 'i'),
        '$and' => [
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[1], 'i')],
        ],
      ];
      break;
    case 3:
      $filter = [
        'NOMBRE' => new MongoDB\BSON\Regex($palabras[0], 'i'),
        '$and' => [
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[2], 'i')],
        ],
      ];
      break;
    case 4:
      $filter = [
        'NOMBRE' => new MongoDB\BSON\Regex($palabras[0], 'i'),
        '$and' => [
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['$or' => [
            ['NOMBRE' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ]],
        ],
      ];
      break;
    case 5:
      $filter = [
        'NOMBRE' => new MongoDB\BSON\Regex($palabras[0], 'i'),
        '$and' => [
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['$or' => [
            ['NOMBRE' => new MongoDB\BSON\Regex($palabras[3], 'i')],
            ['NOMBRE' => new MongoDB\BSON\Regex($palabras[4], 'i')],
          ]],
        ],
      ];
      break;
    case 6:
      $filter = [
        'NOMBRE' => new MongoDB\BSON\Regex($palabras[0], 'i'),
        '$and' => [
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['NOMBRE' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['$or' => [
            ['NOMBRE' => new MongoDB\BSON\Regex($palabras[3], 'i')],
            ['NOMBRE' => new MongoDB\BSON\Regex($palabras[4], 'i')],
            ['NOMBRE' => new MongoDB\BSON\Regex($palabras[5], 'i')],
          ]],
        ],
      ];
      break;
    default:
      $filter = null;
      break;
  }
  return $filter;
}

function getAdressQuery($palabras)
{
  $num = count($palabras);
  switch ($num) {
    case 1:
      $filter = [
        'DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i'),
      ];
      break;
    case 2:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
        ],
      ];
      break;
    case 3:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
        ],
      ];
      break;
    case 4:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
        ],
      ];
      break;
    case 5:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[4], 'i')],
        ],
      ];
      break;
    case 6:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[4], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[5], 'i')],
        ],
      ];
      break;
    case 7:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[4], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[5], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[6], 'i')],
        ],
      ];
      break;
    case 8:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[4], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[5], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[6], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[7], 'i')],
        ],
      ];
      break;
    case 9:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[4], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[5], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[6], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[7], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[8], 'i')],
        ],
      ];
      break;
    case 10:
      $filter = [
        '$and' => [
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[0], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[1], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[2], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[3], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[4], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[5], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[6], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[7], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[8], 'i')],
          ['DIRECCION' => new MongoDB\BSON\Regex($palabras[9], 'i')],
        ],
      ];
      break;

    default:
      $filter = null;
      break;
  }

  return $filter;
}

function getAcuseReciboHitorial_PromocionLucy($con, $niu, $fechainicio, $fechafin)
{
  $fechaInicioSuma = strtotime('+6 hour', strtotime($fechainicio));
  $fechaFinSuma = strtotime('+7 hour', strtotime($fechafin));

  $fechaInicioSuma = date('Y-m-d H:i:s', $fechaInicioSuma);
  $fechaFinSuma = date('Y-m-d H:i:s', $fechaFinSuma);

  $filter = [
    'FECHA_ENTREGA' => ['$gte' => $fechainicio, '$lte' => $fechafin],
    'NIU' => (string) $niu
  ];
  $options = [
    'sort' => [
      'FECHA_ENTREGA' => -1,
    ],
  ];
  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_acuse_recibo_dinp", $query);
  $respuesta['consulta_filtrada'] = $result->toArray();

  //-----------------------------

  $filter = '';

  $filter = [
    'FECHA_ENTREGA' => ['$gte' => '1900-01-02 00:00'],
    'NIU' => (string) $niu
  ];
  $options = [
    'sort' => [
      'FECHA_ENTREGA' => -1,
    ],
  ];
  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_acuse_recibo_dinp", $query);
  $respuesta['consulta_no_filtrada'] = $result->toArray();


  return $respuesta;
}


function llamadasContact($con, $niu, $fechaInicio, $fechaFin)
{
  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
    'C_Cuenta' => $niu,
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  $cuentas['consulta_filtrada'] = $respuesta;

  $filter = '';
  $filter = [
    'C_Cuenta' => $niu,
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  $cuentas['consulta_no_filtrada'] = $respuesta;

  return $cuentas;
}

function invitacionSusProgramadas($con, $niu, $fechaInicio, $fechaFin)
{
  //filtrar cuentas a las que se les ha hecho invitacion a sus progrsmadas
  $filter = [
    'FECHA_PROMOCION_PROGRAMADAS' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
    'ESTADO_PROMOCION_PROGRAMADAS' => ['$exists' => true],
    'NIU' => $niu,
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_acuse_recibo_dinp", $query);
  $respuesta = $result->toArray();
  $cuentas['consulta_filtrada'] = $respuesta;

  $filter = '';
  $filter = [
    'ESTADO_PROMOCION_PROGRAMADAS' => ['$exists' => true],
    'NIU' => $niu,
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_acuse_recibo_dinp", $query);
  $respuesta = $result->toArray();
  $cuentas['consulta_no_filtrada'] = $respuesta;

  return $cuentas;
}

function difusion($con, $niu,  $fechainicio, $fechafin)
{
  //envio apertura
  $response = array();
  $filter = '';

  $options = [
    'sort' => [
      'FECHA_ENTREGA' => -1,
    ],
  ];

  $filter = [
    'FECHA_ENVIO_APERTURA' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'NIU' => $niu,
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_difusion_enviados", $query);
  $response['aperturas_filtradas'] = $result->toArray();

  $filter = '';

  $filter = [
    'FECHA_ENVIO_APERTURA' => ['$exists' => true],
    'NIU' => $niu,
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_difusion_enviados", $query);
  $response['aperturas_no_filtradas'] = $result->toArray();

  //-----envio cierre

  $filter = '';

  $filter = [
    'FECHA_ENVIO_CIERRE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'NIU' => $niu,
    'MENSAJE_CIERRE' => 'ok',
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_difusion_enviados", $query);
  $response['cierres_filtrados'] = $result->toArray();

  $filter = '';

  $filter = [
    'FECHA_ENVIO_CIERRE' => ['$exists' => true],
    'NIU' => $niu,
    'MENSAJE_CIERRE' => 'ok',
  ];

  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_HEROKU_CHEC_SGCB . ".log_difusion_enviados", $query);
  $response['cierres_no_filtrados'] = $result->toArray();


  return $response;
}

function orderSessions($con)
{
  $filter = [];
  $options = [
    'limit' => 100
  ];
  $query = new MongoDB\Driver\Query($filter, $options);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.sessions', $query);
  $response = $result->toArray();

  return $response;
}

function getChacts($con, $ordenarSesiones)
{

  $chat = array();
  $cont = 0;
  foreach ($ordenarSesiones as $sesion) {
    $filter = [
      'mensaje.codUser' => $sesion
    ];
    $options = [
      'sort' => [
        'mensaje.fecha' => 1,
      ],
    ];

    $query = new MongoDB\Driver\Query($filter, $options);
    $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
    $prueba = $result->toArray();
    if (count($prueba) > 1) {
      $chat[$cont] = $prueba;
      $cont += 1;
    }
  }
  return $chat;
}


/*function getConversacionLucy($con, $niu, $fechaInicio, $fechaFin)
{

  $chat = array();
  $cont = 0;
  $filter = [
    'mensaje.text' => $niu
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
  $resultado = $result->toArray();
  if (count($resultado) == 1) {
    foreach ($resultado as $clave => $valor) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $valor->mensaje[0]->codUser
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
      $chat[$cont] = $result->toArray();
    }
  } else if (count($resultado) > 1) {
    $sesisiones = sesiones($resultado);
    $valorSesion = array_values(array_unique($sesisiones));
    if (count(array_values(array_unique($sesisiones))) > 1) {
      foreach ($valorSesion as $sesion) {
        $filter = [
          'mensaje.fecha' => ['$gte' => (int) $fechaInicio, '$lt' => (int)$fechaFin],
          'mensaje.codUser' => $sesion
        ];
        $options = [
          'sort' => [
            'mensaje.fecha' => 1,
          ],
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
        $prueba = $result->toArray();
        // if (count($prueba) > 1) {
        $chat[$cont] = $prueba;
        $cont += 1;
        //}
      }
    } else if (count(array_values(array_unique($sesisiones))) == 1) {
      $valorSesion = array_values(array_unique($sesisiones));
      $chat['chats_no_filtrados'] = filterAllChatsByUser($con, $valorSesion);
      foreach ($valorSesion as $clave => $valor) {
        $filter = [
          'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
          'mensaje.codUser' => $valor
        ];
        $options = [
          'sort' => [
            'mensaje.fecha' => 1,
          ],
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
        $chat[$cont] = $result->toArray();
      }
    }
  }

  return $chat;
}*/

function getConversacionLucy($con, $niu, $fechaInicio, $fechaFin)
{

  //Chats filtrados por fecha
  $chat = array();
  $cont = 0;
  $filter = [
    'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
    'mensaje.text' => $niu
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
  $resultado = $result->toArray();
  if (count($resultado) == 1) {
    foreach ($resultado as $clave => $valor) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $valor->mensaje[0]->codUser
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
      $chat['chats_filtrados'][$cont] = $result->toArray();
    }
  } else if (count($resultado) > 1) {
    $sesisiones = sesiones($resultado);
    $valorSesion = array_values(array_unique($sesisiones));
    foreach ($valorSesion as $sesion) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int) $fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $sesion
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
      $prueba = $result->toArray();
      // if (count($prueba) > 1) {
      $chat['chats_filtrados'][$cont] = $prueba;
      $cont += 1;
      //}
    }
  } else if (count($resultado) == 0) {
    $chat['chats_filtrados'] = array();
  }


  //Chat no filtrados
  $cont = 0;
  $filter = 0;
  $filter = [
    'mensaje.text' => $niu
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
  $resultado = $result->toArray();
  if (count($resultado) == 1) {
    foreach ($resultado as $clave => $valor) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $valor->mensaje[0]->codUser
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
      $chat['chats_no_filtrados'][$cont] = $result->toArray();
    }
  } else if (count($resultado) > 1) {
    $sesisiones = sesiones($resultado);
    $valorSesion = array_values(array_unique($sesisiones));
    foreach ($valorSesion as $sesion) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int) $fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $sesion
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
      $prueba = $result->toArray();
      // if (count($prueba) > 1) {
      $chat['chats_no_filtrados'][$cont] = $prueba;
      $cont += 1;
      //}
    }
  } else if (count($resultado) == 0) {
    $chat['chats_no_filtrados'] = array();
  }

  return $chat;
}

function getConversacionFallback($con, $conChec,  $fechaInicio, $fechaFin, $fechaInicioUnix, $fechaFinUnix)
{
  //Chats filtrados por fecha
  /*$filter = [
    'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
  $resultado = $result->toArray();*/


  $chat = array();
  $cont = 0;
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'fallback',
    'pipeline' => [
      [
        '$match' => [
          'FECHA' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $conChec->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultado = $result->toArray();
  if (count($resultado) == 1) {
    foreach ($resultado as $clave => $valor) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $valor->mensaje[0]->codUser
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
      $chat['chats_filtrados'][$cont] = $result->toArray();
    }
  } else if (count($resultado) > 1) {
    $sesisiones = sesionesFallback($resultado);
    $valorSesion = array_values(array_unique($sesisiones));
    foreach ($valorSesion as $sesion) {
      /*$filter = [
        'mensaje.fecha' => ['$gte' => (int) $fechaInicioUnix, '$lt' => (int)$fechaFinUnix],
        'mensaje.codUser' => $sesion
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.chats', $query);
      $prueba = $result->toArray();
      // if (count($prueba) > 1) {
      $chat['chats_filtrados'][$cont] = $prueba;
      $cont += 1;*/
      //}

      $Command = new MongoDB\Driver\Command([
        'aggregate' => 'chats',
        'pipeline' => [
          [
            '$match' => [
              'mensaje.fecha' => ['$gte' => (int) $fechaInicioUnix, '$lt' => (int)$fechaFinUnix],
              'mensaje.codUser' => $sesion
            ]
          ]
        ],
        'cursor' => new stdClass,
      ]);
      $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
      $prueba = $result->toArray();
      $chat['chats_filtrados'][$cont] = $prueba;
      $cont += 1;
    }
  }

  return $chat;
}

/*function filterAllChatsByUser($con, $valorSesion)
{

  $chatsSessions = array();
  foreach ($valorSesion as $clave => $valor) {
    $filter = [
      'mensaje.codUser' => $valor
    ];
    $options = [
      'sort' => [
        'mensaje.fecha' => 1,
      ],
    ];

    $query = new MongoDB\Driver\Query($filter, $options);
    $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
    array_push($chatsSessions, $result->toArray());
  }
  return $chatsSessions;
}*/



function getLlamadasCunetasTelefonoValidosMunicipios($con, $fechainicio, $fechafin, $municipio)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{6,10}$", 'i'),
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
        '$and' => [
          [
            '$or' => [
              ['TELEFONO' => $valor->C_Telefono],
              ['CELULAR' => $valor->C_Telefono],
            ]
          ]
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['cuentas_telefonos_validas'] = $cont;


  return $telefonos;
}

function getLlamadasCunetasTelefonoValidosUbicacion($con, $fechainicio, $fechafin, $ubicacion)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{6,10}$", 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
        'NIU' => $valor->C_Cuenta,
        '$and' => [
          [
            '$or' => [
              ['TELEFONO' => $valor->C_Telefono],
              ['CELULAR' => $valor->C_Telefono],
            ]
          ]
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['cuentas_telefonos_validas'] = $cont;


  return $telefonos;
}

function getLlamadasCunetasTelefonoValidosUbicacionMunicipio($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{6,10}$", 'i'),
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
        'NIU' => $valor->C_Cuenta,
        '$and' => [
          [
            '$or' => [
              ['TELEFONO' => $valor->C_Telefono],
              ['CELULAR' => $valor->C_Telefono],
            ]
          ]
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['cuentas_telefonos_validas'] = $cont;


  return $telefonos;
}

//-------------------------------------------NUEVO-----------------------------------------------------------------------------------------------------------------------------



function filterResportesSourcesMuniUbicacion2($con, $fechainicio, $fechafin, $flag = true)
{
  if ($flag) {
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'reportes_sgo_chatbot',
      'pipeline' => [
        [
          '$match' => [
            'FECHA_REPORTE' => [
              '$gte' => $fechainicio,
              '$lt' => $fechafin
            ],
            'TELEFONO' => ['$exists' => true],
            'NOMBREUSUARIO' => ['$exists' => true]
          ]
        ], [
          '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'NIU',
            'foreignField' => 'NIU',
            'as' => 'usuario'
          ]
        ], [
          '$unwind' => [
            'path' => '$usuario'
          ]
        ], [
          '$project' => [
            'NIU' => '$NIU',
            'MUNICIPIO' => '$usuario.MUNICIPIO',
            'UBICACION' => '$usuario.UBICACION',
            'SEGMENTO' => '$usuario.SEGMENTO',
            'CLASE_SERVICIO' => '$usuario.CLASE_SERVICIO'
          ]
        ]
      ],
      'cursor' => new stdClass,
    ]);
  } else {
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'reportes_sgo_chatbot',
      'pipeline' => [
        [
          '$match' => [
            'TELEFONO' => ['$exists' => true],
            'NOMBREUSUARIO' => ['$exists' => true],
            //'SOURCE' => ['$exists' => true]
          ]
        ], [
          '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'NIU',
            'foreignField' => 'NIU',
            'as' => 'usuario'
          ]
        ], [
          '$unwind' => [
            'path' => '$usuario'
          ]
        ], [
          '$project' => [
            'NIU' => '$NIU',
            'MUNICIPIO' => '$usuario.MUNICIPIO',
            'UBICACION' => '$usuario.UBICACION',
            'SEGMENTO' => '$usuario.SEGMENTO',
            'CLASE_SERVICIO' => '$usuario.CLASE_SERVICIO',
          ]
        ]
      ],
      'cursor' => new stdClass,
    ]);
  }

  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response; //1818 - 1786
}

function filterConsultasSourcesMuniUbicacion($con, $fechainicio, $fechafin, $flag = true)
{

  if ($flag) {
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'log_resultados_usuarios',
      'pipeline' => [
        [
          '$match' => [
            'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
            '$or' => [
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
              ],
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
              ],
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
              ],
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
              ]
            ]
          ]
        ], [
          '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'NIU',
            'foreignField' => 'NIU',
            'as' => 'usuario'
          ]
        ],
        [
          '$project' => [
            'niu' => '$NIU',
            'FECHA_CONSULTA' => '$FECHA_RESULTADO',
            'usuario' => '$usuario'
          ]
        ],
        [
          '$match' => [
            'usuario.MUNICIPIO' => ['$exists' => True],
            'usuario.UBICACION' => ['$exists' => True],
            'usuario.SEGMENTO' => ['$exists' => True],
          ]
        ]
      ],
      'cursor' => new stdClass,
    ]);
  } else {
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'log_resultados_usuarios',
      'pipeline' => [
        [
          '$match' => [
            '$or' => [
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
              ],
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
              ],
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
              ],
              [
                'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
              ]
            ]
          ]
        ], [
          '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'NIU',
            'foreignField' => 'NIU',
            'as' => 'usuario'
          ]
        ],
        [
          '$project' => [
            'niu' => '$NIU',
            'FECHA_CONSULTA' => '$FECHA_RESULTADO',
            'usuario' => '$usuario'
          ]
        ],
        [
          '$match' => [
            'usuario.MUNICIPIO' => ['$exists' => True],
            'usuario.UBICACION' => ['$exists' => True],
            'usuario.SEGMENTO' => ['$exists' => True],
          ]
        ]
      ],
      'cursor' => new stdClass,
    ]);
  }

  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  foreach ($response as $clave => $valor) {
    if (count($valor->usuario) > 0) { //la 29 no existe en usuarios
      $valor->MUNICIPIO = $valor->usuario[0]->MUNICIPIO;
      $valor->UBICACION = $valor->usuario[0]->UBICACION;
      $valor->SEGMENTO = $valor->usuario[0]->SEGMENTO;
      $valor->CLASE_SERVICIO = $valor->usuario[0]->CLASE_SERVICIO;
    }
  }
  return $response; //6934
}

function filterResportesSourcesMuniUbicacionTelegramFaltaEnergia($con, $fechainicio, $fechafin, $flag = true)
{

  /*if ($flag) {
    $filter = [
      'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      'TELEFONO' => ['$exists' => true],
      'NOMBREUSUARIO' => ['$exists' => true],
    ];
  } else {
    $filter = [
      'SOURCE' => ['$exists' => true],
      'TELEFONO' => ['$exists' => true],
      'NOMBREUSUARIO' => ['$exists' => true],
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME . ".reportes_sgo_chatbot", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      $filter = [
        'NIU' => $value->NIU,
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_NAME . ".usuarios", $query);
      $resultado = $result->toArray();
      if (count($resultado) > 0) {
        $resultado[0]->SOURCE = $value->SOURCE;
        $resultado[0]->FECHA_REPORTE = $value->FECHA_REPORTE;
      }
      array_push($response, $resultado);
    }
  }
  return $response; //187
*/
  $anio = date('Y');
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          //'FECHA_REPORTE' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'FECHA_REPORTE' => new MongoDB\BSON\Regex($anio, 'i'),
          'TELEFONO' => ['$exists' => true],
          'NOMBREUSUARIO' => ['$exists' => true]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuario'
        ]
      ], [
        '$project' => [
          'NIU' => '$NIU',
          'MUNICIPIO' => '$usuario.MUNICIPIO',
          'UBICACION' => '$usuario.UBICACION',
          'FECHA_REPORTE' => '$FECHA_REPORTE',
          'SEGMENTO' => '$usuario.SEGMENTO',
          'SOURCE' => '$SOURCE',

        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response; //184
}

function filterConsultasSourcesMuniUbicacionFaltaEnergiaMeses($con, $fechainicio, $fechafin, $flag = true)
{
  $anio = date('Y');
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => new MongoDB\BSON\Regex($anio, 'i'),
          '$or' => [
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Programada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Suspension Efectiva', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Sin Indisponibilidad Reportada', 'i')
            ],
            [
              'TIPO_INDISPONIBILIDAD' => new MongoDB\BSON\Regex('Indisponibilidad a nivel de Nodo', 'i')
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'niu' => '$NIU',
          'FECHA_CONSULTA' => '$FECHA_RESULTADO',
          'usuario' => '$usuario'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}


function filterResportesMunicipiosUbicacion($con, $fechainicio, $fechafin)
{

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_REPORTE' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          'TELEFONO' => ['$exists' => true],
          'NOMBREUSUARIO' => ['$exists' => true]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuario'
        ]
      ], [
        '$project' => [
          'NIU' => '$NIU',
          'MUNICIPIO' => '$usuario.MUNICIPIO',
          'UBICACION' => '$usuario.UBICACION',
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response; //238
}


function filterResportesCopiaFacturaMuniUbicacion($con, $fechainicio, $fechafin, $flag = true)
{
  if ($flag) {
    $filter = [
      'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
      '$and' => [
        ['NIU' => ['$exists' => true]],
        ['NIU' => ['$ne' => '']],
        ['SOURCE' => ['$exists' => true]],
        ['SOURCE' => ['$ne' => '']]
      ]
    ];
  } else {
    $filter = [
      '$and' => [
        ['NIU' => ['$exists' => true]],
        ['NIU' => ['$ne' => '']],
        ['SOURCE' => ['$exists' => true]],
        ['SOURCE' => ['$ne' => '']]
      ]
    ];
  }
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      if (isset($value->SOURCE) && isset($value->NIU)) {
        if ((strcmp(strtolower($value->MENU), strtolower('fCopia factura')) == 0 || strcmp(strtolower($value->MENU), strtolower('Copia factura')) == 0) && $value->NIU != '' && $value->SOURCE != '') {
          $filter = [
            'NIU' => strval($value->NIU),
          ];
          $query = new MongoDB\Driver\Query($filter);
          $result = $con->executeQuery(DB_DIFUSION_CHEC . ".usuarios", $query);
          $resultado = $result->toArray();
          if (count($resultado) > 0) {
            $resultado[0]->SOURCE = $value->SOURCE;
            $resultado[0]->FECHA_RESULTADO = $value->FECHA_RESULTADO;
            $resultado[0]->MENU = $value->MENU;
            array_push($response, $resultado);
          }
        }
      }
    }
  }
  return $response; //716
}

function filterResportesCopiaFacturaMuniUbicacion3($con, $municipioUser, $ubicacionUser, $flag)
{

  $match = [];
  if ($flag == 'municipio') {
    $match = [
      'MUNICIPIO' => new MongoDB\BSON\Regex($municipioUser, 'i'),
    ];
  } else if ($flag == 'ubicacion') {
    $match = [
      'UBICACION' => new MongoDB\BSON\Regex($ubicacionUser, 'i'),
    ];
  } else if ($flag == 'municipioUbicacion') {
    $match = [
      'MUNICIPIO' => new MongoDB\BSON\Regex($municipioUser, 'i'),
      'UBICACION' => new MongoDB\BSON\Regex($ubicacionUser, 'i'),
    ];
  }

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'MENU' => 'fCopia factura',
          'NIU' => ['$ne' => ''],
          'SOURDE' => ['$ne' => '']
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'FECHA_RESULTADO' => '$FECHA_RESULTADO',
          'SOURCE' => '$SOURCE',
          'NIU' => '$NIU',
          'MUNICIPIO' => '$usuario.MUNICIPIO',
          'UBICACION' => '$usuario.UBICACION',
          'MENU' => '$MENU',
          'SEGMENTO' => '$usuario.SEGMENTO',
        ]
      ],
      [
        '$match' => $match
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();


  return $response; //213

}

function filterResportesCopiaFacturaMuniUbicacion2($con, $fechainicio, $fechafin, $flag = true)
{

  /*[
    [
        '$match'=> [
            'FECHA_RESULTADO'=> [
                '$gte'=> '2020-07-27',
                '$lt'=> '2020-07-28'
            ],
            'NIU'=> [
                '$exists'=> True,
                '$ne'=> ''
            ]
        ]
    ], [
        '$lookup'=> [
            'from'=> 'usuarios',
            'localField'=> 'NIU',
            'foreignField'=> 'NIU',
            'as'=> 'usuario'
        ]
    ], [
        '$unwind'=> [
            'path'=> '$usuario'
        ]
    ], [
        '$project'=> [
            'NIU': '$NIU',
            'MUNICIPIO': '$usuario.MUNICIPIO',
            'UBICACION': '$usuario.UBICACION',
            'SEGMENTO': '$usuario.SEGMENTO',
            'CLASE_SERVICIO': '$usuario.CLASE_SERVICIO'
        ]
    ]
]*/

  if ($flag) {
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'log_menu_usuarios',
      'pipeline' => [
        [
          '$match' => [
            'FECHA_RESULTADO' => [
              '$gte' => $fechainicio,
              '$lt' => $fechafin
            ],
            'NIU' => [
              '$exists' => True,
              '$ne' => ''
            ]
          ]
        ], [
          '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'NIU',
            'foreignField' => 'NIU',
            'as' => 'usuario'
          ]
        ], [
          '$unwind' => [
            'path' => '$usuario'
          ]
        ], [
          '$project' => [
            'NIU' => '$NIU',
            'MUNICIPIO' => '$usuario.MUNICIPIO',
            'UBICACION' => '$usuario.UBICACION',
            'SEGMENTO' => '$usuario.SEGMENTO',
            'CLASE_SERVICIO' => '$usuario.CLASE_SERVICIO'
          ]
        ]
      ],
      'cursor' => new stdClass,
    ]);
  } else {
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'log_menu_usuarios',
      'pipeline' => [
        [
          '$match' => [
            'NIU' => [
              '$exists' => True,
              '$ne' => ''
            ],
            'SOURCE' => [
              '$exists' => True,
              '$ne' => ''
            ]
          ]
        ], [
          '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'NIU',
            'foreignField' => 'NIU',
            'as' => 'usuario'
          ]
        ], [
          '$unwind' => [
            'path' => '$usuario'
          ]
        ], [
          '$project' => [
            'NIU' => '$NIU',
            'MUNICIPIO' => '$usuario.MUNICIPIO',
            'UBICACION' => '$usuario.UBICACION',
            'SEGMENTO' => '$usuario.SEGMENTO',
            'CLASE_SERVICIO' => '$usuario.CLASE_SERVICIO',
            'SOURCE' => '$usuario.SOURCE'
          ]
        ]
      ],
      'cursor' => new stdClass,
    ]);
  }

  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();
  return $response; //764
}


function filterResportesCopiaFactura($con, $fechainicio, $fechafin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'NIU' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          'MENU' => 'fCopia factura'
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ],
      [
        '$project' => [
          'SOURCE' => '$SOURCE',
          'NIU' => '$NIU',
          'DATA' => '$usuario'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();


  return $response;
}

function filterResportesCopiaFacturaAnual($con, $fechainicio, $fechafin)
{


  $anio = date('Y');
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => new MongoDB\BSON\Regex($anio, 'i'),
          'NIU' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          'MENU' => 'fCopia factura'
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario'
        ]
      ], [
        '$project' => [
          'FECHA_RESULTADO' => '$FECHA_RESULTADO',
          'SOURCE' => '$SOURCE',
          'DATA' => '$usuario'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $response = $result->toArray();
  return $response;
}

function filterResportesCopiaFacturaMunicipio($con, $fechainicio, $fechafin, $municipio)
{

  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    '$and' => [
      ['NIU' => ['$exists' => true]],
      ['NIU' => ['$ne' => '']],
      ['SOURCE' => ['$exists' => true]],
      ['SOURCE' => ['$ne' => '']]
    ]
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      if (isset($value->SOURCE) && isset($value->NIU)) {
        if ((strcmp(strtolower($value->MENU), strtolower('fCopia factura')) == 0 || strcmp(strtolower($value->MENU), strtolower('Copia factura')) == 0) && $value->NIU != '' && $value->SOURCE != '') {
          $filter = [
            '$and' => [
              ['NIU' => $value->NIU],
              ['MUNICIPIO' =>  new MongoDB\BSON\Regex($municipio, 'i')],
            ]
          ];
          $query = new MongoDB\Driver\Query($filter);
          $result = $con->executeQuery(DB_DIFUSION_CHEC . ".usuarios", $query);
          $resultado = $result->toArray();
          if (count($resultado) > 0) {
            $resultado[0]->SOURCE = $value->SOURCE;
            $resultado[0]->FECHA_RESULTADO = $value->FECHA_RESULTADO;
            $resultado[0]->MENU = $value->MENU;
            array_push($response, $resultado);
          }
        }
      }
    }
  }
  return $response; //259 - 1:05 - 2:51 - 696
}

function filterResportesCopiaFacturaUbicacion($con, $fechainicio, $fechafin, $ubicacion)
{
  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    '$and' => [
      ['NIU' => ['$exists' => true]],
      ['NIU' => ['$ne' => '']],
      ['SOURCE' => ['$exists' => true]],
      ['SOURCE' => ['$ne' => '']]
    ]
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      if (isset($value->SOURCE) && isset($value->NIU)) {
        if ((strcmp(strtolower($value->MENU), strtolower('fCopia factura')) == 0 || strcmp(strtolower($value->MENU), strtolower('Copia factura')) == 0) && $value->NIU != '' && $value->SOURCE != '') {
          $filter = [
            '$and' => [
              ['NIU' => $value->NIU],
              ['UBICACION' =>  new MongoDB\BSON\Regex($ubicacion, 'i')],
            ]
          ];
          $query = new MongoDB\Driver\Query($filter);
          $result = $con->executeQuery(DB_DIFUSION_CHEC . ".usuarios", $query);
          $resultado = $result->toArray();
          if (count($resultado) > 0) {
            $resultado[0]->SOURCE = $value->SOURCE;
            $resultado[0]->FECHA_RESULTADO = $value->FECHA_RESULTADO;
            $resultado[0]->MENU = $value->MENU;
            array_push($response, $resultado);
          }
        }
      }
    }
  }
  return $response; //259 - 1:05 - 2:51 - 696
}

function filterResportesCopiaFacturaUbicacionMunicipio($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{

  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    '$and' => [
      ['NIU' => ['$exists' => true]],
      ['NIU' => ['$ne' => '']],
      ['SOURCE' => ['$exists' => true]],
      ['SOURCE' => ['$ne' => '']]
    ]
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  $response = array();
  if (count($respuesta) > 0) {
    foreach ($respuesta as $key => $value) {
      if (isset($value->SOURCE) && isset($value->NIU)) {
        if ((strcmp(strtolower($value->MENU), strtolower('fCopia factura')) == 0 || strcmp(strtolower($value->MENU), strtolower('Copia factura')) == 0) && $value->NIU != '' && $value->SOURCE != '') {
          $filter = [
            '$and' => [
              ['NIU' => $value->NIU],
              ['MUNICIPIO' =>  new MongoDB\BSON\Regex($municipio, 'i')],
              ['UBICACION' =>  new MongoDB\BSON\Regex($ubicacion, 'i')],
            ]
          ];
          $query = new MongoDB\Driver\Query($filter);
          $result = $con->executeQuery(DB_DIFUSION_CHEC . ".usuarios", $query);
          $resultado = $result->toArray();
          if (count($resultado) > 0) {
            $resultado[0]->SOURCE = $value->SOURCE;
            $resultado[0]->FECHA_RESULTADO = $value->FECHA_RESULTADO;
            $resultado[0]->MENU = $value->MENU;
            array_push($response, $resultado);
          }
        }
      }
    }
  }
  return $response; //259 - 1:05 - 2:51 - 696
}

/*function filterConsultaMunicipio2($con, $municipio)
{
  $filter = [
    'MUNICIPIO' => new MongoDB\BSON\Regex($municipio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . ".municipios_chec", $query);
  return $result->toArray();
}*/

/*function getRules_Difusion($con)
{
  $filter = [];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . ".reglas_difusion", $query);
  $respuesta = $result->toArray();
  $reglas = array();
  foreach ($respuesta as $clave => $valor) {
    array_push($reglas, $valor->REGLA);
  }

  return $reglas;
}*/

function getConsultas_LucyHora2($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_resultados_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => [
            '$gte' => $fechaInicio,
            '$lte' => $fechaFin,
          ],
        ],
      ],
      [
        '$project' => [
          'hour' => [
            '$dateToString' => [
              'format' => '%H',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_RESULTADO',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$group' => [
          '_id' => '$hour',
          'cantidad' => [
            '$sum' => 1,
          ],
        ],
      ],
      [
        '$sort' => ["_id" => 1],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}


/*function getConversacionLucy($con, $niu, $fechaInicio, $fechaFin)
{

  $chat = array();
  $cont = 0;
  $filter = [
    'mensaje.text' => $niu
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
  $resultado = $result->toArray();
  if (count($resultado) == 1) {
    foreach ($resultado as $clave => $valor) {
      $filter = [
        'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
        'mensaje.codUser' => $valor->mensaje[0]->codUser
      ];
      $options = [
        'sort' => [
          'mensaje.fecha' => 1,
        ],
      ];

      $query = new MongoDB\Driver\Query($filter, $options);
      $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
      $chat[$cont] = $result->toArray();
    }
  } else if (count($resultado) > 1) {
    $sesisiones = sesiones($resultado);
    $valorSesion = array_values(array_unique($sesisiones));
    if (count(array_values(array_unique($sesisiones))) > 1) {
      foreach ($valorSesion as $sesion) {
        $filter = [
          'mensaje.fecha' => ['$gte' => (int) $fechaInicio, '$lt' => (int)$fechaFin],
          'mensaje.codUser' => $sesion
        ];
        $options = [
          'sort' => [
            'mensaje.fecha' => 1,
          ],
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
        $prueba = $result->toArray();
        // if (count($prueba) > 1) {
        $chat[$cont] = $prueba;
        $cont += 1;
        //}
      }
    } else if (count(array_values(array_unique($sesisiones))) == 1) {
      $valorSesion = array_values(array_unique($sesisiones));
      $chat['chats_no_filtrados'] = filterAllChatsByUser($con, $valorSesion);
      foreach ($valorSesion as $clave => $valor) {
        $filter = [
          'mensaje.fecha' => ['$gte' => (int)$fechaInicio, '$lt' => (int)$fechaFin],
          'mensaje.codUser' => $valor
        ];
        $options = [
          'sort' => [
            'mensaje.fecha' => 1,
          ],
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
        $chat[$cont] = $result->toArray();
      }
    }
  }

  return $chat;
}*/

/*function filterAllChatsByUser($con, $valorSesion)
{

  $chatsSessions = array();
  foreach ($valorSesion as $clave => $valor) {
    $filter = [
      'mensaje.codUser' => $valor
    ];
    $options = [
      'sort' => [
        'mensaje.fecha' => 1,
      ],
    ];

    $query = new MongoDB\Driver\Query($filter, $options);
    $result = $con->executeQuery(DB_NAME_LOCAL . '.chats', $query);
    array_push($chatsSessions, $result->toArray());
  }
  return $chatsSessions;
}*/

function sesiones($resultado)
{
  $obtenerSesiones = array();
  $cont = 0;
  foreach ($resultado as $clave => $valor) {
    if (isset($valor->mensaje[0]->codUser)) {
      $obtenerSesiones[$cont] = $valor->mensaje[0]->codUser;
      $cont += 1;
    }
  }

  return $obtenerSesiones;
}

function sesionesFallback($resultado)
{
  $obtenerSesiones = array();
  $cont = 0;
  foreach ($resultado as $clave => $valor) {
    if (isset($valor->SESSIONID)) {
      $obtenerSesiones[$cont] = $valor->SESSIONID;
      $cont += 1;
    }
  }

  return $obtenerSesiones;
}

function filterFallbacks($con, $fechaInicio, $fechaFin)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'fallback',
    'pipeline' => [
      [
        '$match' => [
          'FECHA' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultado = current($result->toArray());
  return $resultado;
}

function filterAccesosMenuMes($con, $fechaInicio, $fechaFin)
{
  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
  ];
  /*$anio = date('Y');
  $filter = [
    'FECHA_RESULTADO' => new MongoDB\BSON\Regex($anio, 'i'),
  ];*/
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_HEROKU_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  return $respuesta;
}

function filterAccesosMenuAnio($con)
{
  $anio = date('Y');

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => new MongoDB\BSON\Regex($anio, 'i'),
          '$or' => [
            [
              'MENU' => 'Falta de Energia'
            ], [
              'MENU' => 'Pqr'
            ], [
              'MENU' => 'Puntos de Atencion'
            ], [
              'MENU' => 'Vacantes'
            ], [
              'MENU' => 'Pago factura'
            ], [
              'MENU' => 'Fraudes'
            ], [
              'MENU' => 'Copia factura'
            ], [
              'MENU' => 'Asesor remoto'
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultado = $result->toArray();
  return $resultado;
}

function totalAccesosMenuMes($con)
{
  $filter = [
    //'FECHA_RESULTADO' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
    'MENU' => ['$ne' => ''],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".log_menu_usuarios", $query);
  $respuesta = $result->toArray();
  return $respuesta;
}

function getConversacion($conversaciones)
{
}


function getUsuarios($con, $conChec, $anio, $mes)
{
  $fechaInicio = "$anio-$mes-01";
  $dia = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
  $fechaFin = "$anio-$mes-$dia";
  $resultado = [];
  $resultados = [];
  $niusDuplicados = [];
  $nius = [];

  //filtrar los nius de los usuarios que han consultado por copia_factura, cupon_pago(actual y anterior)
  $filter = [
    'FECHA_RESULTADO' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
    'NIU' => ['$ne' => ''],
    '$and' => [
      ['$or' => [
        ['MENU' => new MongoDB\BSON\Regex('fCupon actual', 'i')],
        ['MENU' => new MongoDB\BSON\Regex('fCupon anterior', 'i')],
        ['MENU' => new MongoDB\BSON\Regex('fCopia factura', 'i')],
      ]],
    ]
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.log_menu_usuarios', $query);
  $resultados = $result->toArray();
  foreach ($resultados as $clave => $valor) {
    array_push($niusDuplicados, $valor->NIU);
  }
  $nius = array_unique($niusDuplicados);

  //obtener los usuarios asociados a esos nius obtenidos
  $filter = [];
  $usuarios = [];
  foreach ($nius as $valor) {
    $filter = [
      'NIU' => (string) $valor
    ];
    $query = new MongoDB\Driver\Query($filter);
    $result = $conChec->executeQuery(DB_HEROKU_CHEC . '.usuarios', $query);
    array_push($usuarios, $result->toArray());
  }
  $resultado[0] = $usuarios;


  //filtrar los reportes que han hecho los usuarios y obtener los niu
  $niusDuplicados2 = [];
  $nius2 = [];
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          'NIU' => ['$exists' => true],
          'FECHA_REPORTE' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
          'ESTADO' => 'Enviado',
        ],
      ],
      [
        '$project' => [
          'niu' => '$NIU',
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $conChec->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultados2 = $result->toArray();

  foreach ($resultados2 as $clave => $valor) {
    array_push($niusDuplicados2, $valor->niu);
  }

  $nius2 = array_unique($niusDuplicados2);


  //buscar los usuarios asociados a estos numeros de cuenta seleccionados anteriormente
  $filter = [];
  $usuarios = [];
  foreach ($nius2 as $valor) {

    $filter = [
      'NIU' => (string) $valor
    ];
    $query = new MongoDB\Driver\Query($filter);
    $result = $conChec->executeQuery(DB_HEROKU_CHEC . '.usuarios', $query);
    array_push($usuarios, $result->toArray());
  }

  $resultado[1] = $usuarios;

  return $resultado;
}

function getUsuarios2($con, $anio, $mes)
{
  $fechaInicio = "$anio-$mes-01";
  $dia = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
  $fechaFin = "$anio-$mes-$dia";
  $resultado = [];
  $resultados = [];
  $niusDuplicados = [];
  $nius = [];

  //filtrar los nius de los usuarios que han consultado por copia_factura, cupon_pago(actual y anterior)
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => [
            '$gte' => $fechaInicio,
            '$lt' => $fechaFin
          ],
          'NIU' => [
            '$ne' => ''
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'NOMBRE' => '$usuarios.NOMBRE',
          'DIRECCION' => '$usuarios.DIRECCION',
          'MUNICIPIO' => '$usuarios.MUNICIPIO',
          'ESTRATO' => '$usuarios.ESTRATO',
          'NIU' => '$usuarios.NIU'
        ]
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $result = $result->toArray();
  $resultado[0] = $result;


  //filtrar los reportes que han hecho los usuarios y obtener los niu
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'reportes_sgo_chatbot',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_REPORTE' => [
            '$gte' => $fechaInicio,
            '$lt' => $fechaFin
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'users'
        ]
      ], [
        '$unwind' => [
          'path' => '$users'
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'NOMBRE' => '$users.NOMBRE',
          'DIRECCION' => '$users.DIRECCION',
          'MUNICIPIO' => '$users.MUNICIPIO',
          'ESTRATO' => '$users.ESTRATO',
          'NIU' => '$usuausersrios.NIU'
        ]
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $result = $result->toArray();
  $resultado[1] = $result;

  $total = array();

  foreach ($resultado[0] as $clave => $valor) {
    array_push($total, $valor);
  }

  foreach ($resultado[1] as $clave => $valor) {
    array_push($total, $valor);
  }

  return $total;
}

function getUsuarios_inscritos($con, $month, $year)
{
  $filter = [];
  $usuarioInscrito = [];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.universo_chec_difusion', $query);
  $resultado = $result->toArray();

  if (count($resultado) > 0) {
    $filter = [];
    foreach ($resultado as $clave => $valor) {
      $filter = [
        'NIU' => $valor->CUENTA_UCD,
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $resultado = $result->toArray();

      if (count($resultado) > 0) {
        array_push($usuarioInscrito, $resultado);
      }
    }
  }

  return $usuarioInscrito;
}

function getUsuarios_inscritos2($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'universo_chec_difusion',
    'pipeline' => [
      [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'CUENTA_UCD',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'NOMBRE' => '$usuarios.NOMBRE',
          'DIRECCION' => '$usuarios.DIRECCION',
          'MUNICIPIO' => '$usuarios.MUNICIPIO',
          'ESTRATO' => '$usuarios.ESTRATO',
          'NIU' => '$usuarios.NIU'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}


function getcalnegativas($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'calificacion_usuarios',
    'pipeline' => [
      [
        '$project' => [
          '_id' => 0,
          'calificacion' => '$CALIFICACION',
          'fecha' => '$FECHA',
          'voc' => '$VOC',
          'source' => '$SOURCE',
          'niu' => '$NIU',
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA',
                ],
              ],
            ],
          ],
          'year' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$match' => [
          'month' => $month,
          'year' => $year,
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}


function getConsultas_Usuarios($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_sender_niu',
    'pipeline' => [
      [
        '$group' => [
          '_id' => '$IDCONVERSATION',
          'suma' => [
            '$sum' => 1,
          ],
          'niu' => [
            '$first' => '$NIU',
          ],
          'fecha' => [
            '$first' => '$FECHA',
          ],
          'source' => [
            '$first' => '$SOURCE',
          ],
        ],
      ],
      [
        '$project' => [
          'fecha' => '$fecha',
          'source' => '$source',
          'idconversation' => '$_id',
          'niu' => '$niu',
          'total' => '$suma',
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$fecha',
                ],
              ],
            ],
          ],
          'year' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$fecha',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'niu',
          'foreignField' => 'NIU',
          'as' => 'usuario',
        ],
      ],
      [
        '$match' => [
          'month' => $month,
          'year' => $year,
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}


function getUsuarios_Segmentos($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_sender_niu',
    'pipeline' => [
      [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'NIU',
          'foreignField' => 'NIU',
          'as' => 'usuario',
        ],
      ],
      [
        '$match' => [
          'usuario.SEGMENTO' => [
            '$exists' => true,
          ],
        ],
      ],
      [
        '$project' => [
          'source' => '$SOURCE',
          'niu' => '$NIU',
          'segmento' => '$usuario.SEGMENTO',
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA',
                ],
              ],
            ],
          ],
          'year' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$match' => [
          'month' => $month,
          'year' => $year,
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function getAcuseRecibo_Difusion($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$project' => [
          'niu' => '$NIU',
          'apertura' => '$APERTURA',
          'telefono' => '$TELEFONO',
          'estadoApertura' => '$ESTADO_APERTURA',
          'estadoCierre' => '$ESTADO_CIERRE',
          'fechaApertura' => '$FECHA_ENTREGA_APERTURA',
          'fechaCierre' => '$FECHA_ENTREGA_CIERRE',
          'month2' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA_APERTURA',
                ],
              ],
            ],
          ],
          'year2' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA_APERTURA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$match' => [
          'month2' => $month,
          'year2' => $year,
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function getAcuseRecibo_DifusionSegmntos($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$project' => [
          'niu' => '$NIU',
          'apertura' => '$APERTURA',
          'telefono' => '$TELEFONO',
          'estadoApertura' => '$ESTADO_APERTURA',
          'estadoCierre' => '$ESTADO_CIERRE',
          'fechaApertura' => '$FECHA_ENTREGA_APERTURA',
          'fechaCierre' => '$FECHA_ENTREGA_CIERRE',
          'month2' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA_APERTURA',
                ],
              ],
            ],
          ],
          'year2' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA_APERTURA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$match' => [
          'month2' => $month,
          'year2' => $year,
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta = $result->toArray();

  $filter = [];
  $usuariosSegmentos = [];
  if (count($respuesta) > 0) {
    $cont = 0;
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => strval($valor->niu),
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_HEROKU_CHEC . '.usuarios', $query);
      $resultado = $result->toArray();
      $usuariosSegmentos[$cont]['niu'] = $valor->niu;
      $usuariosSegmentos[$cont]['telefono'] = $valor->telefono;
      $usuariosSegmentos[$cont]['estadoApertura'] = $valor->estadoApertura;
      $usuariosSegmentos[$cont]['fechaApertura'] = $valor->fechaApertura;
      $usuariosSegmentos[$cont]['estadoCierre'] = $valor->estadoCierre;
      $usuariosSegmentos[$cont]['fechaCierre'] = $valor->fechaCierre;
      $usuariosSegmentos[$cont]['segmento'] = $resultado[0]->SEGMENTO;

      $cont = $cont + 1;
    }
  }

  return $usuariosSegmentos;
}

function getAcuseRecibo_DifusionSegmntos2($con, $month, $year)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$project' => [
          'niu' => '$NIU',
          'apertura' => '$APERTURA',
          'telefono' => '$TELEFONO',
          'estadoApertura' => '$ESTADO_APERTURA',
          'estadoCierre' => '$ESTADO_CIERRE',
          'fechaApertura' => '$FECHA_ENTREGA_APERTURA',
          'fechaCierre' => '$FECHA_ENTREGA_CIERRE',
          'month2' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA_APERTURA',
                ],
              ],
            ],
          ],
          'year2' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA_APERTURA',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$match' => [
          'month2' => $month,
          'year2' => $year,
        ],
      ],
      [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'niu',
          'foreignField' => 'NIU',
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'TIPO_MENSAJE' => 'INTERRUPCIÓN NO PROGRAMADA',
          'NIU' => '$usuarios.NIU',
          'TELEFONO' => '$usuarios.TELEFONO',
          'ESTADO_APERTURA' => '$estadoApertura',
          'FECHA_APERTURA' => '$fechaApertura',
          'ESTADO_CIERRE' => '$estadoCierre',
          'FECHA_CIERRE' => '$fechaCierre',
          'SEGMENTO' => '$usuarios.SEGMENTO'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta = $result->toArray(); //60754

  return $respuesta;
}

function getAcuseRecibo_DifusionPromocion($con, $month, $year)
{
  //    'FECHA_ENTREGA' => ['$gte' => $fechainicio, '$lte' => $fechafin],

  /*
      'FECHA_PROMOCION_PROGRAMADAS' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
    'ESTADO_PROMOCION_PROGRAMADAS' => ['$exists' => true],
    'NIU' => $niu,
  */

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$project' => [
          'niu' => '$NIU',
          'telefono' => '$TELEFONO',
          'estadoPromocionProgramadas' => '$ESTADO_PROMOCION_PROGRAMADAS',
          'fechaPromocionProgramadas' => '$FECHA_PROMOCION_PROGRAMADAS',
          'fechaPromocion' => '$FECHA_ENTREGA',
          'estadoPromocion' => '$ESTADO_ENVIO',
          'month' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA',
                ],
              ],
            ],
          ],
          'year' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_ENTREGA',
                ],
              ],
            ],
          ],
          'month3' => [
            '$dateToString' => [
              'format' => '%m',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_PROMOCION_PROGRAMADAS',
                ],
              ],
            ],
          ],
          'year3' => [
            '$dateToString' => [
              'format' => '%Y',
              'date' => [
                '$dateFromString' => [
                  'dateString' => '$FECHA_PROMOCION_PROGRAMADAS',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '$match' => [
          '$or' => [
            [
              'month' => $month,
              'year' => $year,
            ],
            [
              'month3' => $month,
              'year3' => $year,
            ],
          ],
        ],
      ],
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $respuesta = $result->toArray();
  return $respuesta;
}

function getAcuseRecibo_DifusionPromocion2($con, $month, $year)
{
  //    'FECHA_ENTREGA' => ['$gte' => $fechainicio, '$lte' => $fechafin],

  /*
      'FECHA_PROMOCION_PROGRAMADAS' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
    'ESTADO_PROMOCION_PROGRAMADAS' => ['$exists' => true],
    'NIU' => $niu,
  */

  $fechaInicio = "$year-$month-01";
  $dia = cal_days_in_month(CAL_GREGORIAN, $month, $year);
  $fechaFin = "$year-$month-$dia";
  $resultado = array();

  //promocion lucy
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_ENTREGA' => ['$gte' => $fechaInicio, '$lte' => $fechaFin]
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'TIPO_PROMOCION' => 'Lucy',
          'NIU' => '$NIU',
          'TELEFONO' => '$TELEFONO',
          'ESTADO_PROMOCION' => '$ESTADO_ENVIO',
          'FECHA_PROMOCION' => '$FECHA_ENTREGA'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta = $result->toArray();
  $resultado[0] = $respuesta;

  //promocion suspensiones programadas
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_acuse_recibo_dinp',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_PROMOCION_PROGRAMADAS' => ['$gte' => $fechaInicio, '$lt' => $fechaFin],
          'ESTADO_PROMOCION_PROGRAMADAS' => ['$exists' => true],
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'TIPO_PROMOCION' => 'INCRIPCIÓN A SUSPENSIONES PROGRAMADAS',
          'NIU' => '$NIU',
          'TELEFONO' => '$TELEFONO',
          'ESTADO_PROMOCION' => '$ESTADO_PROMOCION_PROGRAMADAS',
          'FECHA_PROMOCION' => '$FECHA_PROMOCION_PROGRAMADAS'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC_SGCB, $Command);
  $respuesta = $result->toArray();
  $resultado[1] = $respuesta;

  $total = array();

  foreach ($resultado[0] as $clave => $valor) {
    array_push($total, $valor);
  }

  foreach ($resultado[1] as $clave => $valor) {
    array_push($total, $valor);
  }

  return $total;
}


function getLlamadasCuentasValidas($con, $fechainicio, $fechafin)
{
  $cuentas = [];
  $cuentasValidas = [];
  $cuentasNoValidas = [];
  $cont = 0;
  //contar numero de registros en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $cuentas['cuentas_totales'] = current($result->toArray());

  if ($cuentas['cuentas_totales'] == false) {

    $cuentas['cuentas_totales'] = 0;
  }

  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  /*if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $cuentas['cuentas_validas'] = $cont;*/
  $cuentas['cuentas_validas'] = count($respuesta);

  return $cuentas;
}

function getLlamadasCuentasValidasXMunicipios($con, $fechainicio, $fechafin, $municipio)
{
  $cuentas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $cuentas['cuentas_totales'] = current($result->toArray());

  if ($cuentas['cuentas_totales'] == false) {
    $cuentas['cuentas_totales'] = 0;
  }

  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  /* if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $cuentas['cuentas_validas'] = $cont;*/
  $cuentas['cuentas_validas'] = count($respuesta);


  return $cuentas;
}


function getLlamadasCuentasValidasXUbicacion($con, $fechainicio, $fechafin, $ubicacion)
{
  $cuentas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $cuentas['cuentas_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i')
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $cuentas['cuentas_validas'] = $cont;


  return $cuentas;
}

function getLlamadasCuentasValidasXUbicacion2($con, $fechainicio, $fechafin, $ubicacion)
{
  $cuentas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $cuentas['cuentas_totales'] = current($result->toArray());

  if ($cuentas['cuentas_totales'] == false) {
    $cuentas['cuentas_totales'] = 0;
  }

  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => ['$regex' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i')]
        ]
      ], [
        '$project' => [
          'niu' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Cuenta', null
                ]
              ],
              'then' => '$C_Cuenta',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          'niu' => [
            '$exists' => True
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $cuentasFiltradas = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->niu)) {
        array_push($cuentasFiltradas, $valor->niu);
      }
    }



    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'NIU' => ['$in' => $cuentasFiltradas],
            'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i')
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $cuentas['cuentas_validas'] = current($result->toArray());
  } else {
    $cuentas['cuentas_validas'] = 0;
  }

  return $cuentas;
}


function getLlamadasCuentasValidasXUbicacionMunicipio($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $cuentas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $cuentas['cuentas_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i')
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $cuentas['cuentas_validas'] = $cont;

  return $cuentas;
}


function getLlamadasCuentasValidasXUbicacionMunicipio2($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $cuentas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $cuentas['cuentas_totales'] = current($result->toArray());

  if ($cuentas['cuentas_totales'] == false) {
    $cuentas['cuentas_totales'] = 0;
  }
  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => ['$regex' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i')],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
        ]
      ], [
        '$project' => [
          'niu' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Cuenta', null
                ]
              ],
              'then' => '$C_Cuenta',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          'niu' => [
            '$exists' => True
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $cuentasFiltradas = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->niu)) {
        array_push($cuentasFiltradas, $valor->niu);
      }
    }

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'NIU' => ['$in' => $cuentasFiltradas],
            'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i')
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $cuentas['cuentas_validas'] = current($result->toArray());
  } else {
    $cuentas['cuentas_validas'] = 0;
  }

  return $cuentas;
}

function getLlamadasTelefonosValidas($con, $fechainicio, $fechafin)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //filtrar telefonos validos de tipificacion - revisar celular
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    '$or' => [
      [
        'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
      ], [
        'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
      ]
    ]
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //$telefonos['cuentas_validas'] = count($respuesta);
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        '$or' => [
          ['TELEFONO' => $valor->C_Telefono],
          ['CELULAR' => $valor->C_Telefono],
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['telefonos_validas'] = $cont;


  return $telefonos; //2838
}

function getLlamadasTelefonosValidas2($con, $fechainicio, $fechafin)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ], [
              'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ]
          ]
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ], [
              'celular' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray(); //5703

  //separar telefonos y celulares encontrados anteriormente
  $telefonosFiltrados = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono) && $valor->telefono != '') {
        array_push($telefonosFiltrados, $valor->telefono);
      }
      if (isset($valor->celular) && $valor->celular != '') {
        array_push($telefonosFiltrados, $valor->celular);
      }
    }

    $tel = array_unique($telefonosFiltrados);

    $prueba = array(
      //0 => "3104152121", //2 veces en la base de datos
      //1 => "3204583714",
      //2 => "3137778764", 
      //3 => "3137778764",
      //4 => "3145634581",
      //5 => "3145634581",
      //6 => "3145634581",//tres veces en la base de datos
      0 => "3128276518", // 14 veces
      1 => "3128276518", // 14 veces
      2 => "3128276518", // 14 veces 

    );

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            /*'$or' => [
              ['CELULAR' => ['$in' => $prueba]],
              ['TELEFONO' => ['$in' => $prueba]],
            ],*/
            '$or' => [
              ['CELULAR' => ['$in' => $telefonosFiltrados]],
            ],
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['telefonos_validas'] = current($result->toArray()); //848
  }

  return $telefonos;
}

function getLlamadasTelefonosValidas3($con, $fechainicio, $fechafin)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'C_Telefono' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ], [
              'C_Celular' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ]
          ]
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => [
                '$cond' => [
                  'if' => [
                    '$ne' => [
                      '$C_Celular', null
                    ]
                  ],
                  'then' => '$C_Celular',
                  'else' => '$$REMOVE'
                ]
              ]
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'celular',
          'foreignField' => 'CELULAR',
          'as' => 'usuario'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuario'
        ]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_validas'] = current($result->toArray());


  return $telefonos;
}


function getLlamadasTelefonosValidasXMunicipios($con, $fechainicio, $fechafin, $municipio)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //filtrar telefonos validos de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    '$or' => [
      ['C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')],
      ['C_CELULAR' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')],
    ],
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray(); //1369
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        '$or' => [
          ['TELEFONO' => $valor->C_Telefono],
          ['CELULAR' => $valor->C_Telefono],
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['telefonos_validas'] = $cont;


  return $telefonos;
}

function getLlamadasTelefonosValidasXMunicipios2($con, $fechainicio, $fechafin, $municipio)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ], [
              'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ]
          ],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ], [
              'celular' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $telefonosFiltrados = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono)) {
        array_push($telefonosFiltrados, $valor->telefono);
      }
      if (isset($valor->celular)) {
        array_push($telefonosFiltrados, $valor->celular);
      }
    }

    array_unique($telefonosFiltrados);

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'CELULAR' => ['$in' => $telefonosFiltrados]
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['telefonos_validas'] = current($result->toArray());
  }

  return $telefonos;
}

function getLlamadasTelefonosValidasXMunicipios3($con, $fechainicio, $fechafin, $municipio)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());

  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'C_Telefono' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ], [
              'C_Celular' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ]
          ],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
        ]
      ], [
        '$project' => [
          '_id' => 0,
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => [
                '$cond' => [
                  'if' => [
                    '$ne' => [
                      '$C_Celular', null
                    ]
                  ],
                  'then' => '$C_Celular',
                  'else' => '$$REMOVE'
                ]
              ]
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'localField' => 'celular',
          'foreignField' => 'CELULAR',
          'as' => 'usuario'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuario'
        ]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_validas'] = current($result->toArray());


  return $telefonos;
}

function getLlamadasTelefonosValidasXUbicacion2($con, $fechainicio, $fechafin, $ubicacion)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => ['$regex' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i')],
          '$or' => [
            [
              'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ], [
              'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'let' => [
            'cuenta' => '$C_Cuenta',
            'ubi' => strtoupper($ubicacion)
          ],
          'pipeline' => [
            [
              '$match' => [
                '$expr' => [
                  '$and' => [
                    [
                      '$eq' => [
                        '$NIU', '$$cuenta'
                      ]
                    ], [
                      '$eq' => [
                        '$UBICACION', '$$ubi'
                      ]
                    ]
                  ]
                ]
              ]
            ], [
              '$project' => [
                '_id' => 0
              ]
            ]
          ],
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          'usuarios' => '$usuarios.NIU',
          'ubicacion' => '$usuarios.UBICACION',
          'tel' => '$usuarios.TELEFONO',
          'CEL' => '$usuarios.CELULAR'
        ]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_validas'] = current($result->toArray());

  return $telefonos;
}



function getLlamadasTelefonosValidasXUbicacion3($con, $fechainicio, $fechafin, $ubicacion)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ], [
              'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ]
          ]
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ], [
              'celular' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();
}

function getLlamadasTelefonosValidasXUbicacion($con, $fechainicio, $fechafin, $ubicacion)
{
  $llamadas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $llamadas['telefonos_totales'] = current($result->toArray());


  //filtrar llamadas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{6,10}$", 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las llamadas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
        '$and' => [
          [
            '$or' => [
              ['TELEFONO' => $valor->C_Telefono],
              ['CELULAR' => $valor->C_Telefono],
            ]
          ]
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $llamadas['telefonos_validas'] = $cont;


  return $llamadas;
}


function getLlamadasTelefonosValidasXUbicacionMunicipio($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{1,10}$", 'i'),
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
        '$and' => [
          [
            '$or' => [
              ['TELEFONO' => $valor->C_Telefono],
              ['CELULAR' => $valor->C_Telefono],
            ]
          ]
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['telefonos_validas'] = $cont;

  return $telefonos;
}

function getLlamadasTelefonosValidasXUbicacionMunicipio2($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ], [
              'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ]
          ],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ], [
              'celular' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $telefonosFiltrados = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono)) {
        array_push($telefonosFiltrados, $valor->telefono);
      }
      if (isset($valor->celular)) {
        array_push($telefonosFiltrados, $valor->celular);
      }
    }

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'CELULAR' => ['$in' => $telefonosFiltrados],
            'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['telefonos_validas'] = current($result->toArray());
  }

  return $telefonos;
}

function getLlamadasTelefonosValidasXUbicacionMunicipio3($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $telefonos = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_totales'] = current($result->toArray()); //4460



  //buscar telefonos y celulares en usuarios
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => ['$regex' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i')],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i'),
          '$or' => [
            [
              'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ], [
              'C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i'),
            ]
          ]
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'let' => [
            'cuenta' => '$C_Cuenta',
            'ubi' => strtoupper($ubicacion)
          ],
          'pipeline' => [
            [
              '$match' => [
                '$expr' => [
                  '$and' => [
                    [
                      '$eq' => [
                        '$NIU', '$$cuenta'
                      ]
                    ], [
                      '$eq' => [
                        '$UBICACION', '$$ubi'
                      ]
                    ]
                  ]
                ]
              ]
            ], [
              '$project' => [
                '_id' => 0
              ]
            ]
          ],
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          'usuarios' => '$usuarios.NIU',
          'ubicacion' => '$usuarios.UBICACION',
          'tel' => '$usuarios.TELEFONO',
          'CEL' => '$usuarios.CELULAR'
        ]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['telefonos_validas'] = current($result->toArray()); //20172

  return $telefonos;
  //return $telefonos; //totales:4460, filtrados:20172
}


function getTotallamadas($con, $fechainicio, $fechafin)
{
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $llamadas['cuentas_totales'] = current($result->toArray());
  return $llamadas;
}

function cunetasNuevas($con, $fechainicio, $fechafin)
{

  //contar numero de registros de llamadas en tipificacion
  $filter = [
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.tipificacion', $query);
  $resultado = $result->toArray();
  return $resultado;
}

function cuentasNuevasMunicipio($con, $fechainicio, $fechafin, $municipio)
{
  //contar numero de registros de llamadas en tipificacion
  $filter = [
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.tipificacion', $query);
  $resultado = $result->toArray();
  return $resultado;
}

function cuentasNuevasUbicacion($con, $fechainicio, $fechafin, $ubicacion)
{
  //contar numero de registros de llamadas en tipificacion
  $cuentasNuevas = [];
  $cont = 0;
  $resultado = [];
  $filter = [
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.tipificacion', $query);
  $respuesta = $result->toArray(); //3163

  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i')
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $resultado[$cont] = $response;
        $cont = $cont + 1;
      }
    }
  }
  $cuentas['cuentas_validas'] = $cont; //2474


  return $resultado;
}

function cuentasNuevasUbicacion2($con, $fechainicio, $fechafin, $ubicacion)
{

  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'let' => [
            'cuenta' => '$C_Cuenta',
            'ubi' => strtoupper($ubicacion)
          ],
          'pipeline' => [
            [
              '$match' => [
                '$expr' => [
                  '$and' => [
                    [
                      '$eq' => [
                        '$NIU', '$$cuenta'
                      ]
                    ], [
                      '$eq' => [
                        '$UBICACION', '$$ubi'
                      ]
                    ]
                  ]
                ]
              ]
            ], [
              '$project' => [
                '_id' => 0
              ]
            ]
          ],
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          'UBICACION' => '$usuarios.UBICACION',
          'NIU' => '$usuarios.NIU'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $resultado = $result->toArray();
  return $resultado;
}

function cuentasNuevasUbicacionMunicipio($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  //contar numero de registros de llamadas en tipificacion
  $cuentasNuevas = [];
  $cont = 0;
  $resultado = [];
  $filter = [
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.tipificacion', $query);
  $respuesta = $result->toArray();

  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
        'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i')
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $resultado[$cont] = $response;
        $cont = $cont + 1;
      }
    }
  }
  $cuentas['cuentas_validas'] = $cont;


  return $resultado; //98
}

function cuentasNuevasUbicacionMunicipio2($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i'),
        ]
      ], [
        '$lookup' => [
          'from' => 'usuarios',
          'let' => [
            'cuenta' => '$C_Cuenta',
            'ubi' => strtoupper($ubicacion)
          ],
          'pipeline' => [
            [
              '$match' => [
                '$expr' => [
                  '$and' => [
                    [
                      '$eq' => [
                        '$NIU', '$$cuenta'
                      ]
                    ], [
                      '$eq' => [
                        '$UBICACION', '$$ubi'
                      ]
                    ]
                  ]
                ]
              ]
            ], [
              '$project' => [
                '_id' => 0
              ]
            ]
          ],
          'as' => 'usuarios'
        ]
      ], [
        '$unwind' => [
          'path' => '$usuarios'
        ]
      ], [
        '$project' => [
          'UBICACION' => '$usuarios.UBICACION',
          'NIU' => '$usuarios.NIU'
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $resultado = $result->toArray();
  return $resultado;
}


function Getmodificaciones($con, $niu)
{
  $telefonos = [];
  $cont = 0;
  //filtrar las cuentas por niu y telefonos
  $filter = [
    'C_Cuenta' => $niu,
    'C_Telefono' => new MongoDB\BSON\Regex('^[0-9]{10}$', 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.tipificacion', $query);
  $resultado = $result->toArray();

  //almacenar en un array los telefonos encontrados de esa cuenta
  foreach ($resultado as $clave => $valor) {
    array_push($telefonos, $valor->C_Telefono);
  }

  //contrar cuantos numeros diferentes hay, para sacar las modificaciones
  $telefonosUnicos = array_unique($telefonos);
  foreach ($telefonosUnicos as $clave => $valor) {
    if ($telefonos[0] != $valor) {
      $cont = $cont + 1;
    }
  }

  return $cont;
}

function Getmodificaciones2($con, $niu)
{
  $telefonos = [];
  $cont = 0;
  //filtrar las cuentas por niu y telefonos
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'C_Cuenta' => $niu,
          '$or' => [
            [
              'C_Telefono' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ], [
              'C_Celular' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $resultado = $result->toArray();

  //almacenar en un array los telefonos encontrados de esa cuenta
  foreach ($resultado as $clave => $valor) {
    array_push($telefonos, $valor->C_Telefono);
  }

  //contrar cuantos numeros diferentes hay, para sacar las modificaciones
  $telefonosUnicos = array_unique($telefonos);
  foreach ($telefonosUnicos as $clave => $valor) {
    if ($telefonos[0] != $valor) {
      $cont = $cont + 1;
    }
  }

  return $cont;
}

function GetConfirmaciones($con, $niu)
{
  $telefonos = [];
  $cont = 0;
  //filtrar las cuentas por niu y telefonos
  $filter = [
    'C_Cuenta' => $niu,
    'C_Telefono' => new MongoDB\BSON\Regex('^[0-9]{10}$', 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.tipificacion', $query);
  $resultado = $result->toArray();

  //almacenar en un array los telefonos encontrados de esa cuenta
  foreach ($resultado as $clave => $valor) {
    array_push($telefonos, $valor->C_Telefono);
  }
  //[1,2,2,2]
  //[1,2][2,2]

  //contrar cuantos numeros diferentes hay, para sacar las modificaciones
  $telefonosUnicos = array_unique($telefonos);
  $confirmaciones = count($telefonos) - count($telefonosUnicos);

  return $confirmaciones;
}

function GetConfirmaciones2($con, $niu)
{
  $telefonos = [];
  $cont = 0;
  //filtrar las cuentas por niu y telefonos
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'C_Cuenta' => $niu,
          '$or' => [
            [
              'C_Telefono' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ], [
              'C_Celular' => [
                '$regex' => '^[0-9]{10}$'
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $resultado = $result->toArray();

  //almacenar en un array los telefonos encontrados de esa cuenta
  foreach ($resultado as $clave => $valor) {
    array_push($telefonos, $valor->C_Telefono);
  }
  //[1,2,2,2]
  //[1,2][2,2]

  //contrar cuantos numeros diferentes hay, para sacar las modificaciones
  $telefonosUnicos = array_unique($telefonos);
  $confirmaciones = count($telefonos) - count($telefonosUnicos);

  return $confirmaciones;
}

//obtener el porcentaje de eficacia
function getPorcentajeEficacia($con, $fechainicio, $fechafin)
{

  $filter = [
    'fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.gestionLlamadas', $query);
  $resultado = $result->toArray();

  return $resultado;
}


function obtenerConsultasLlamadasEntrantesContestadasDia($con, $anio, $mes)
{
  $fecha = $anio . '-' . $mes;
  $filter = [
    'fecha' => new MongoDB\BSON\Regex($fecha, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.gestionLlamadas', $query);
  $resultado = $result->toArray();

  return $resultado;
}

function obtenerConsultasLlamadasEntrantesContestadasMes($con, $anio)
{
  $filter = [
    'fecha' => new MongoDB\BSON\Regex($anio, 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.gestionLlamadas', $query);
  $resultado = $result->toArray();

  return $resultado;
}

function obtenerConsultasLlamadasEntrantesContestadasSemana($con, $fechainicio, $fechafin)
{
  $filter = [
    'fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . '.gestionLlamadas', $query);
  $resultado = $result->toArray();

  return $resultado;
}


function getLlamadasCunetasTelefonoValidos($con, $fechainicio, $fechafin)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //filtrar cuentas validas de tipificacion
  $filter = [
    'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
    'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
    'C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{6,10}$", 'i'),
  ];
  $query = new MongoDB\Driver\Query($filter);
  $result = $con->executeQuery(DB_DIFUSION_CHEC . ".tipificacion", $query);
  $respuesta = $result->toArray();
  //verificar que las cuentas anteriormente obtenidas, existan
  if (count($respuesta) > 0) {
    $filter = '';
    foreach ($respuesta as $clave => $valor) {
      $filter = [
        'NIU' => $valor->C_Cuenta,
        '$and' => [
          [
            '$or' => [
              ['TELEFONO' => $valor->C_Telefono],
              ['CELULAR' => $valor->C_Telefono],
            ]
          ]
        ]
      ];
      $query = new MongoDB\Driver\Query($filter);
      $result = $con->executeQuery(DB_DIFUSION_CHEC . '.usuarios', $query);
      $response = $result->toArray();
      if (count($response) > 0) {
        $cont = $cont + 1;
      }
    }
  }
  $telefonos['cuentas_telefonos_validas'] = $cont;

  return $telefonos;
}

function getLlamadasCunetasTelefonoValidos2($con, $fechainicio, $fechafin)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());

  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          '$or' => [
            ['C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')],
            ['C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')]
          ],
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ],
          'niu' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Cuenta', null
                ]
              ],
              'then' => '$C_Cuenta',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ],
            [
              'celular' => [
                '$exists' => True
              ]
            ],
            [
              'niu' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $telefonosFiltrados = [];
  $cuentasFiltradas = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono) && $valor->telefono != '') {
        array_push($telefonosFiltrados, $valor->telefono);
      }

      if (isset($valor->celular) && $valor->celular != '') {
        array_push($telefonosFiltrados, $valor->celular);
      }

      if (isset($valor->niu) && $valor->niu != '') {
        array_push($cuentasFiltradas, $valor->niu);
      }
    }

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'NIU' => ['$in' => $cuentasFiltradas],
            'CELULAR' => ['$in' => $telefonosFiltrados],
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['cuentas_telefonos_validas'] = current($result->toArray());
  } else {
    $telefonos['cuentas_telefonos_validas'] = false;
  }

  return $telefonos;
}

function getLlamadasCunetasTelefonoValidosMunicipios2($con, $fechainicio, $fechafin, $municipio)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          '$or' => [
            ['C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')],
            ['C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')]
          ],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ],
          'niu' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Cuenta', null
                ]
              ],
              'then' => '$C_Cuenta',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ],
            [
              'celular' => [
                '$exists' => True
              ]
            ],
            [
              'niu' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $telefonosFiltrados = [];
  $cuentasFiltradas = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono) && $valor->telefono != '') {
        array_push($telefonosFiltrados, $valor->telefono);
      }

      if (isset($valor->celular) && $valor->celular != '') {
        array_push($telefonosFiltrados, $valor->celular);
      }

      if (isset($valor->niu) && $valor->niu != '') {
        array_push($cuentasFiltradas, $valor->niu);
      }
    }

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'NIU' => ['$in' => $cuentasFiltradas],
            'CELULAR' => ['$in' => $telefonosFiltrados],
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['cuentas_telefonos_validas'] = current($result->toArray());
  }

  return $telefonos;
}

function getLlamadasCunetasTelefonoValidosUbicacion2($con, $fechainicio, $fechafin, $ubicacion)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          '$or' => [
            ['C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')],
            ['C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')]
          ],
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ],
          'niu' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Cuenta', null
                ]
              ],
              'then' => '$C_Cuenta',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ],
            [
              'celular' => [
                '$exists' => True
              ]
            ],
            [
              'niu' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $telefonosFiltrados = [];
  $cuentasFiltradas = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono) && $valor->telefono != '') {
        array_push($telefonosFiltrados, $valor->telefono);
      }

      if (isset($valor->celular) && $valor->celular != '') {
        array_push($telefonosFiltrados, $valor->celular);
      }

      if (isset($valor->niu) && $valor->niu != '') {
        array_push($cuentasFiltradas, $valor->niu);
      }
    }

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
            'NIU' => ['$in' => $cuentasFiltradas],
            'CELULAR' => ['$in' => $telefonosFiltrados],
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['cuentas_telefonos_validas'] = current($result->toArray());
  }

  return $telefonos;
}

function getLlamadasCunetasTelefonoValidosUbicacionMunicipio2($con, $fechainicio, $fechafin, $municipio, $ubicacion)
{
  $telefonos = [];
  $cuentasValidas = [];
  $cont = 0;
  //contar numero de registros de llamadas en tipificacion
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
        ]
      ],
      [
        '$count' => 'n',
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $telefonos['cuentas_telefonos_totales'] = current($result->toArray());


  //contar numero de registros en tipificacion filtrados
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'tipificacion',
    'pipeline' => [
      [
        '$match' => [
          'Fecha' => ['$gte' => $fechainicio, '$lt' => $fechafin],
          'C_Cuenta' => new MongoDB\BSON\Regex("^[0-9]{9}$", 'i'),
          '$or' => [
            ['C_Telefono' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')],
            ['C_Celular' => new MongoDB\BSON\Regex("^[0-9]{10}$", 'i')]
          ],
          'L_Municipio' => new MongoDB\BSON\Regex($municipio, 'i')
        ]
      ], [
        '$project' => [
          'telefono' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Telefono', null
                ]
              ],
              'then' => '$C_Telefono',
              'else' => '$$REMOVE'
            ]
          ],
          'celular' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Celular', null
                ]
              ],
              'then' => '$C_Celular',
              'else' => '$$REMOVE'
            ]
          ],
          'niu' => [
            '$cond' => [
              'if' => [
                '$ne' => [
                  '$C_Cuenta', null
                ]
              ],
              'then' => '$C_Cuenta',
              'else' => '$$REMOVE'
            ]
          ]
        ]
      ], [
        '$match' => [
          '$or' => [
            [
              'telefono' => [
                '$exists' => True
              ]
            ],
            [
              'celular' => [
                '$exists' => True
              ]
            ],
            [
              'niu' => [
                '$exists' => True
              ]
            ]
          ]
        ]
      ]
    ],
    'cursor' => new stdClass,
  ]);
  $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
  $response = $result->toArray();

  //separar telefonos y celulares encontrados enteriormente
  $telefonosFiltrados = [];
  $cuentasFiltradas = [];
  if (count($response) > 0) {
    foreach ($response as $clave => $valor) {
      if (isset($valor->telefono) && $valor->telefono != '') {
        array_push($telefonosFiltrados, $valor->telefono);
      }

      if (isset($valor->celular) && $valor->celular != '') {
        array_push($telefonosFiltrados, $valor->celular);
      }

      if (isset($valor->niu) && $valor->niu != '') {
        array_push($cuentasFiltradas, $valor->niu);
      }
    }

    //buscar telefonos y celulares en usuarios
    $Command = new MongoDB\Driver\Command([
      'aggregate' => 'usuarios',
      'pipeline' => [
        [
          '$match' => [
            'UBICACION' => new MongoDB\BSON\Regex($ubicacion, 'i'),
            'NIU' => ['$in' => $cuentasFiltradas],
            'CELULAR' => ['$in' => $telefonosFiltrados],
          ],
        ],
        [
          '$count' => 'n',
        ]
      ],
      'cursor' => new stdClass(),
    ]);
    $result = $con->executeCommand(DB_DIFUSION_CHEC, $Command);
    $telefonos['cuentas_telefonos_validas'] = current($result->toArray());
  }

  return $telefonos;
}

function filterAccesosSubmenu($con, $fechainicio, $fechafin)
{
  //buscar accesos a submenu
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_menu_usuarios',
    'pipeline' => [
      [
        '$match' => [
          'FECHA_RESULTADO' => [
            '$gte' => $fechainicio,
            '$lt' => $fechafin
          ],
          '$or' => [
            [
              'MENU' => 'fasesor_remoto'
            ], [
              'MENU' => 'fCopia factura'
            ], [
              'MENU' => 'fCupon actual'
            ], [
              'MENU' => 'fCupon anterior'
            ], [
              'MENU' => 'fcomo_pago_linea'
            ], [
              'MENU' => 'fpago_linea'
            ], [
              'MENU' => 'fpqr'
            ], [
              'MENU' => 'fvacantes'
            ], [
              'MENU' => 'fcomo_pago_linea'
            ], [
              'MENU' => 'ffraude'
            ], [
              'MENU' => 'Cupon pago'
            ], [
              'MENU' => 'Pago en Linea'
            ]
          ]
        ]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $result = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultado = current($result->toArray());


  //buscar accesos a criterios de busqueda
  $Command = new MongoDB\Driver\Command([
    'aggregate' => 'log_busqueda_usuarios',
    'pipeline' => [
      [
        '$match' => ['FECHABUSQUEDA' => ['$gte' => $fechainicio, '$lt' => $fechafin]]
      ], [
        '$count' => 'n'
      ]
    ],
    'cursor' => new stdClass(),
  ]);
  $resultCriterio = $con->executeCommand(DB_HEROKU_CHEC, $Command);
  $resultadoCriterio = current($resultCriterio->toArray());

  if ($resultadoCriterio == false) {
    $resultadoCriterio = 0;
  } else {
    $resultadoCriterio = $resultadoCriterio->n;
  }

  if ($resultado == false) {
    $resultado = 0;
  } else {
    $resultado = $resultado->n;
  }


  $totalAccesos = $resultado + $resultadoCriterio;

  return  $totalAccesos;
}
