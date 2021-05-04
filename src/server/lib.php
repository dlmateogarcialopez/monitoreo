<?php

if ($_SERVER['SERVER_NAME'] === "localhost") {
  header('Access-Control-Allow-Origin: http://localhost:4200');
}
// header("Access-Control-Allow-Origin: http://127.0.0.1:4200");
// header("Access-Control-Allow-Origin: http://dev.com:4200");
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Credentials: true');
// header("Content-type: application/json");

error_reporting(E_ALL);
// ini_set('display_errors', '1');
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '0');
date_default_timezone_set('America/Bogota');

require 'consultas.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

class sgcbApi
{
  /* Datos BD IntraChec */
  private $conIntraChec;
  private $hostIntraChec;
  // private $hostIntraChec = 'mongodb://localhost:27017';

  /* Datos BD Difusión */
  private $conDifusion;
  private $hostDifusion = 'mongodb://datalab:umCHEC1234@40.87.47.53:27017/admin';
  private $conHerokuChec;
  //produccion heroku
  private $hostHerokuChec = 'mongodb://heroku_qqkvqh3x:TxjMxP9HKb7gpU@chec-bdpm01.chec.corp.epm.com.co:27017/?authSource=heroku_qqkvqh3x';
  private $conHerokuChecSgcb;
  //produccion sgcb
  private $hostHerokuChecSgcb = 'mongodb://SGCB:nwT%25T%23YFMEK%3Ah@CHEC-BDPM01.chec.corp.epm.com.co:27017/?authSource=SGCB&readPreference=primary&appname=MongoDB%20Compass&ssl=false';
  
  //desarrollo sgcb
  //private $hostHerokuChecSgcb = 'mongodb://SGCB:SGCB@chec-apd08.chec.corp.epm.com.co:27017/?authSource=SGCB&readPreference=primary&appname=MongoDB%20Compass%20Community&ssl=false';
  // private $hostDifusion = "mongodb://heroku_qqkvqh3x:b50q78jtvojl1aobh01eut4rpj@ds215358-a1.mlab.com:15358/heroku_qqkvqh3x?retryWrites=false";
  /** Nombre del servidor que utiliza CHEC */
  private $nombreServidorChec = "intrachecdes.chec.com.co";

  //mongodb://SGCB:nwT%25T%23YFMEK%3Ah@CHEC-BDPM01.chec.corp.epm.com.co:27017/?authSource=SGCB&readPreference=primary&appname=MongoDB%20Compass&ssl=false

  public function __construct()
  {
    if ($_SERVER['SERVER_NAME'] === $this->nombreServidorChec) {
      $this->hostIntraChec = 'mongodb://SGCB:SGCB@chec-apd08.chec.corp.epm.com.co:27017/SGCB';
    } else {
      $this->hostIntraChec = 'mongodb://localhost:27017';
    }

    $this->connectDbIntraChec();
    $this->connectDbDifusion();
    $this->connectDbHerokuChec();
    $this->connectDbHerokuChecSgcb();
  }

  public function connectDbIntraChec()
  {
    try {
      $this->conIntraChec = new MongoDB\Driver\Manager($this->hostIntraChec);
    } catch (MongoDB\Driver\Exception\Exception $e) {
      $filename = basename(__FILE__);
      echo "The $filename script has experienced an error.\n";
      echo "It failed with the following exception:\n";
      echo 'Exception:', $e->getMessage(), "\n";
      echo 'In file:', $e->getFile(), "\n";
      echo 'On line:', $e->getLine(), "\n";
    }
  }

  public function connectDbHerokuChec()
  {
    try {
      $this->conHerokuChec = new MongoDB\Driver\Manager($this->hostHerokuChec);
    } catch (MongoDB\Driver\Exception\Exception $e) {
      $filename = basename(__FILE__);
      echo "The $filename script has experienced an error.\n";
      echo "It failed with the following exception:\n";
      echo 'Exception:', $e->getMessage(), "\n";
      echo 'In file:', $e->getFile(), "\n";
      echo 'On line:', $e->getLine(), "\n";
    }
  }

  public function connectDbHerokuChecSgcb()
  {
    try {
      $this->conHerokuChecSgcb = new MongoDB\Driver\Manager($this->hostHerokuChecSgcb);
    } catch (MongoDB\Driver\Exception\Exception $e) {
      $filename = basename(__FILE__);
      echo "The $filename script has experienced an error.\n";
      echo "It failed with the following exception:\n";
      echo 'Exception:', $e->getMessage(), "\n";
      echo 'In file:', $e->getFile(), "\n";
      echo 'On line:', $e->getLine(), "\n";
    }
  }

  public function connectDbDifusion()
  {
    try {
      $this->conDifusion = new MongoDB\Driver\Manager($this->hostDifusion);
    } catch (MongoDB\Driver\Exception\Exception $e) {
      $filename = basename(__FILE__);
      echo "The $filename script has experienced an error.\n";
      echo "It failed with the following exception:\n";
      echo 'Exception:', $e->getMessage(), "\n";
      echo 'In file:', $e->getFile(), "\n";
      echo 'On line:', $e->getLine(), "\n";
    }
  }

  //Decodificacion de json que llega del front
  public function detectRequestBody()
  {
    return json_decode(file_get_contents('php://input'), true);
  }

  public function sanitize_string($string)
  {
    return trim(filter_var(preg_replace('/\s\s+/', ' ', stripslashes($string)), FILTER_SANITIZE_STRING));
  }

  public function sanitize_integer($integer)
  {
    return trim(filter_var(preg_replace('/\s\s+/', ' ', $integer), FILTER_SANITIZE_NUMBER_INT));
  }

  public function sanitize_float($float)
  {
    return trim(filter_var(preg_replace('/\s\s+/', ' ', $float), FILTER_SANITIZE_NUMBER_FLOAT));
  }

  public function validate_boolean($boolean)
  {
    return filter_var($boolean, FILTER_VALIDATE_BOOLEAN);
  }

  public function generateRandomToken($length)
  {
    $token = '';
    $codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
    $codeAlphabet .= '0123456789';
    $max = strlen($codeAlphabet);

    for ($i = 0; $i < $length; ++$i) {
      $token .= $codeAlphabet[random_int(0, $max - 1)];
    }

    return $token . time();
  }

  /* function getAuthorizationHeader()
    {
      $headers = null;
      if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
      } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
      } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
          $headers = trim($requestHeaders['Authorization']);
        }
      }
      return $headers;
    } */

  /** Obtiene el Bearer token desde los encabezados de una petición HTTP */
  public function getBearerTokenFromHeader()
  {
    /* $headers = $this->getAuthorizationHeader(); Utilizar esta función si en el servidor de producción no sirve apache_request_headers() */
    $headers = apache_request_headers();
    $authorizationHeader = $headers['Authorization'];
    $isBearer = substr($authorizationHeader, 0, 6) === 'Bearer';

    /* Si la cabecera Authorization no es vacía y contiene "Bearer" en su valor, extraer el token, el cual se obtiene a partir de la posición 7 del valor de la cabecera. */
    if (!empty($authorizationHeader) && $isBearer) {
      return substr($authorizationHeader, 7);
    }
    return null;
  }

  public function setJwtToken($idUsuario)
  {
    if (isset($_COOKIE['refreshToken'])) {
      $usuarioActual = getUsuarioIndividualQuery($this->conIntraChec, $idUsuario);
      $refreshTokenExists = getRefreshToken($this->conIntraChec, $_COOKIE['refreshToken'], $idUsuario);

      if ($refreshTokenExists) {
        return Auth::encodeJwt($usuarioActual);
      }
    }
    return false;
  }

  /** Verifica si las peticiones se hacen por HTTP o HTTPS */
  public function isHttps()
  {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === '443';
  }

  /** Define la URL que se va a utilizar en las peticiones CURL para envío de SMS  */
  private function setHostUrl()
  {
    if ($_SERVER['SERVER_NAME'] === $this->nombreServidorChec) {
      return "https://$this->nombreServidorChec";
    }
    return "http://localhost";
  }

  public function isJwtTokenValid($jwtToken = null)
  {
    /* Si no se envía el parámetro $jwtToken, el token se decodificará en la función getBearerTokenFromHeader(). Esta función decodificará los tokens enviados a través de las cabeceras en cada petición HTTP para verificar que el usuario esté autorizado a realizar dicha petición. */
    if ($jwtToken === null) {
      $valid = true;
      try {
        Auth::decodeJwt($this->getBearerTokenFromHeader());
      } catch (Exception $e) {
        $valid = false;
      }
    } else {
      /* De lo contrario, se decodificará el token enviado como parámetro. Se utiliza este bloque para decodificar el token enviado por URL para el usuario que haya olvidado su contraseña de ingreso a la aplicación. */
      try {
        $decoded = Auth::decodeJwt($jwtToken);
        $isPasswordHashValid = $this->verificarHashPassword($decoded->idUsuario, $decoded->hashId);

        if ($isPasswordHashValid) {
          $valid = $isPasswordHashValid;
        } else {
          $valid = false;
          /* WHAT? Mejor solo poner $valid = $isPasswordHashValid; */
        }
      } catch (Exception $e) {
        $valid = false;
      }
    }

    return $valid;
  }

  public function verificarHashPassword($idUsuario, $password)
  {
    return verificarHashPasswordQuery($this->conIntraChec, $idUsuario, $password);
  }

  public function getLogin($correo, $password)
  {
    $response = getLoginQuery($this->conIntraChec, $correo, $password);

    if ($response) {
      $datos = new stdClass();
      $oid = '$oid';
      $datos->jwtToken = Auth::encodeJwt($response);
      /* Se eliminan los permisos para que solo aparezcan en la clave JWT del localStorage y puedan ser decodificados en el front */
      unset($response->permisos);
      $datos->datosUsuario = $response;
      $randomToken = $this->generateRandomToken(30);
      saveRefreshToken($this->conIntraChec, $datos->datosUsuario->_id->$oid, $randomToken);

      /* Esta cookie expirará en 7 días */
      setcookie('refreshToken', $randomToken, strtotime('+7 days'), '/', '', $this->isHttps(), true);

      return $datos;
    }

    return 'El correo o la contraseña son incorrectos.';
  }

  public function logout($idUsuario)
  {
    if (isset($_COOKIE['refreshToken'])) {
      /* Se borra la cookie */
      setcookie('refreshToken', $_COOKIE['refreshToken'], 1, '/', '', $this->isHttps(), true);

      return logoutQuery($this->conIntraChec, $idUsuario, $_COOKIE['refreshToken']);
    }

    return false;
  }

  public function getUsuarios()
  {
    // if ($this->isJwtTokenValid()) {
    return getUsuariosQuery($this->conIntraChec);
    // }
    // return http_response_code(401);
  }

  public function getUsuarioIndividual($idUsuario)
  {
    return getUsuarioIndividualQuery($this->conIntraChec, $idUsuario);
  }

  public function getCuotaMensajesUsuario($idUsuario)
  {
    return getCuotaMensajesUsuarioQuery($this->conIntraChec, $idUsuario);
  }

  public function updateCuotaMensajesUsuarioEnvioSms($idCuota, $cantidadMensajesEnviados)
  {
    return updateCuotaMensajesUsuarioEnvioSmsQuery($this->conIntraChec, $idCuota, $cantidadMensajesEnviados);
  }

  public function getDatosUsuario($idUsuario)
  {
    if ($this->isJwtTokenValid()) {
      return getDatosUsuarioQuery($this->conIntraChec, $idUsuario);
    }

    return http_response_code(401);
  }

  public function getCorreoUsuario($correo)
  {
    $response = getCorreoUsuarioQuery($this->conIntraChec, $correo);

    if (count($response)) {
      $datosCorreo = [
        'id' => $response[0]->_id,
        'correo' => $response[0]->correo,
        'hashId' => $response[0]->password,
      ];

      return $datosCorreo;
    }

    return 'La dirección de correo no se encuentra registrada.';
  }

  public function buscarCorreoExistenteUsuario($idUsuario, $correo)
  {
    return buscarCorreoExistenteUsuarioQuery($this->conIntraChec, $idUsuario, $correo);
  }

  public function getPasswordActual($id, $passwordActual, $passwordNuevo)
  {
    $response = getPasswordActualQuery($this->conIntraChec, $id, $passwordActual, $passwordNuevo);

    if ($response) {
      return $response;
    }

    return 'La contraseña actual no es válida.';
  }

  public function resetPasswordUsuario($id, $password)
  {
    return resetPasswordUsuarioQuery($this->conIntraChec, $id, $password);
  }

  public function insertUsuario($nombres, $apellidos, $cargo, $correo, $password, $permisos, $cuotaMensajes)
  {
    return insertUsuarioQuery($this->conIntraChec, $nombres, $apellidos, $cargo, $correo, $password, $permisos, $cuotaMensajes);
  }

  public function updateUsuario($id, $nombres, $apellidos, $cargo, $correo, $permisos, $cuotaMensajes)
  {
    return updateUsuarioQuery($this->conIntraChec, $id, $nombres, $apellidos, $cargo, $correo, $permisos, $cuotaMensajes);
  }

  public function updatePerfilUsuario($datosPerfilUsuario)
  {
    return updatePerfilUsuarioQuery($this->conIntraChec, $datosPerfilUsuario);
  }

  public function insertCuotaMensajesUsuario($idUsuario, $nombreUsuario, $cuotaMensajes)
  {
    return insertCuotaMensajesUsuarioQuery($this->conIntraChec, $idUsuario, $nombreUsuario, $cuotaMensajes);
  }

  public function getCuotaMensajesCliente()
  {
    return getCuotaMensajesClienteQuery($this->conIntraChec);
  }

  public function insertCuotaMensajesCliente($idUsuario, $nombreUsuario, $cuotaMensajes, $fechaRegistro)
  {
    return insertCuotaMensajesClienteQuery($this->conIntraChec, $idUsuario, $nombreUsuario, $cuotaMensajes, $fechaRegistro);
  }

  public function updateCuotaMensajesClienteDod($fechaInicioMensajesCliente, $fechaFinMensajesCliente, $cantidadMensajesCliente, $cantidadMensajesClienteAnterior)
  {
    return updateCuotaMensajesClienteDodQuery($this->conIntraChec, $fechaInicioMensajesCliente, $fechaFinMensajesCliente, $cantidadMensajesCliente, $cantidadMensajesClienteAnterior);
  }

  public function inactivarUsuario($idUsuario)
  {
    return inactivarUsuarioQuery($this->conIntraChec, $idUsuario);
  }

  //FUNCIONES DE BASE DE DATOS
  public function findCampos($campo)
  {
    return findCampos($this->conDifusion, $campo);
  }

  public function totalCampos($campos)
  {
    $jsonSalida['total_usuarios'] = totalUsuarios($this->conDifusion, $campos);
    $jsonSalida['total_usuarios_cel_validos'] = totalUsuariosCelularValido($this->conDifusion, $campos)->total_usuarios_cel_validos;

    return $jsonSalida;
  }

  public function getUsersSendSMS($campos, $total_usuarios)
  {
    return getUsersSendSMSQuery($this->conDifusion, $campos, $total_usuarios);
  }

  //INSERTAR Y ENVIAR MENSAJES DE TEXTO INDIVIDUALES O PRECARGADOS
  public function insertSendMessageIndividualOrPrecargado($idUsuario, $nombreUsuario, $fecha, $motivoEnvio, $nombreBolsa, $metodoEnvio, $tipoMensaje, $rawMensaje, $mensajes, $valorMensajeIndividual)
  {
    if (is_array($mensajes)) {
      $insertConsolidado = insertSendMessageConsolidado($this->conIntraChec, $idUsuario, $nombreUsuario, $fecha, $motivoEnvio, $metodoEnvio, $tipoMensaje, $rawMensaje, 0, 'En proceso', 0, $valorMensajeIndividual);
      // $insertConsolidado = "";
      if ($insertConsolidado != 'Error') {
        $data = array(
          'mensajes' => json_encode($mensajes),
          'idUsuario' => $idUsuario,
          'insertConsolidado' => $insertConsolidado,
          'tipoMensaje' => $tipoMensaje,
          'valorMensajeIndividual' => $valorMensajeIndividual,
          'nombreBolsa' => $nombreBolsa,
        );

        try {
          $ch = curl_init();
          // curl_setopt($ch, CURLOPT_URL, $this->setHostUrl() . ":8080/sgcb/src/server/insertSend/insertSendIndividual.php");
          curl_setopt($ch, CURLOPT_URL, $this->setHostUrl() . "/sgcb/src/server/insertSend/insertSendIndividual.php");
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_TIMEOUT, 5);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
          $result = curl_exec($ch);
          // curl_close($ch);
          // var_dump($result);
          return 'Procesando';
        } catch (\Throwable $th) {
          return $th;
        }
      }
    }
    return 'Error';
  }

  // INSERCIÓN PARA PROCESO EN PARALELO
  public function insertSendParaleloIndividual($data)
  {
    include '../sendSMS/sendSMS.php';
    $apiPhone = new sendPhoneAPI();
    $response = 'Error';
    $totalInsert = 0;
    // var_dump($data);
    $mensajes = json_decode($data['mensajes']);
    $cuotaMensajesClientes = getCuotaMensajesClienteQuery($this->conIntraChec);
    /** @var cantidadMensajesCliente Retorna la cuota actual de mensajes para todos los clientes */
    $cantidadMensajesCliente = $cuotaMensajesClientes->cantidadMensajesCliente;
    $datosFechasLogDod = [
      "fechaInicio" => $cuotaMensajesClientes->fechaInicioMensajesCliente,
      "fechaFin" => $cuotaMensajesClientes->fechaFinMensajesCliente,
    ];
    $logCelularesDod = getLogCelularesDodQuery($this->conIntraChec, $datosFechasLogDod);

    foreach ($mensajes as $key => $valueMensaje) {
      foreach ($logCelularesDod as $indexCelularDod => $valueCelularDod) {
        if ($valueCelularDod->celular === $valueMensaje->celular) {
          $cantidadMensajesCliente = $valueCelularDod->cantidadCuotaMensajesCliente;
          break;
        }
      }
      $datosLogDod = [
        "idUsuario" => $data['idUsuario'],
        "cuenta" => $valueMensaje->cuenta,
        "nombre" => $valueMensaje->nombre,
        "celular" => $valueMensaje->celular,
        "mensaje" => $valueMensaje->mensaje,
        "cantidadCaracteres" => $valueMensaje->cantidadCaracteres,
        "cantidadMensajes" => $valueMensaje->cantidadMensajes,
        "idConsolidado" => $data['insertConsolidado'],
        "tipoMensaje" => $data['tipoMensaje'],
        "cantidadMensajesClienteRestantes" => $cantidadMensajesCliente - 1,
        "valorMensajeIndividual" => $data['valorMensajeIndividual'],
      ];
      $insertUnitario = insertSendMessageUnitario($this->conIntraChec, $datosLogDod);

      $cantidadMensajesCliente = $cuotaMensajesClientes->cantidadMensajesCliente;

      if ($insertUnitario->getInsertedCount()) {
        $totalInsert += $valueMensaje->cantidadMensajes;
        // PARA ENVIAR MENSAJES
        $datosDodAcuse = [
          "idUsuario" => $data['idUsuario'],
          "cuenta" => $valueMensaje->cuenta,
          "nombre" => $valueMensaje->nombre,
          "celular" => $valueMensaje->celular,
          "mensaje" => $valueMensaje->mensaje,
          "tipoMensaje" => $data['tipoMensaje'],
          "idConsolidado" => $data['insertConsolidado'],
          "cantidadCaracteres" => $valueMensaje->cantidadCaracteres,
          "cantidadMensajes" => $valueMensaje->cantidadMensajes,
          "nombreBolsa" => $data['nombreBolsa'],
          "estado" => "1",
        ];
        insertDodAcuse($this->conIntraChec, $datosDodAcuse);

        //PROVISIONAL
        $datosAcuseRecibo = [
          // "celular" => "57" . $valueMensaje->celular,
          // "estado" => "1",
          // "fecha" => date("Y-m-d H:i:s"),
          "idConsolidado" => $data['insertConsolidado'],
          "cantidadMensajes" => $valueMensaje->cantidadMensajes,
          "valorMensajeIndividual" => $data['valorMensajeIndividual'],
        ];
        updateValorConsolidadoDod($this->conIntraChec, $datosAcuseRecibo);

        //DESCOMENTAR ESTA LINEA PARA ENVIAR LOS MENSAJES INDIVIDUALES
        $datosApiPhone = [
          "celular" => $valueMensaje->celular,
          "mensaje" => $valueMensaje->mensaje,
          "idConsolidado" => $data['insertConsolidado'],
          "cantidadMensajes" => $valueMensaje->cantidadMensajes,
          "nombreBolsa" => $data['nombreBolsa'],
          "valorMensajeIndividual" => $data['valorMensajeIndividual'],
        ];
        $apiPhone->sendMessageIndividual($datosApiPhone);
      }
    }
    updateSaldoBolsa($this->conDifusion, $this->conIntraChec, $data['insertConsolidado'], $data['idUsuario'], $data['nombreBolsa']);

    $insertConsolidado2 = updateEstadoConsolidadoIndividual2($this->conIntraChec, $data['insertConsolidado'], 'Enviado', $totalInsert);

    if ($insertConsolidado2->getModifiedCount()) {
      $response = 'Ok';
    }
    return $response;
  }

  // INSERTAR Y ENVIAR MENSAJES DE TEXTO GENERAL
  public function insertSendMessageGeneral($idUsuario, $nombreUsuario, $fecha, $motivoEnvio, $nombreBolsa, $metodoEnvio, $tipoMensaje, $rawMensaje, $mensajes, $valorMensajeIndividual)
  {
    $response = 'Error';
    $totalInsert = 0;
    $celulares = array();

    if (is_array($mensajes['datosUsuario'])) {
      $insertConsolidado = insertSendMessageConsolidado($this->conIntraChec, $idUsuario, $nombreUsuario, $fecha, $motivoEnvio, $metodoEnvio, $tipoMensaje, $rawMensaje, count($mensajes['datosUsuario']), 'En proceso', 0, $valorMensajeIndividual);

      $data = array(
        'mensajes' => json_encode($mensajes),
        'idUsuario' => $idUsuario,
        'insertConsolidado' => $insertConsolidado,
        'tipoMensaje' => $tipoMensaje,
        'valorMensajeIndividual' => $valorMensajeIndividual,
        'nombreBolsa' => $nombreBolsa,
      );
      try {
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $this->setHostUrl() . ':8080/sgcb/src/server/insertSend/insertSendGeneral.php');
        curl_setopt($ch, CURLOPT_URL, $this->setHostUrl() . '/sgcb/src/server/insertSend/insertSendGeneral.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);

        return 'Procesando';
      } catch (\Throwable $th) {
        return $th;
      }
    }

    return $response;
  }

  public function insertSendParaleloGeneral($data)
  {
    include '../sendSMS/sendSMS.php';
    $apiPhone = new sendPhoneAPI();
    $mensajes = json_decode($data['mensajes']);
    $celulares = array();
    $cantidadReal = 0;

    $cuotaMensajesClientes = getCuotaMensajesClienteQuery($this->conIntraChec);
    /** @var cantidadMensajesCliente Retorna la cuota actual de mensajes para todos los clientes */
    $cantidadMensajesCliente = $cuotaMensajesClientes->cantidadMensajesCliente;
    $datosFechasLogDod = [
      "fechaInicio" => $cuotaMensajesClientes->fechaInicioMensajesCliente,
      "fechaFin" => $cuotaMensajesClientes->fechaFinMensajesCliente,
    ];
    $logCelularesDod = getLogCelularesDodQuery($this->conIntraChec, $datosFechasLogDod);

    foreach ($mensajes->datosUsuario as $valueMensaje) {
      foreach ($logCelularesDod as $indexCelularDod => $valueCelularDod) {
        if ($valueCelularDod->celular === $valueMensaje->celular) {
          $cantidadMensajesCliente = $valueCelularDod->cantidadCuotaMensajesCliente;
          break;
        }
      }

      $datosLogDod = [
        "idUsuario" => $data['idUsuario'],
        "cuenta" => $valueMensaje->cuenta,
        "nombre" => $valueMensaje->nombre,
        "celular" => $valueMensaje->celular,
        "mensaje" => $mensajes->mensaje,
        "cantidadCaracteres" => $mensajes->cantidadCaracteres,
        "cantidadMensajes" => $mensajes->cantidadMensajes,
        "idConsolidado" => $data['insertConsolidado'],
        "tipoMensaje" => $data['tipoMensaje'],
        "cantidadMensajesClienteRestantes" => $cantidadMensajesCliente - 1,
        "valorMensajeIndividual" => $data['valorMensajeIndividual'],
      ];
      insertSendMessageUnitario($this->conIntraChec, $datosLogDod);

      $cantidadMensajesCliente = $cuotaMensajesClientes->cantidadMensajesCliente;
      $cantidadReal += $mensajes->cantidadMensajes;
      $datosDodAcuse = [
        "idUsuario" => $data['idUsuario'],
        "cuenta" => $valueMensaje->cuenta,
        "nombre" => $valueMensaje->nombre,
        "celular" => $valueMensaje->celular,
        "mensaje" => $mensajes->mensaje,
        "tipoMensaje" => $data['tipoMensaje'],
        "idConsolidado" => $data['insertConsolidado'],
        "cantidadCaracteres" => $mensajes->cantidadCaracteres,
        "cantidadMensajes" => $mensajes->cantidadMensajes,
        "nombreBolsa" => $data['nombreBolsa'],
        "estado" => "1",
      ];
      insertDodAcuse($this->conIntraChec, $datosDodAcuse);

      //LINEA PROVISIONAL
      $datosAcuseRecibo = [
        // "celular" => "57" . $valueMensaje->celular,
        // "estado" => "1",
        // "fecha" => date("Y-m-d H:i:s"),
        "idConsolidado" => $data['insertConsolidado'],
        "cantidadMensajes" => $mensajes->cantidadMensajes,
        "valorMensajeIndividual" => $data['valorMensajeIndividual'],
      ];
      updateValorConsolidadoDod($this->conIntraChec, $datosAcuseRecibo);

      if (strtoupper($data['nombreBolsa']) === "UM") {
        $valueMensaje->celular = "57" . $valueMensaje->celular;
      }
      array_push($celulares, $valueMensaje->celular);
    }
    // DESCOMENTAR ESTA LINEA PARA ENVIAR LOS MENSAJES GENERALES
    $datosApiPhoneGeneral = [
      "celulares" => $celulares,
      "mensaje" => $mensajes->mensaje,
      "idConsolidado" => $data['insertConsolidado'],
      "cantidadMensajes" => $mensajes->cantidadMensajes,
      "nombreBolsa" => $data['nombreBolsa'],
      "valorMensajeIndividual" => $data['valorMensajeIndividual'],
    ];
    $apiPhone->sendMessageGeneral($datosApiPhoneGeneral);

    if (count($celulares) > 100) {
      sleep(count($celulares) * 0.2);
    } else {
      sleep(10);
    }
    // insertAcuseProvisional2($this->conIntraChec, $data['insertConsolidado']);

    updateSaldoBolsa($this->conDifusion, $this->conIntraChec, $data['insertConsolidado'], $data['idUsuario'], $data['nombreBolsa']);

    //LUEGO DE ENVIAR LOS MENSAJES ESTADO = ENVIADO
    $insertConsolidado2 = updateEstadoConsolidadoIndividual2($this->conIntraChec, $data['insertConsolidado'], 'Enviado', $cantidadReal);

    if ($insertConsolidado2->getModifiedCount()) {
      $response = 'Ok';
    }
    return $response;
  }

  public function getListSends()
  {
    return getListSends($this->conIntraChec);
  }

  public function getUsersNIU($NIUS)
  {
    return getUsersNIU($this->conDifusion, $NIUS);
  }

  public function getBolsaDinero()
  {
    return getBolsaDinero($this->conDifusion);
  }

  public function getBolsaMensajesUsuario($nombreBolsa)
  {
    return getBolsaMensajesUsuarioQuery($this->conDifusion, $nombreBolsa);
  }

  public function getDatosBolsas()
  {
    return getDatosBolsasQuery($this->conDifusion);
  }

  public function updateValorMensajeUnitarioBolsa($datosValorMensajeUnitario)
  {
    return updateValorMensajeUnitarioBolsaQuery($this->conDifusion, $datosValorMensajeUnitario);
  }

  public function adicionarSaldoBolsa($datosValorSaldoAdicionar)
  {
    return adicionarSaldoBolsaQuery($this->conDifusion, $datosValorSaldoAdicionar);
  }

  public function getDetalleEnvio($idDetalleEnvio)
  {
    $consolidado = getConsolidadoDetalleEnvio($this->conIntraChec, $idDetalleEnvio);
    //var_dump($consolidado);
    $result['idUsuario'] = $consolidado[0]->idUsuario;
    $result['nombreUsuario'] = $consolidado[0]->nombreUsuario;
    $result['fecha'] = $consolidado[0]->fechaEnvio;
    $result['motivoEnvio'] = $consolidado[0]->motivoEnvio;
    $result['metodoEnvio'] = $consolidado[0]->metodoEnvio;
    $result['tipoMensaje'] = $consolidado[0]->tipoMensaje;
    $result['estado'] = $consolidado[0]->estado;
    $result['valorMensajeIndividual'] = $consolidado[0]->valorMensajeIndividual;

    $cantidadMensajesEnviados = 0;
    $precioMensajes = 0;
    $individual = getIndividualDetalleEnvio($this->conIntraChec, $idDetalleEnvio);
    $result['cantidadMensajesEnviados'] = $individual[0]->total;
    $result['precioMensajes'] = $individual[0]->total * $result['valorMensajeIndividual'];

    $mensajes = [];
    $cantidadMensajesRecibidos = 0;
    $cantidadMensajesNoRecibidos = 0;
    $acuse = getAcuseDetalleEnvio($this->conIntraChec, $idDetalleEnvio);
    if (count($acuse) > 0) {
      foreach ($acuse as $key => $value) {
        $estado = '';
        if ((int) $value->estado == 1) {
          $estado = 'Entregado a destinatario';
          $cantidadMensajesRecibidos += (1 * $value->cantidadMensajes);
        } else {
          $estado = 'No se pudo entregar a destinatario';
          $cantidadMensajesNoRecibidos += (1 * $value->cantidadMensajes);
        }

        if ($result['metodoEnvio'] === 'Desde archivo') {
          array_push($mensajes, ['celular' => $value->celular, 'mensaje' => $value->mensaje, 'estado' => $estado]);
        } else {
          array_push($mensajes, ['cuenta' => $value->cuenta, 'nombre' => $value->nombre, 'celular' => $value->celular, 'mensaje' => $value->mensaje, 'estado' => $estado]);
        }
      }
    }
    $result['cantidadMensajesRecibidos'] = $cantidadMensajesRecibidos;
    $result['cantidadMensajesNoRecibidos'] = $cantidadMensajesNoRecibidos;
    $result['mensajes'] = $mensajes;

    return $result;
  }

  public function getLogCelularesDodExcluir($datosCelularesDod)
  {
    return getLogCelularesDodExcluirQuery($this->conIntraChec, $datosCelularesDod);
  }
  /* Monitoreo */


  //grafica invocar chatbot
  public function getResultadoInvocar($fechainicio, $fechafin)
  {
    $response = array();
    /*$result = filterResultadoInvocar($this->conDifusion, $fechainicio, $fechafin);
    $response['facebook'] = 0;
    $response['telegram'] = 0;
    $response['chatWeb'] = 0;
    foreach ($result as $key => $value) {
      switch ($value->SOURCE) {
        case 'facebook':
          $response['facebook'] += 1;
          break;
        case 'telegram':
          $response['telegram'] += 1;
          break;
        case 'chatWeb':
          $response['chatWeb'] += 1;
          break;
      }
    }
    return $response;*/

    $result = filterResultadoMenus($this->conHerokuChec, $fechainicio, $fechafin);
    $acceso_menu_filtrado =  $this->accesosMenu($result);
    $response['logMenuTotal'] = array_sum($acceso_menu_filtrado);
    return $response['logMenuTotal'];
  }

  //grafica invocar chatbot mes telegram
  public function getResultadoInvocarMesTelegram($fechainicio, $fechafin)
  {
    //$result = filterResultadoInvocarMes($this->conDifusion, $fechainicio, $fechafin);
    $result = filterResultadoInvocarMes2($this->conHerokuChec);
    $telegram = array();
    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $key => $value) {

      if (strcmp(strtolower($value->SOURCE), strtolower('telegram')) == 0) {
        $separarFecha = explode('-', $value->FECHA);
        $mes = $separarFecha[1];
        switch ($mes) {
          case '01':
            $response['enero'] += 1;
            break;
          case '02':
            $response['febrero'] += 1;
            break;
          case '03':
            $response['marzo'] += 1;
            break;
          case '04':
            $response['abril'] += 1;
            break;
          case '05':
            $response['mayo'] += 1;
            break;
          case '06':
            $response['junio'] += 1;
            break;
          case '07':
            $response['julio'] += 1;
            break;
          case '08':
            $response['agosto'] += 1;
            break;
          case '09':
            $response['septiembre'] += 1;
            break;
          case '10':
            $response['octubre'] += 1;
            break;
          case '11':
            $response['noviembre'] += 1;
            break;
          case '12':
            $response['diciembre'] += 1;
            break;
        }
      }
    }
    return $response;
  }

  //kpi consultas menu de acceso general
  public function getResultadoInvocarMesTotal()
  {
    //$result = filterResultadoInvocarMes($this->conDifusion, $fechainicio, $fechafin);
    $result = filterAccesosMenuAnio($this->conHerokuChec);
    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $key => $value) {
      $separarFecha = explode('-', $value->FECHA_RESULTADO);
      $mes = $separarFecha[1];
      switch ($mes) {
        case '01':
          $response['enero'] += 1;
          break;
        case '02':
          $response['febrero'] += 1;
          break;
        case '03':
          $response['marzo'] += 1;
          break;
        case '04':
          $response['abril'] += 1;
          break;
        case '05':
          $response['mayo'] += 1;
          break;
        case '06':
          $response['junio'] += 1;
          break;
        case '07':
          $response['julio'] += 1;
          break;
        case '08':
          $response['agosto'] += 1;
          break;
        case '09':
          $response['septiembre'] += 1;
          break;
        case '10':
          $response['octubre'] += 1;
          break;
        case '11':
          $response['noviembre'] += 1;
          break;
        case '12':
          $response['diciembre'] += 1;
          break;
      }
    }
    return $response;
  }

  //grafica invocar chatbot mes chatqeb
  public function getResultadoInvocarMesChatWeb($fechainicio, $fechafin)
  {
    //$result = filterResultadoInvocarMes($this->conDifusion, $fechainicio, $fechafin);
    $result = filterResultadoInvocarMes2($this->conHerokuChec);
    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $key => $value) {

      if (strcmp(strtolower($value->SOURCE), strtolower('chatWeb')) == 0) {
        $separarFecha = explode('-', $value->FECHA);
        $mes = $separarFecha[1];
        switch ($mes) {
          case '01':
            $response['enero'] += 1;
            break;
          case '02':
            $response['febrero'] += 1;
            break;
          case '03':
            $response['marzo'] += 1;
            break;
          case '04':
            $response['abril'] += 1;
            break;
          case '05':
            $response['mayo'] += 1;
            break;
          case '06':
            $response['junio'] += 1;
            break;
          case '07':
            $response['julio'] += 1;
            break;
          case '08':
            $response['agosto'] += 1;
            break;
          case '09':
            $response['septiembre'] += 1;
            break;
          case '10':
            $response['octubre'] += 1;
            break;
          case '11':
            $response['noviembre'] += 1;
            break;
          case '12':
            $response['diciembre'] += 1;
            break;
        }
      }
    }
    return $response;
  }

  //contar comentarios
  public function getComentario($fechainicio, $fechafin)
  {
    $result = countComentarios($this->conHerokuChec, $fechainicio, $fechafin);
    if (gettype($result) == 'boolean') {
      $numeroComentario = 0;
    } else {
      $numeroComentario = $result->n;
    }
    return $numeroComentario;
  }



  //CALIFICACIONES
  public function getCalificaciones($fechainicio, $fechafin)
  {
    $result = filterCalificaciones($this->conHerokuChec, $fechainicio, $fechafin);
    $response = array();
    $response['excelente'] = 0;
    $response['bueno'] = 0;
    $response['regular'] = 0;
    $response['malo'] = 0;
    $response['totalCalificacion'] = 0;
    foreach ($result as $key => $value) {
      switch (strtoupper($value->CALIFICACION)) {
        case 'EXCELENTE':
          $response['excelente'] += 1;
          break;
        case '😍 EXCELENTE':
          $response['excelente'] += 1;
          break;
        case 'BUENO':
          $response['bueno'] += 1;
          break;
        case '😁 BUENO':
          $response['bueno'] += 1;
          break;
        case 'REGULAR':
          $response['regular'] += 1;
          break;
        case '😐 REGULAR':
          $response['regular'] += 1;
          break;
        case 'MALO':
          $response['malo'] += 1;
          break;
        case '😣 MALO':
          $response['malo'] += 1;
          break;
      }
    }

    $response['totalCalificacion'] = $response['bueno'] + $response['regular'] + $response['malo'];

    return $response;
  }


  //grafica acceso menu
  public function getResultadoMenus($fechainicio, $fechafin)
  {
    //$resultTotal = filterResultadoMenusTotales($this->conDifusion, $fechainicio, $fechafin);
    $result = filterResultadoMenus($this->conHerokuChec, $fechainicio, $fechafin);
    //$acceso_menu_total =  $this->accesosMenu($resultTotal);
    $acceso_menu_filtrado =  $this->accesosMenu($result);
    $acceso_menu_total = $acceso_menu_filtrado['faltaEnergia'] + $acceso_menu_filtrado['copia'] + $acceso_menu_filtrado['vacantes'] + $acceso_menu_filtrado['pqr'] + $acceso_menu_filtrado['pagoFactura'] + $acceso_menu_filtrado['asesorRemoto'] + $acceso_menu_filtrado['puntosAtencion'] + $acceso_menu_filtrado['fraudes'];

    if ($acceso_menu_total <= 0) {
      $acceso_menu_filtrado['porcentajeFaltaEnergia'] = 0;
      $acceso_menu_filtrado['porcentajeCopiaFactura'] = 0;
      $acceso_menu_filtrado['porcentajeVacantes'] = 0;
      $acceso_menu_filtrado['porcentajePqr'] = 0;
    } else {
      $acceso_menu_filtrado['porcentajeFaltaEnergia'] = (float)number_format((($acceso_menu_filtrado['faltaEnergia'] * 100) / $acceso_menu_total), 1, '.', ',');
      $acceso_menu_filtrado['porcentajeCopiaFactura'] = (float)number_format((($acceso_menu_filtrado['copia'] * 100) / $acceso_menu_total), 1, '.', ',');
      $acceso_menu_filtrado['porcentajeVacantes'] = (float)number_format((($acceso_menu_filtrado['vacantes'] * 100) / $acceso_menu_total), 1, '.', ',');
      $acceso_menu_filtrado['porcentajePqr'] = (float)number_format((($acceso_menu_filtrado['pqr'] * 100) / $acceso_menu_total), 1, '.', ',');
    }

    return $acceso_menu_filtrado;
  }


  //acceso a menu (total y filtrados)
  public function accesosMenu($result)
  {

    /*
      asesor remoto
      reportar falla de eneiga
      copia de la factura
      paga tu factura
      pqr
      vancate
      puntos de atencion
      fraudes
    */
    $response = array();
    $response['faltaEnergia'] = 0; //-----------------------
    $response['copia'] = 0; //-----------------------
    $response['fraudes'] = 0; //-----------------------
    $response['puntosAtencion'] = 0; //-----------------------
    //$response['pagoLinea'] = 0;
    $response['vacantes'] = 0; //-----------------------
    $response['pqr'] = 0; //-----------------------
    //$response['otros'] = 0;
    $response['asesorRemoto'] = 0; //-----------------------
    $response['pagoFactura'] = 0; //-----------------------
    $response['menu_total'] = [];

    foreach ($result as $key => $value) {
      switch ($value->MENU) {
        case 'Falta de Energia':
          $response['faltaEnergia'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
          /*case 'Pago en Linea':
          $response['pagoLinea'] += 1; //-----------------------
          break;*/
        case 'Puntos de Atencion':
          $response['puntosAtencion'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
        case 'Vacantes':
          $response['vacantes'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
        case 'Pqr':
          $response['pqr'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
          /*case 'Otros motivos':
          $response['otros'] += 1; //-----------------------
          break;*/
        case 'Fraudes':
          $response['fraudes'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
        case 'Copia factura':
          $response['copia'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
        case 'Asesor remoto':
          $response['asesorRemoto'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
        case 'Pago factura':
          $response['pagoFactura'] += 1; //-----------------------
          $response['menu_total'] = $value;
          break;
      }
    }
    return $response;
  }

  //reportes hechos
  public function getResultado($fechainicio, $fechafin)
  {
    $response = array();
    $response['Nodo'] = 0;
    $response['Programada'] = 0;
    $response['Efectiva'] = 0;
    $response['SinIndis'] = 0;

    $response['SiCuenta'] = 0;
    $response['NoCuenta'] = 0;
    $response['SiCedula'] = 0;
    $response['NoCedula'] = 0;
    $response['SiNit'] = 0;
    $response['NoNit'] = 0;
    $response['SiDireccion'] = 0;
    $response['NoDireccion'] = 0;
    $response['SiNombre'] = 0;
    $response['NoNombre'] = 0;
    $response['SiTelefono'] = 0;
    $response['NoTelefono'] = 0;

    $report = filterReporte($this->conHerokuChec, $fechainicio, $fechafin);
    $response['SiReporte'] = $report->n;

    $result = filterResultado($this->conDifusion, $fechainicio, $fechafin);

    if (count($result) > 0) {
      foreach ($result as $key => $value) {
        switch ($value->TIPO_INDISPONIBILIDAD) {
          case 'Ningun Resultado de Consulta por Cuenta':
            $response['NoCuenta'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Cuenta':
            $response['SiCuenta'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Cedula':
            $response['NoCedula'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Cedula':
            $response['SiCedula'] += 1;
            break;
          case 'Ningun Resultado de Consulta por NIT':
            $response['NoNit'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por NIT':
            $response['SiNit'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Direccion':
            $response['NoDireccion'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Direccion':
            $response['SiDireccion'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Nombre':
            $response['NoNombre'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Nombre':
            $response['SiNombre'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Telefono':
            $response['NoTelefono'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Telefono':
            $response['SiTelefono'] += 1;
            break;
          case 'Indisponibilidad a nivel de Nodo':
            $response['Nodo'] += 1;
            break;
          case 'Suspension Programada':
            $response['Programada'] += 1;
            break;
          case 'Suspension Efectiva':
            $response['Efectiva'] += 1;
            break;
          case 'Sin Indisponibilidad Reportada':
            $response['SinIndis'] += 1;
            break;
        }
      }
    }
    return $response;
  }


  //Municipios
  public function getConsultasSegmentosUbicacionMunicipio($fechainicio, $fechafin)
  {
    $result = filterConsultasSegmentosUbicacionMunicipio($this->conDifusion, $fechainicio, $fechafin);
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {
        if (array_key_exists(0, $value)) {

          switch ($value[0]->SEGMENTO) {
            case 'Hogares':
              $responseSeg['hogares'] += 1;
              break;
            case 'Empresas':
              $responseSeg['empresas'] += 1;
              break;
            case 'Grandes Clientes':
              $responseSeg['grandesClientes'] += 1;
              break;
            case 'Gobierno':
              $responseSeg['gobierno'] += 1;
              break;
          }
          switch ($value[0]->UBICACION) {
            case 'U':
              $responseUbi['urbano'] += 1;
              break;
            case 'R':
              $responseUbi['rural'] += 1;
              break;
          }

          $municipio = strtoupper($value[0]->MUNICIPIO);
          if (isset($municipiosArray[$municipio])) {
            $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
            switch ($value[0]->UBICACION) {
              case 'U':
                $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
                break;
            }
          } else {
            $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
            $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);
            //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
            if (isset($municipiosArray[$cabecera])) {
              $municipiosArray[$cabecera] += 1;
              switch ($value[0]->UBICACION) {
                case 'U':
                  $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                  break;
                case 'R':
                  $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                  break;
              }
            }
          }
        }
      }
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;

      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
    }
    return $respuestaFinal;
  }

  public function getMunicipios()
  {

    $municipios = filterMunicipios($this->conHerokuChec);
    return $municipios;
  }


  //numero de reportes hechos por fecha-municipio-ubicacion
  public function getResportesFechaMunicipioUbicacion($fechainicio, $fechafin, $municipioUser, $ubicacionUser)
  {

    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = count(filterResportesGneral($this->conHerokuChec, $fechainicio, $fechafin));
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = 0;
      $consultas = filterResportesMunicipiosUbicacion($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        $municipioDb = $valor->MUNICIPIO;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          $resultado++;
        }
      }
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {

      $resultado = 0;
      $consultas = filterResportesMunicipiosUbicacion($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->UBICACION;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
          $resultado++;
        }
      }
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = 0;
      $consultas = filterResportesMunicipiosUbicacion($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->UBICACION;
        $municipioDb = $valor->MUNICIPIO;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
          $resultado++;
        }
      }
    }

    return $resultado;
  }

  //consultas totales por falta de energia por fecha-municipio-ubicacion
  public function getConsultasFechaMunicipioUbicacion($fechainicio, $fechafin, $municipioUser, $ubicacionUser)
  {

    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {

      $numConsultas = filterConsultasFaltaEnergia($this->conHerokuChec, $fechainicio, $fechafin);
      if ($numConsultas == false) {
        $resultado = 0;
      } else {
        $resultado = $numConsultas->n;
      }
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = 0;
      $consultas = filterConsultasFaltaEnergiaFiltrados($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $municipioDb = $valor->usuario[0]->MUNICIPIO;
          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            $resultado++;
          }
        }
      }
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {

      $resultado = 0;
      $consultas = filterConsultasFaltaEnergiaFiltrados($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $ubicacionDb = $valor->usuario[0]->UBICACION;
          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
            $resultado++;
          }
        }
      }
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = 0;
      $consultas = filterConsultasFaltaEnergiaFiltrados($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $municipioDb = $valor->usuario[0]->MUNICIPIO;
          $ubicacionDb = $valor->usuario[0]->UBICACION;
          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
            $resultado++;
          }
        }
      }
    }

    return $resultado;
  }


  //Obtener el numero total reportes hechos por fuentes(telegram chatweb), filtrando por fecha, municipio y ubicacion
  public function getResportesWebTelegram($fechainicio, $fechafin, $municipioUser, $ubicacionUser)
  {

    $contChatWeb = 0;
    $contTelegram = 0;
    $result = array();

    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = filterResportesSource($this->conHerokuChec, $fechainicio, $fechafin);
      $prueba = count($resultado);

      foreach ($resultado as $clave => $valor) {
        if (strcmp(strtolower('telegram'), strtolower($valor->SOURCE)) == 0) {
          $contTelegram++;
        } else if (strcmp(strtolower($valor->SOURCE), strtolower('chatWeb')) == 0 || strcmp(strtolower($valor->SOURCE), strtolower("")) == 0) {
          $contChatWeb++;
        }
      }

      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['reportesTotales'] = $contChatWeb + $contTelegram;
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesSourcesMuniUbicacion($this->conHerokuChec ,$fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        if (isset($valor[0])) {
          $municipioDb = $valor[0]->MUNICIPIO;
          $source =  $valor[0]->SOURCE;


          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
            $contTelegram++;
          } else if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
            $contChatWeb++;
          }
        }
      }
      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['reportesTotales'] = $contChatWeb + $contTelegram;
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesSourcesMuniUbicacion($this->conHerokuChec, $fechainicio, $fechafin);

      foreach ($consultas as $clave => $valor) {
        if (isset($valor[0])) {
          $ubicacionDb = $valor[0]->UBICACION;
          $source =  $valor[0]->SOURCE;


          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
            $contTelegram++;
          } else if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
            $contChatWeb++;
          }
        }
      }

      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['reportesTotales'] = $contChatWeb + $contTelegram;
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesSourcesMuniUbicacion($this->conHerokuChec, $fechainicio, $fechafin);
      foreach ($consultas as $clave => $valor) {
        if (isset($valor[0])) {
          $ubicacionDb = $valor[0]->UBICACION;
          $municipioDb = $valor[0]->MUNICIPIO;
          $source =  $valor[0]->SOURCE;

          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
            $contTelegram++;
          } else if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
            $contChatWeb++;
          }
        }
      }

      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['reportesTotales'] = $contChatWeb + $contTelegram;
    }

    return $result;
  }


  //Obtener los reportes de los usuarios de los meses, por fuente(telegram y chatweb)
  public function getResultadoInvocarMesTelegramFaltaEnergia($fechainicio, $fechafin, $municipioUser, $ubicacionUser)
  {

    $contChatWeb = 0;
    $contTelegram = 0;
    $result = array();


    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conHerokuChec, $fechainicio, $fechafin);
      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($resultado);
      $result['chatWebMes'] = $this->getMesFaltaEnergia($resultado, 'chatWeb', true);
      $result['telegramMes'] = $this->getMesFaltaEnergia($resultado, 'telegram', false);
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conHerokuChec, $fechainicio, $fechafin);
      $reportesPorMes = array();

      foreach ($consultas as $clave => $valor) {
        if (isset($valor)) {
          $municipioDb = $valor->MUNICIPIO;
          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMes, $consultas[$clave]);
          }
        }
      }
      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($reportesPorMes);
      $result['chatWebMes'] = $this->getMesFaltaEnergia($reportesPorMes, 'chatWeb', true);
      $result['telegramMes'] = $this->getMesFaltaEnergia($reportesPorMes, 'telegram', false);
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conHerokuChec, $fechainicio, $fechafin);
      $reportesPorUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (isset($valor)) {
          $ubicacionDb = $valor->UBICACION;

          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
            array_push($reportesPorUbicacion, $consultas[$clave]);
          }
        }
      }
      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($reportesPorUbicacion);
      $result['chatWebMes'] = $this->getMesFaltaEnergia($reportesPorUbicacion, 'chatWeb', true);
      $result['telegramMes'] = $this->getMesFaltaEnergia($reportesPorUbicacion, 'telegram', false);
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conHerokuChec, $fechainicio, $fechafin);
      $reportesPorMesUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (isset($valor)) {
          $ubicacionDb = $valor->UBICACION;
          $municipioDb = $valor->MUNICIPIO;

          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMesUbicacion, $consultas[$clave]);
          }
        }
      }

      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($reportesPorMesUbicacion);
      $result['chatWebMes'] = $this->getMesFaltaEnergia($reportesPorMesUbicacion, 'chatWeb', true);
      $result['telegramMes'] = $this->getMesFaltaEnergia($reportesPorMesUbicacion, 'telegram', false);
    }

    return $result;
  }

  //Obtener las consultas de los usuarios de los meses, por fuente(telegram y chatweb)
  public function getResultadoInvocarMesConsultasFaltaEnergia($fechainicio, $fechafin, $municipioUser, $ubicacionUser)
  {

    $contChatWeb = 0;
    $contTelegram = 0;
    $result = array();


    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      //filterConsultasFaltaEnergia
      //filterConsultasFaltaEnergiaFiltrados
      //$result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conDifusion, $fechainicio, $fechafin);
      $resultado = filterConsultasFaltaEnergiaMeses($this->conHerokuChec);
      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($resultado);
      //$result['chatWebMes'] = $this->getMesFaltaEnergiaTotal($resultado, 'chatWeb', true);
      //$result['telegramMes'] = $this->getMesFaltaEnergiaTotal($resultado, 'telegram', false);
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      //$result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conDifusion, $fechainicio, $fechafin);
      $consultas = filterConsultasSourcesMuniUbicacionFaltaEnergiaMeses($this->conHerokuChec, $fechainicio, $fechafin);
      $reportesPorMes = array();

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $municipioDb = $valor->usuario[0]->MUNICIPIO;
          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMes, $consultas[$clave]);
          }
        }
      }
      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($reportesPorMes);
      //$result['chatWebMes'] = $this->getMesFaltaEnergia($reportesPorMes, 'chatWeb', true);
      //$result['telegramMes'] = $this->getMesFaltaEnergia($reportesPorMes, 'telegram', false);
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      //$result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conDifusion, $fechainicio, $fechafin);
      $consultas = filterConsultasSourcesMuniUbicacionFaltaEnergiaMeses($this->conHerokuChec, $fechainicio, $fechafin);
      $reportesPorUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $ubicacionDb = $valor->usuario[0]->UBICACION;
          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
            array_push($reportesPorUbicacion, $consultas[$clave]);
          }
        }
      }
      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($reportesPorUbicacion);
      //$result['chatWebMes'] = $this->getMesFaltaEnergia($reportesPorUbicacion, 'chatWeb', true);
      //$result['telegramMes'] = $this->getMesFaltaEnergia($reportesPorUbicacion, 'telegram', false);
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      //$result['consulta'] = filterResportesSourcesMuniUbicacionTelegramFaltaEnergia2($this->conDifusion, $fechainicio, $fechafin);
      $consultas = filterConsultasSourcesMuniUbicacionFaltaEnergiaMeses($this->conHerokuChec, $fechainicio, $fechafin);
      $reportesPorMesUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $ubicacionDb = $valor->usuario[0]->UBICACION;
          $municipioDb = $valor->usuario[0]->MUNICIPIO;

          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMesUbicacion, $consultas[$clave]);
          }
        }
      }

      $result['reportesTotales'] = $this->getMesFaltaEnergiaTotal($reportesPorMesUbicacion);
      //$result['chatWebMes'] = $this->getMesFaltaEnergia($reportesPorMesUbicacion, 'chatWeb', true);
      //$result['telegramMes'] = $this->getMesFaltaEnergia($reportesPorMesUbicacion, 'telegram', false);
    }

    return $result;
  }


  //Identificar el número de reportes por feha-municipio-ubicación de cada mes(totales)
  public function getMesFaltaEnergiaTotal($result)
  {

    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $clave => $valor) {
      if (isset($valor)) {
        if (isset($valor->FECHA_REPORTE)) {
          $fechaDb =  $valor->FECHA_REPORTE;
        } else if ($valor->FECHA_CONSULTA) {
          $fechaDb =  $valor->FECHA_CONSULTA;
        }

        $separarFecha = explode('-', $fechaDb);
        $mes = $separarFecha[1];

        switch ($mes) {
          case '01':
            $response['enero'] += 1;
            break;
          case '02':
            $response['febrero'] += 1;
            break;
          case '03':
            $response['marzo'] += 1;
            break;
          case '04':
            $response['abril'] += 1;
            break;
          case '05':
            $response['mayo'] += 1;
            break;
          case '06':
            $response['junio'] += 1;
            break;
          case '07':
            $response['julio'] += 1;
            break;
          case '08':
            $response['agosto'] += 1;
            break;
          case '09':
            $response['septiembre'] += 1;
            break;
          case '10':
            $response['octubre'] += 1;
            break;
          case '11':
            $response['noviembre'] += 1;
            break;
          case '12':
            $response['diciembre'] += 1;
            break;
        }
      }
    }
    return $response;
  }

  //Identificar el número de reportes por feha-municipio-ubicación de cada mes(chatweb y telegram)
  public function getMesFaltaEnergia($result, $source, $flag)
  {

    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $clave => $valor) {
      if (isset($valor)) {
        $sourceDb =  $valor->SOURCE;
        $fechaDb =  $valor->FECHA_REPORTE;

        if (strcmp(strtolower($source), strtolower($sourceDb)) == 0 && $flag == false) {
          $separarFecha = explode('-', $fechaDb);
          $mes = $separarFecha[1];

          switch ($mes) {
            case '01':
              $response['enero'] += 1;
              break;
            case '02':
              $response['febrero'] += 1;
              break;
            case '03':
              $response['marzo'] += 1;
              break;
            case '04':
              $response['abril'] += 1;
              break;
            case '05':
              $response['mayo'] += 1;
              break;
            case '06':
              $response['junio'] += 1;
              break;
            case '07':
              $response['julio'] += 1;
              break;
            case '08':
              $response['agosto'] += 1;
              break;
            case '09':
              $response['septiembre'] += 1;
              break;
            case '10':
              $response['octubre'] += 1;
              break;
            case '11':
              $response['noviembre'] += 1;
              break;
            case '12':
              $response['diciembre'] += 1;
              break;
          }
        } else if (strcmp(strtolower($sourceDb), strtolower($source)) == 0 || strcmp(strtolower($sourceDb), strtolower("")) == 0 && $flag == true) {

          $separarFecha = explode('-', $fechaDb);
          $mes = $separarFecha[1];

          switch ($mes) {
            case '01':
              $response['enero'] += 1;
              break;
            case '02':
              $response['febrero'] += 1;
              break;
            case '03':
              $response['marzo'] += 1;
              break;
            case '04':
              $response['abril'] += 1;
              break;
            case '05':
              $response['mayo'] += 1;
              break;
            case '06':
              $response['junio'] += 1;
              break;
            case '07':
              $response['julio'] += 1;
              break;
            case '08':
              $response['agosto'] += 1;
              break;
            case '09':
              $response['septiembre'] += 1;
              break;
            case '10':
              $response['octubre'] += 1;
              break;
            case '11':
              $response['noviembre'] += 1;
              break;
            case '12':
              $response['diciembre'] += 1;
              break;
          }
        }
      }
    }

    return $response;
  }

  //consultas por criterio, filtrado por fecha-municipio-ubicacion
  public function getConsultasSegmentosUbicacionMunicipioFaltaEneria($fechainicio, $fechafin)
  {
    $response = array();
    $response['Nodo'] = 0;
    $response['Programada'] = 0;
    $response['Efectiva'] = 0;
    $response['SinIndis'] = 0;

    $response['SiCuenta'] = 0;
    $response['NoCuenta'] = 0;
    $response['SiCedula'] = 0;
    $response['NoCedula'] = 0;
    $response['SiNit'] = 0;
    $response['NoNit'] = 0;
    $response['SiDireccion'] = 0;
    $response['NoDireccion'] = 0;
    $response['SiNombre'] = 0;
    $response['NoNombre'] = 0;
    $response['SiTelefono'] = 0;
    $response['NoTelefono'] = 0;

    $report = filterReporte($this->conHerokuChec, $fechainicio, $fechafin);
    $response['SiReporte'] = $report->n;

    $result = filterResultado($this->conHerokuChec, $fechainicio, $fechafin);

    if (count($result) > 0) {
      foreach ($result as $key => $value) {
        switch ($value->TIPO_INDISPONIBILIDAD) {
          case 'Ningun Resultado de Consulta por Cuenta':
            $response['NoCuenta'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Cuenta':
            $response['SiCuenta'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Cedula':
            $response['NoCedula'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Cedula':
            $response['SiCedula'] += 1;
            break;
          case 'Ningun Resultado de Consulta por NIT':
            $response['NoNit'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por NIT':
            $response['SiNit'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Direccion':
            $response['NoDireccion'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Direccion':
            $response['SiDireccion'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Nombre':
            $response['NoNombre'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Nombre':
            $response['SiNombre'] += 1;
            break;
          case 'Ningun Resultado de Consulta por Telefono':
            $response['NoTelefono'] += 1;
            break;
          case 'Mas de 1 Resultado de Consulta por Telefono':
            $response['SiTelefono'] += 1;
            break;
          case 'Indisponibilidad a nivel de Nodo':
            $response['Nodo'] += 1;
            break;
          case 'Suspension Programada':
            $response['Programada'] += 1;
            break;
          case 'Suspension Efectiva':
            $response['Efectiva'] += 1;
            break;
          case 'Sin Indisponibilidad Reportada':
            $response['SinIndis'] += 1;
            break;
        }
      }
    }
    return $response;
  }

  //reportes de falta de energia por municipio, filtrado por fecha y ubicacion
  public function reportesMunicipioFaltaDeEnergia($fechainicio, $fechafin, $municipioUser, $ubicacionUser, $consulta)
  {

    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      //$resultado = filterResportesSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $resultado = $consulta;
      $resultadoTotales = filterResportesSourcesMuniUbicacion2($this->conHerokuChec, $fechainicio, $fechafin, false);
      //$resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes2($resultadoTotales);
      $resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes($resultadoTotales);

      return  $this->getConsultasSegmentosUbicacionMunicipioResportes6($resultado, $resultadosTotalesFiltrados, true);
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      //$consultas = filterResportesSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $consultas = $consulta;
      $reportesPorMunicipio = array();

      foreach ($consultas as $clave => $valor) {
        if (isset($valor)) {
          $municipioDb = $valor->MUNICIPIO;
          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMunicipio, $consultas[$clave]);
          }
        }
      }
      $resultadoTotales = filterResportesSourcesMuniUbicacion2($this->conHerokuChec, $fechainicio, $fechafin, false);
      $resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes2($resultadoTotales);
      return $this->getConsultasSegmentosUbicacionMunicipioResportes6($reportesPorMunicipio, $resultadosTotalesFiltrados, true);
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      //$consultas = filterResportesSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $consultas = $consulta;
      $reportesPorUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (isset($valor)) {
          $ubicacionDb = $valor->UBICACION;

          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
            array_push($reportesPorUbicacion, $consultas[$clave]);
          }
        }
      }
      $resultadoTotales = filterResportesSourcesMuniUbicacion2($this->conHerokuChec, $fechainicio, $fechafin, false);
      $resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes2($resultadoTotales);
      return  $this->getConsultasSegmentosUbicacionMunicipioResportes6($reportesPorUbicacion, $resultadosTotalesFiltrados, true);
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      //$consultas = filterResportesSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $consultas = $consulta;
      $reportesPorMunicipioUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (isset($valor)) {
          $ubicacionDb = $valor->UBICACION;
          $municipioDb = $valor->MUNICIPIO;

          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMunicipioUbicacion, $consultas[$clave]);
          }
        }
      }
      $resultadoTotales = filterResportesSourcesMuniUbicacion2($this->conHerokuChec, $fechainicio, $fechafin, false);
      $resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes2($resultadoTotales);
      return  $this->getConsultasSegmentosUbicacionMunicipioResportes6($reportesPorMunicipioUbicacion, $resultadosTotalesFiltrados, true);
    }
  }

  //consultas de falta de energia por municipio, filtrado por fecha y ubicacion
  public function consultasMunicipioFaltaDeEnergia($fechainicio, $fechafin, $municipioUser, $ubicacionUser, $reportes)
  {

    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $porcReportes = [];
      $resultado = filterConsultasSourcesMuniUbicacion($this->conHerokuChec, $fechainicio, $fechafin);
      //$resultado = $consulta;
      //$resultadoTotales = filterConsultasSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin, false);
      return $this->getConsultasSegmentosUbicacionMunicipioResportes7($resultado, $reportes);
      //return  $this->getConsultasSegmentosUbicacionMunicipioResportes6($resultado, $resultadosTotalesFiltrados, true);
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterConsultasSourcesMuniUbicacion($this->conHerokuChec, $fechainicio, $fechafin);
      //$consultas = $consulta;
      $reportesPorMunicipio = array();

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $municipioDb = $valor->usuario[0]->MUNICIPIO;
          if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMunicipio, $consultas[$clave]);
          }
        }
      }

      //$resultadoTotales = filterConsultasSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin, false);
      return $this->getConsultasSegmentosUbicacionMunicipioResportes7($reportesPorMunicipio, $reportes);
      //return $this->getConsultasSegmentosUbicacionMunicipioResportes6($reportesPorMunicipio, $resultadosTotalesFiltrados, true);
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterConsultasSourcesMuniUbicacion($this->conHerokuChec, $fechainicio, $fechafin);
      //$consultas = $consulta;
      $reportesPorUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $ubicacionDb = $valor->usuario[0]->UBICACION;
          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
            array_push($reportesPorUbicacion, $consultas[$clave]);
          }
        }
      }
      //$resultadoTotales = filterConsultasSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin, false);
      return $this->getConsultasSegmentosUbicacionMunicipioResportes7($reportesPorUbicacion, $reportes);
      //return  $this->getConsultasSegmentosUbicacionMunicipioResportes6($reportesPorUbicacion, $resultadosTotalesFiltrados, true);
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterConsultasSourcesMuniUbicacion($this->conHerokuChec, $fechainicio, $fechafin);
      //$consultas = $consulta;
      $reportesPorMunicipioUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        if (count($valor->usuario) > 0) {
          $municipioDb = $valor->usuario[0]->MUNICIPIO;
          $ubicacionDb = $valor->usuario[0]->UBICACION;
          if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
            array_push($reportesPorMunicipioUbicacion, $consultas[$clave]);
          }
        }
      }
      //$resultadoTotales = filterConsultasSourcesMuniUbicacion($this->conDifusion, $fechainicio, $fechafin, false);
      return $this->getConsultasSegmentosUbicacionMunicipioResportes7($reportesPorMunicipioUbicacion, $reportes);
      //return  $this->getConsultasSegmentosUbicacionMunicipioResportes6($reportesPorMunicipioUbicacion, $resultadosTotalesFiltrados, true);
    }
  }



  //obtener municipios(u,r), ubiacion, reportesPorMes
  public function getConsultasSegmentosUbicacionMunicipioResportes($result, $resulTotal = '', $flag = false)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0, "MARIQUITA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {
        switch ($value->SEGMENTO) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->UBICACION) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->MUNICIPIO);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->UBICACION) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
          $cabecera = strtoupper($cabecera->MUNICIPIO_CABECERA);
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
          if (isset($municipiosArray[$cabecera])) {
            $municipiosArray[$cabecera] += 1;
            switch ($value->UBICACION) {
              case 'U':
                $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                break;
            }
          }
        }
      }


      if ($flag) {
        $objTable = array();
        $cont = 0;
        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }




      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;


      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
    }
    return $respuestaFinal;
  }

  //obtener municipios(u,r), ubiacion, reportesPorMes
  public function getConsultasSegmentosUbicacionMunicipioResportes7($result, $reportes)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;
    $objTable = [];
    $cont = 0;
    $porceReportes = [];
    $responseporcenMunicipioUrbano = [];
    $responseporcenMunicipioRural = [];

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0, "MARIQUITA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {
        switch ($value->SEGMENTO) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->UBICACION) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->MUNICIPIO);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->UBICACION) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $cabecera = filterConsultaMunicipio($this->conHerokuChec, $municipio);
          if (gettype($cabecera) == 'array') {
            $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);
            if (isset($municipiosArray[$cabecera])) {
              $municipiosArray[$cabecera] += 1;
              switch ($value->UBICACION) {
                case 'U':
                  $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                  break;
                case 'R':
                  $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                  break;
              }
            }
          } else {
            $cabecera = strtoupper($cabecera->MUNICIPIO_CABECERA);
            if (isset($municipiosArray[$cabecera])) {
              $municipiosArray[$cabecera] += 1;
              switch ($value->UBICACION) {
                case 'U':
                  $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                  break;
                case 'R':
                  $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                  break;
              }
            }
          }
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
        }
      }


      foreach ($reportes['municipio'] as $clave1 => $valor1) {
        foreach ($municipiosArray as $clave2 => $valor2) {
          foreach ($responseMunicipioUrbano as $clave3 => $valor3) {
            foreach ($responseMunicipioRural as $clave4 => $valor4) {
              if ($clave1 == $clave2 && $clave1 == $clave3 && $clave1 == $clave4) {

                $objTable[$cont]['municipio'] = $clave1;

                $objTable[$cont]['num'] = $valor2;

                if ($valor2 > 0) {
                  //$porceReportes[$clave1] = (float)number_format((($valor1 * 100) / $valor2), 1, '.', ',');
                  $objTable[$cont]['porReporte'] = (float)number_format((($valor1 * 100) / $valor2), 1, '.', ',');
                } else {
                  //$porceReportes[$clave1] = 0;
                  $objTable[$cont]['porReporte'] = 0;
                }

                if ($valor3 > 0) {
                  //$responseporcenMunicipioUrbano[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
                  $objTable[$cont]['porUrbano'] = (float)number_format((($valor3 * 100) / $valor2), 1, '.', ',');
                } else {
                  //$responseporcenMunicipioUrbano[$clave1] = 0;
                  $objTable[$cont]['porUrbano'] = 0;
                }

                if ($valor4 > 0) {
                  //$responseporcenMunicipioRural[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
                  $objTable[$cont]['porRural'] = (float)number_format((($valor4 * 100) / $valor2), 1, '.', ',');
                } else {
                  //$responseporcenMunicipioRural[$clave1] = 0;
                  $objTable[$cont]['porRural'] = 0;
                }

                $cont = $cont + 1;
              }
            }
          }
        }
      }

      /*foreach ($municipiosArray as $clave1 => $valor1) {
        foreach ($responseMunicipioUrbano as $clave2 => $valor2) {
          if ($clave1 == $clave2) {
            if ($valor2 > 0) {
              $responseporcenMunicipioUrbano[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
            } else {
              $responseporcenMunicipioUrbano[$clave1] = 0;
            }
          }
        }
      }
      foreach ($municipiosArray as $clave1 => $valor1) {
        foreach ($responseMunicipioRural as $clave2 => $valor2) {
          if ($clave1 == $clave2) {
            if ($valor2 > 0) {
              $responseporcenMunicipioRural[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
            } else {
              $responseporcenMunicipioRural[$clave1] = 0;
            }
          }
        }
      }*/
      $volume = [];
      foreach ($objTable as $key => $row) {
        $volume[$key]  = $row['num'];
      }

      array_multisort($volume, SORT_DESC, $objTable);

      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['dataTable'] = $objTable;
      $respuestaFinal['municipio'] = $municipiosArray;


      //$respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      //$respuestaFinal['municipioRural'] = $responseMunicipioRural;
      //$respuestaFinal['porcenmunicipioUrbano'] = $responseporcenMunicipioUrbano;
      //$respuestaFinal['porcenmunicipioRural'] = $responseporcenMunicipioRural;
      //$respuestaFinal['porcenReporte'] = $porceReportes;

      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['dataTable'] = $objTable;
      $respuestaFinal['municipio'] = $municipiosArray;
      //$respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      //$respuestaFinal['municipioRural'] = $responseMunicipioRural;
      //$respuestaFinal['porcenmunicipioUrbano'] = $responseporcenMunicipioUrbano;
      //$respuestaFinal['porcenmunicipioRural'] = $responseporcenMunicipioRural;
      //$respuestaFinal['porcenReporte'] = $porceReportes;
    }
    return $respuestaFinal;
  }

  public function sort_by_orden($a, $b)
  {
    return $a['num'] - $b['num'];
  }

  //obtener municipios(u,r), ubiacion, reportesPorMes
  public function getConsultasSegmentosUbicacionMunicipioResportes2($result, $resulTotal = '', $flag = false)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {
      foreach ($result as $key => $value) {
        switch ($value->SEGMENTO) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->UBICACION) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->MUNICIPIO);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->UBICACION) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
          $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
          if (isset($municipiosArray[$cabecera])) {
            $municipiosArray[$cabecera] += 1;
            switch ($value->UBICACION) {
              case 'U':
                $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                break;
            }
          }
        }
      }


      if ($flag) {
        $objTable = array();
        $cont = 0;
        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }




      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;


      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
    }
    return $respuestaFinal;
  }

  //obtener municipios(u,r), ubiacion, reportesPorMes1qwerty
  public function getConsultasSegmentosUbicacionMunicipioResportes4($result, $resulTotal = '', $flag = false)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;
    $objTable = array();
    $cont = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "NARI�O" =>0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {
        switch ($value->DATA[0]->SEGMENTO) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->DATA[0]->UBICACION) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->DATA[0]->MUNICIPIO);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->DATA[0]->UBICACION) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $prueba = strtoupper('Nari�o');
          $cabecera = filterConsultaMunicipio($this->conHerokuChec, $municipio);
          $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
          if (isset($municipiosArray[$cabecera])) {
            $municipiosArray[$cabecera] += 1;
            switch ($value->DATA[0]->UBICACION) {
              case 'U':
                $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                break;
            }
          }
        }
      }


      /*if ($flag) {

        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }*/

      foreach ($municipiosArray as $clave2 => $valor2) {
        foreach ($responseMunicipioUrbano as $clave3 => $valor3) {
          foreach ($responseMunicipioRural as $clave4 => $valor4) {
            if ($clave2 == $clave3 && $clave2 == $clave4) {

              $totalMunicipios = array_sum($municipiosArray);
              $objTable[$cont]['municipio'] = $clave2;

              $objTable[$cont]['num'] = $valor2;

              if ($valor2 > 0) {
                //$porceReportes[$clave1] = (float)number_format((($valor1 * 100) / $valor2), 1, '.', ',');
                $objTable[$cont]['porcon'] = (float)number_format((($valor2 * 100) / $totalMunicipios), 1, '.', ',');
              } else {
                //$porceReportes[$clave1] = 0;
                $objTable[$cont]['porcon'] = 0;
              }

              if ($valor3 > 0) {
                //$responseporcenMunicipioUrbano[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
                $objTable[$cont]['porUrbano'] = (float)number_format((($valor3 * 100) / $valor2), 1, '.', ',');
              } else {
                //$responseporcenMunicipioUrbano[$clave1] = 0;
                $objTable[$cont]['porUrbano'] = 0;
              }

              if ($valor4 > 0) {
                //$responseporcenMunicipioRural[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
                $objTable[$cont]['porcRural'] = (float)number_format((($valor4 * 100) / $valor2), 1, '.', ',');
              } else {
                //$responseporcenMunicipioRural[$clave1] = 0;
                $objTable[$cont]['porcRural'] = 0;
              }

              $cont = $cont + 1;
            }
          }
        }
      }
      $volume = [];
      foreach ($objTable as $key => $row) {
        $volume[$key]  = $row['num'];
      }

      array_multisort($volume, SORT_DESC, $objTable);

      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
      $respuestaFinal['dataTable'] = $objTable;



      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
      $respuestaFinal['dataTable'] = $objTable;
    }
    return $respuestaFinal;
  }


  //obtener municipios(u,r), ubiacion, reportesPorMes1qwerty
  public function getConsultasSegmentosUbicacionMunicipioResportes5($result, $resulTotal = '', $flag = false)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {

        switch ($value->SEGMENTO[0]) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->UBICACION[0]) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->MUNICIPIO[0]);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->UBICACION[0]) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
          $cabecera = strtoupper($cabecera->MUNICIPIO_CABECERA);
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
          if (isset($municipiosArray[$cabecera])) {
            $municipiosArray[$cabecera] += 1;
            switch ($value->UBICACION[0]) {
              case 'U':
                $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                break;
            }
          }
        }
      }


      if ($flag) {
        $objTable = array();
        $cont = 0;
        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }




      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;


      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
    }
    return $respuestaFinal;
  }
  //obtener municipios(u,r), ubiacion, reportesPorMes1qwerty
  public function getConsultasSegmentosUbicacionMunicipioResportes3($result, $resulTotal = '', $flag = false)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {

        switch ($value->SEGMENTO) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->UBICACION) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->MUNICIPIO);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->UBICACION) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
          $cabecera = strtoupper($cabecera->MUNICIPIO_CABECERA);
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
          if (isset($municipiosArray[$cabecera])) {
            $municipiosArray[$cabecera] += 1;
            switch ($value->UBICACION) {
              case 'U':
                $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                break;
            }
          }
        }
      }


      if ($flag) {
        $objTable = array();
        $cont = 0;
        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }




      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;


      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
    }
    return $respuestaFinal;
  }


  //obtener municipios(u,r), ubiacion, reportesPorMes
  public function getConsultasSegmentosUbicacionMunicipioResportes6($result, $resulTotal = '', $flag = false)
  {
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;

    if (count($result) > 0) {

      foreach ($result as $key => $value) {
        switch ($value->SEGMENTO) {
          case 'Hogares':
            $responseSeg['hogares'] += 1;
            break;
          case 'Empresas':
            $responseSeg['empresas'] += 1;
            break;
          case 'Grandes Clientes':
            $responseSeg['grandesClientes'] += 1;
            break;
          case 'Gobierno':
            $responseSeg['gobierno'] += 1;
            break;
        }
        switch ($value->UBICACION) {
          case 'U':
            $responseUbi['urbano'] += 1;
            break;
          case 'R':
            $responseUbi['rural'] += 1;
            break;
        }

        $municipio = strtoupper($value->MUNICIPIO);
        if (isset($municipiosArray[$municipio])) {
          $municipiosArray[$municipio] = $municipiosArray[$municipio] + 1;
          switch ($value->UBICACION) {
            case 'U':
              $responseMunicipioUrbano[$municipio] = $responseMunicipioUrbano[$municipio] + 1;
              break;
            case 'R':
              $responseMunicipioRural[$municipio] = $responseMunicipioRural[$municipio] + 1;
              break;
          }
        } else {
          $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
          if (gettype($cabecera) == 'array') {
            $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);
          } else {
            $cabecera = strtoupper($cabecera->MUNICIPIO_CABECERA);
          }
          //$cabecera = filterConsultaMunicipio($this->conMongo, $value[0]->MUNICIPIO);
          if (isset($municipiosArray[$cabecera])) {
            $municipiosArray[$cabecera] += 1;
            switch ($value->UBICACION) {
              case 'U':
                $responseMunicipioUrbano[$cabecera] = $responseMunicipioUrbano[$cabecera] + 1;
                break;
              case 'R':
                $responseMunicipioRural[$cabecera] = $responseMunicipioRural[$cabecera] + 1;
                break;
            }
          }
        }
      }


      if ($flag) {
        $objTable = array();
        $cont = 0;
        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }




      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;


      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
    }
    return $respuestaFinal;
  }
  //Obtener el numero total consultas de copia factura hechos por fuentes(telegram chatweb), filtrando por fecha, municipio y ubicacion
  public function getConsultasCopiaFacturaWebTelegram($fechainicio, $fechafin, $municipioUser, $ubicacionUser)
  {

    $contChatWeb = 0;
    $contTelegram = 0;
    $contTotal = 0;
    $result = array();

    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $resultado = filterResportesCopiaFactura($this->conHerokuChec, $fechainicio, $fechafin);
      $resultadoAnual = filterResportesCopiaFacturaAnual($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = $resultado;
      $resultado_fuente = array();
      $resultado_fuenteAnual = array();


      foreach ($resultado as $clave => $valor) {
        if (strcmp(strtolower('telegram'), strtolower($valor->SOURCE)) == 0) {
          array_push($resultado_fuente, $valor);
          $contTelegram++;
        } else if (strcmp(strtolower($valor->SOURCE), strtolower('chatWeb')) == 0) {
          array_push($resultado_fuente, $valor);
          $contChatWeb++;
        }
      }

      foreach ($resultadoAnual as $clave => $valor) {
        if (strcmp(strtolower('telegram'), strtolower($valor->SOURCE)) == 0) {
          array_push($resultado_fuenteAnual, $valor);
        } else if (strcmp(strtolower($valor->SOURCE), strtolower('chatWeb')) == 0) {
          array_push($resultado_fuenteAnual, $valor);
        }
      }

      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['copia_factura_total'] = count($resultado);
      $result['meses_chatweb'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'chatWeb', true);
      $result['meses_telegram'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'telegram', false);
      $result['meses_total'] = $this->getMesFaltaEnergiaCopiaFactura2($resultadoAnual, 'total', '');
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesCopiaFactura($this->conHerokuChec, $fechainicio, $fechafin, $municipioUser);
      $resultadoAnual = filterResportesCopiaFacturaAnual($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = $consultas;
      $resultado_fuente = array();
      $resultado_fuenteAnual = array();
      $resultado_fuenteAnual_total = array();


      foreach ($consultas as $clave => $valor) {
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
          array_push($resultado_fuente, $valor);
          $contTelegram++;
        } else if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
          array_push($resultado_fuente, $valor);
          $contChatWeb++;
        }
      }


      foreach ($resultadoAnual as $clave => $valor) {
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
          array_push($resultado_fuenteAnual, $valor);
        } else if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
          array_push($resultado_fuenteAnual, $valor);
        }
      }

      foreach ($consultas as $clave => $valor) {
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          $contTotal++;
        }
      }

      foreach ($resultadoAnual as $clave => $valor) {
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          array_push($resultado_fuenteAnual_total, $valor);
        }
      }

      $result['copia_factura_total'] = $contTotal;
      $result['meses_total'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual_total, 'total', '');
      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['meses_chatweb'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'chatWeb', true);
      $result['meses_telegram'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'telegram', false);
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesCopiaFactura($this->conHerokuChec, $fechainicio, $fechafin);
      $resultadoAnual = filterResportesCopiaFacturaAnual($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = $consultas;
      $resultado_fuente = array();
      $resultado_fuenteAnual = array();
      $resultado_fuenteAnual_total = array();


      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
          array_push($resultado_fuente, $valor);
          $contTelegram++;
        } else if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
          array_push($resultado_fuente, $valor);
          $contChatWeb++;
        }
      }

      foreach ($resultadoAnual as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
          array_push($resultado_fuenteAnual, $valor);
        } else if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
          array_push($resultado_fuenteAnual, $valor);
        }
      }

      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
          $contTotal++;
        }
      }

      foreach ($resultadoAnual as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
          array_push($resultado_fuenteAnual_total, $valor);
        }
      }

      $result['copia_factura_total'] = $contTotal;
      $result['meses_total'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual_total, 'total', '');
      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['meses_chatweb'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'chatWeb', true);
      $result['meses_telegram'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'telegram', false);
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      $consultas = filterResportesCopiaFactura($this->conHerokuChec, $fechainicio, $fechafin, $municipioUser, $ubicacionUser);
      $resultadoAnual = filterResportesCopiaFacturaAnual($this->conHerokuChec, $fechainicio, $fechafin);
      $result['consulta'] = $consultas;
      $resultado_fuente = array();
      $resultado_fuenteAnual = array();
      $resultado_fuenteAnual_total = array();


      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
          array_push($resultado_fuente, $valor);
          $contTelegram++;
        } else if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
          array_push($resultado_fuente, $valor);
          $contChatWeb++;
        }
      }

      foreach ($resultadoAnual as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && strcmp(strtolower('telegram'), strtolower($source)) == 0) {
          array_push($resultado_fuenteAnual, $valor);
        } else if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0 && (strcmp(strtolower($source), strtolower('chatWeb')) == 0 || strcmp(strtolower($source), strtolower("")) == 0)) {
          array_push($resultado_fuenteAnual, $valor);
        }
      }

      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          $contTotal++;
        }
      }

      foreach ($resultadoAnual as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        $source =  $valor->SOURCE;
        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          array_push($resultado_fuenteAnual_total, $valor);
        }
      }

      $result['copia_factura_total'] = $contTotal;
      $result['meses_total'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual_total, 'total', '');
      $result['telegram'] = $contTelegram;
      $result['chatWeb'] = $contChatWeb;
      $result['meses_chatweb'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'chatWeb', true);
      $result['meses_telegram'] = $this->getMesFaltaEnergiaCopiaFactura2($resultado_fuenteAnual, 'telegram', false);
    }

    return $result;
  }


  //Identificar el número de copia de factura  por feha-municipio-ubicación de cada mes(chatweb y telegram)
  public function getMesFaltaEnergiaCopiaFactura2($result, $source, $flag)
  {

    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $clave => $valor) {
      $sourceDb =  $valor->SOURCE;
      $fechaDb =  $valor->FECHA_RESULTADO;

      if (strcmp(strtolower($source), strtolower($sourceDb)) == 0 && $flag == false) {
        $separarFecha = explode('-', $fechaDb);
        $mes = $separarFecha[1];

        switch ($mes) {
          case '01':
            $response['enero'] += 1;
            break;
          case '02':
            $response['febrero'] += 1;
            break;
          case '03':
            $response['marzo'] += 1;
            break;
          case '04':
            $response['abril'] += 1;
            break;
          case '05':
            $response['mayo'] += 1;
            break;
          case '06':
            $response['junio'] += 1;
            break;
          case '07':
            $response['julio'] += 1;
            break;
          case '08':
            $response['agosto'] += 1;
            break;
          case '09':
            $response['septiembre'] += 1;
            break;
          case '10':
            $response['octubre'] += 1;
            break;
          case '11':
            $response['noviembre'] += 1;
            break;
          case '12':
            $response['diciembre'] += 1;
            break;
        }
      } else if (strcmp(strtolower($sourceDb), strtolower($source)) == 0 || strcmp(strtolower($sourceDb), strtolower("")) == 0 && $flag == true) {

        $separarFecha = explode('-', $fechaDb);
        $mes = $separarFecha[1];

        switch ($mes) {
          case '01':
            $response['enero'] += 1;
            break;
          case '02':
            $response['febrero'] += 1;
            break;
          case '03':
            $response['marzo'] += 1;
            break;
          case '04':
            $response['abril'] += 1;
            break;
          case '05':
            $response['mayo'] += 1;
            break;
          case '06':
            $response['junio'] += 1;
            break;
          case '07':
            $response['julio'] += 1;
            break;
          case '08':
            $response['agosto'] += 1;
            break;
          case '09':
            $response['septiembre'] += 1;
            break;
          case '10':
            $response['octubre'] += 1;
            break;
          case '11':
            $response['noviembre'] += 1;
            break;
          case '12':
            $response['diciembre'] += 1;
            break;
        }
      } else if ($source == 'total') {

        $separarFecha = explode('-', $fechaDb);
        $mes = $separarFecha[1];

        switch ($mes) {
          case '01':
            $response['enero'] += 1;
            break;
          case '02':
            $response['febrero'] += 1;
            break;
          case '03':
            $response['marzo'] += 1;
            break;
          case '04':
            $response['abril'] += 1;
            break;
          case '05':
            $response['mayo'] += 1;
            break;
          case '06':
            $response['junio'] += 1;
            break;
          case '07':
            $response['julio'] += 1;
            break;
          case '08':
            $response['agosto'] += 1;
            break;
          case '09':
            $response['septiembre'] += 1;
            break;
          case '10':
            $response['octubre'] += 1;
            break;
          case '11':
            $response['noviembre'] += 1;
            break;
          case '12':
            $response['diciembre'] += 1;
            break;
        }
      }
    }

    return $response;
  }


  //Identificar el número de copia de factura  por feha-municipio-ubicación de cada mes(chatweb y telegram)
  public function getMesFaltaEnergiaCopiaFactura($result, $source, $flag)
  {

    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($result as $clave => $valor) {
      if (isset($valor[0])) {
        $sourceDb =  $valor[0]->SOURCE;
        $fechaDb =  $valor[0]->FECHA_RESULTADO;

        if (strcmp(strtolower($source), strtolower($sourceDb)) == 0 && $flag == false) {
          $separarFecha = explode('-', $fechaDb);
          $mes = $separarFecha[1];

          switch ($mes) {
            case '01':
              $response['enero'] += 1;
              break;
            case '02':
              $response['febrero'] += 1;
              break;
            case '03':
              $response['marzo'] += 1;
              break;
            case '04':
              $response['abril'] += 1;
              break;
            case '05':
              $response['mayo'] += 1;
              break;
            case '06':
              $response['junio'] += 1;
              break;
            case '07':
              $response['julio'] += 1;
              break;
            case '08':
              $response['agosto'] += 1;
              break;
            case '09':
              $response['septiembre'] += 1;
              break;
            case '10':
              $response['octubre'] += 1;
              break;
            case '11':
              $response['noviembre'] += 1;
              break;
            case '12':
              $response['diciembre'] += 1;
              break;
          }
        } else if (strcmp(strtolower($sourceDb), strtolower($source)) == 0 || strcmp(strtolower($sourceDb), strtolower("")) == 0 && $flag == true) {

          $separarFecha = explode('-', $fechaDb);
          $mes = $separarFecha[1];

          switch ($mes) {
            case '01':
              $response['enero'] += 1;
              break;
            case '02':
              $response['febrero'] += 1;
              break;
            case '03':
              $response['marzo'] += 1;
              break;
            case '04':
              $response['abril'] += 1;
              break;
            case '05':
              $response['mayo'] += 1;
              break;
            case '06':
              $response['junio'] += 1;
              break;
            case '07':
              $response['julio'] += 1;
              break;
            case '08':
              $response['agosto'] += 1;
              break;
            case '09':
              $response['septiembre'] += 1;
              break;
            case '10':
              $response['octubre'] += 1;
              break;
            case '11':
              $response['noviembre'] += 1;
              break;
            case '12':
              $response['diciembre'] += 1;
              break;
          }
        }
      }
    }

    return $response;
  }

  //consultas de copia factura por municipio, filtrado por fecha y ubicacion
  public function consultasMunicipioCopiaFactura($fechainicio, $fechafin, $municipioUser, $ubicacionUser, $consulta)
  {
    if (strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {

      //$resultado = filterResportesCopiaFacturaMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $resultado = $consulta;
      //$resultadoTotales = filterResportesCopiaFacturaMuniUbicacion2($this->conDifusion, $fechainicio, $fechafin, false);
      //$resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes3($resultadoTotales);
      return  $this->getConsultasSegmentosUbicacionMunicipioResportes4($resultado);
    } else if (!strcmp($municipioUser, 'todos') == 0 && strcmp($ubicacionUser, 'todos') == 0) {
      //$consultas = filterResportesCopiaFacturaMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $consultas = $consulta;
      $reportesPorMunicipio = array();

      foreach ($consultas as $clave => $valor) {
        $municipioDb = $valor->DATA[0]->MUNICIPIO;
        if (strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          array_push($reportesPorMunicipio, $consultas[$clave]);
        }
      }
      //$resultadoTotales = filterResportesCopiaFacturaMuniUbicacion($this->conDifusion, $fechainicio, $fechafin, false);
      //$resultadoTotales = filterResportesCopiaFacturaMuniUbicacion3($this->conDifusion, $municipioUser, $ubicacionUser, 'municipio'); //1022 organizar esta consulta y retornarlo que necesita el metodo sigueintee
      //$resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes5($resultadoTotales);
      return  $this->getConsultasSegmentosUbicacionMunicipioResportes4($reportesPorMunicipio);
    } else if (strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      //$consultas = filterResportesCopiaFacturaMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $consultas = $consulta;
      $reportesPorUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;

        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0) {
          array_push($reportesPorUbicacion, $consultas[$clave]);
        }
      }
      //$resultadoTotales = filterResportesCopiaFacturaMuniUbicacion($this->conDifusion, $fechainicio, $fechafin, false);
      //$resultadoTotales = filterResportesCopiaFacturaMuniUbicacion3($this->conDifusion, $municipioUser, $ubicacionUser, 'ubicacion'); //1022 organizar esta consulta y retornarlo que necesita el metodo sigueintee
      //$resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes5($resultadoTotales);
      return  $this->getConsultasSegmentosUbicacionMunicipioResportes4($reportesPorUbicacion);
    } else if (!strcmp($municipioUser, 'todos') == 0 && !strcmp($ubicacionUser, 'todos') == 0) {
      //$consultas = filterResportesCopiaFacturaMuniUbicacion($this->conDifusion, $fechainicio, $fechafin);
      $consultas = $consulta;

      $reportesPorMunicipioUbicacion = array();

      foreach ($consultas as $clave => $valor) {
        $ubicacionDb = $valor->DATA[0]->UBICACION;
        $municipioDb = $valor->DATA[0]->MUNICIPIO;

        if (strcmp(strtoupper($ubicacionDb), strtoupper($ubicacionUser)) == 0 && strcmp(strtoupper($municipioDb), strtoupper($municipioUser)) == 0) {
          array_push($reportesPorMunicipioUbicacion, $consultas[$clave]);
        }
      }
      //$resultadoTotales = filterResportesCopiaFacturaMuniUbicacion3($this->conDifusion, $municipioUser, $ubicacionUser, 'municipioUbicacion'); //1022 organizar esta consulta y retornarlo que necesita el metodo sigueintee
      //$resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes3($resultadoTotales);
      // $resultadosTotalesFiltrados = $this->getConsultasSegmentosUbicacionMunicipioResportes5($resultadoTotales);
      return  $this->getConsultasSegmentosUbicacionMunicipioResportes4($reportesPorMunicipioUbicacion);
    }
  }


  //mensajes enviados por hora y día de la semana
  public function getDifusionDifusionHoraDia($fechainicio, $fechafin, $reglas)
  {
    return filterDifusionHoraDia($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas);
  }

  //mensajes enviados de apertura y cierre.
  public function getDifusionCantidadUsuarios($fechainicio, $fechafin, $reglas)
  {
    $response = [];
    $resultadosCriterio = array();

    $difusionCantidadDifundida = difusionCantidadDifundida($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas);

    if ($difusionCantidadDifundida['aperturas'] == false) {
      $resultadosCriterio['CANTIDAD_DIFUNDIDA_APERTURAS'] = 0;
    } else {
      $resultadosCriterio['CANTIDAD_DIFUNDIDA_APERTURAS'] = $difusionCantidadDifundida['aperturas']->n;
    }

    if ($difusionCantidadDifundida['cierres'] == false) {
      $resultadosCriterio['CANTIDAD_DIFUNDIDA_CIERRES'] = 0;
    } else {
      $resultadosCriterio['CANTIDAD_DIFUNDIDA_CIERRES'] = $difusionCantidadDifundida['cierres']->n;
    }


    $response['criterio'] = $resultadosCriterio;
    return $response;
  }

  //acuse recibido - mensajes de apaertua entregados y mensajes de cierre entregados
  public function getAcuseReciboAperturaCierre($fechainicio, $fechafin, $reglas)
  {

    //$catidadDifundida = difusionCantidadDifundida2($this->conDifusion, $fechainicio, $fechafin, $reglas); //mensajes enviados de apetura y de cierre
    $difundidosAperturaCierre = array();
    $acuseAperturaCierre = array();
    //$aperturas = $catidadDifundida['aperturas'];
    //$cierres = $catidadDifundida['cierres'];
    //$acuseRecibido = getAcuse_Recibo($this->conDifusion, $fechainicio, $fechafin); //total de mensajes enviados recibidos de apertura


    /*
    foreach ($aperturas as $apertura) {
      array_push($difundidosAperturaCierre, $apertura);
    }
    foreach ($cierres as $cierre) {
      array_push($difundidosAperturaCierre, $cierre);
    }

    foreach ($acuseRecibido as $clave => $valorAcuse) {
      foreach ($difundidosAperturaCierre as $clave => $valorDifundido) {
        if (strcmp(strtolower($valorAcuse->NIU), strtolower($valorDifundido->NIU)) == 0) {
          array_push($acuseAperturaCierre, $valorAcuse);
        }
      }
    }*/

    $response = getAcuse_Recibo($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas); //total de mensajes enviados recibidos de apertura
    $respuesta = array();
    $respuesta['entregadoApertura'] = 0;
    $respuesta['noEntregaApertura'] = 0;
    $respuesta['entregaSMSCApertura'] = 0;
    $respuesta['noEntregaOperadoraApertura'] = 0;
    $respuesta['entregadoCierre'] = 0;
    $respuesta['noEntregaCierre'] = 0;
    $respuesta['entregaSMSCCierre'] = 0;
    $respuesta['noEntregaOperadoraCierre'] = 0;

    foreach ($response as $value) {
      if ($value->ESTADO_APERTURA == '1') {
        if (isset($respuesta['entregadoApertura'])) {
          $respuesta['entregadoApertura'] += 1;
        } else {
          $respuesta['entregadoApertura'] = 1;
        }
      } else if ($value->ESTADO_APERTURA == '2') {
        if (isset($respuesta['noEntregaApertura'])) {
          $respuesta['noEntregaApertura'] += 1;
        } else {
          $respuesta['noEntregaApertura'] = 1;
        }
      } else if ($value->ESTADO_APERTURA == '4') {
        if (isset($respuesta['entregaSMSCApertura'])) {
          $respuesta['entregaSMSCApertura'] += 1;
        } else {
          $respuesta['entregaSMSCApertura'] = 1;
        }
      } else if ($value->ESTADO_APERTURA == '16') {
        if (isset($respuesta['noEntregaOperadoraApertura'])) {
          $respuesta['noEntregaOperadoraApertura'] += 1;
        } else {
          $respuesta['noEntregaOperadoraApertura'] = 1;
        }
      }
      if ($value->ESTADO_CIERRE == '1') {
        if (isset($respuesta['entregadoCierre'])) {
          $respuesta['entregadoCierre'] += 1;
        } else {
          $respuesta['entregadoCierre'] = 1;
        }
      } else if ($value->ESTADO_CIERRE == '2') {
        if (isset($respuesta['noEntregaCierre'])) {
          $respuesta['noEntregaCierre'] += 1;
        } else {
          $respuesta['noEntregaCierre'] = 1;
        }
      } else if ($value->ESTADO_CIERRE == '4') {
        if (isset($respuesta['entregaSMSCCierre'])) {
          $respuesta['entregaSMSCCierre'] += 1;
        } else {
          $respuesta['entregaSMSCCierre'] = 1;
        }
      } else if ($value->ESTADO_CIERRE == '16') {
        if (isset($respuesta['noEntregaOperadoraCierre'])) {
          $respuesta['noEntregaOperadoraCierre'] += 1;
        } else {
          $respuesta['noEntregaOperadoraCierre'] = 1;
        }
      }
    }

    return $respuesta;
  }


  //obtener mensajes enviados de apertura y cierre, y obtener mensajes entregados de apertura y cierre
  public function getDataAperturaCierre($fechainicio, $fechafin, $reglas)
  {

    $notificacion = array(); //59276 43974

    $msgEnviados = $this->getDifusionCantidadUsuarios($fechainicio, $fechafin, $reglas); //mensajes enviados de aperturas y de cierres - log_difusion_niu
    $msgEntregados = $this->getAcuseReciboAperturaCierre($fechainicio, $fechafin, $reglas); //mensajes entregados y no entregados, de apertura y de cierre -log_acuseRecibido_dinp


    if (count($msgEntregados) == 0) {
      $notificacion['msj_enviados_apertura'] = 0;
      $notificacion['porc_entregados_apertura'] = 0;
      $notificacion['msj_enviados_cierre'] = 0;
      $notificacion['porc_entregados_cierre'] = 0;
    } else {
      $notificacion['msj_enviados_apertura'] = $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_APERTURAS'];
      $notificacion['porc_entregados_apertura'] = (float)number_format((($msgEntregados['entregadoApertura'] * 100) / $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_APERTURAS']), 1, '.', ',');
      $notificacion['msj_enviados_cierre'] = $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_CIERRES'];
      $notificacion['porc_entregados_cierre'] = (float)number_format((($msgEntregados['entregadoCierre'] * 100) / $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_CIERRES']), 1, '.', ',');
    }



    return $notificacion;
  }

  //publicidad a lucy
  public function getAcuseReciboPromocionLucy($fechainicio, $fechafin)
  {
    $promociones = array();
    $promocion = getAcuseRecibo_PromocionLucy($this->conHerokuChecSgcb, $fechainicio, $fechafin);

    $promociones['promocionTotal'] = count($promocion);
    $promociones['promocion_mensual'] = $this->promocionLucyMensual($promocion);

    return $promociones;
  }

  //publicidad a suspensiones
  public function getAcuseReciboPromocionSuspensiones($fechainicio, $fechafin)
  {
    $promociones = array();
    $promocionSuspensiones = getAcuseReciboPromocion_Programadas($this->conHerokuChecSgcb, $fechainicio, $fechafin);

    $promociones['promocionTotalSuspensiones'] = count($promocionSuspensiones);
    //$promociones['promocion_mensual_suspensiones'] = $this->promocionLucyMensual($promocionSuspensiones);

    return $promociones;
  }

  public function getCancelacionesRecibidasPorDia($fechainicio, $fechafin)
  {
    $cancelaciones = filterCancelacionesPorDia($this->conHerokuChecSgcb, $fechainicio, $fechafin);
    return $cancelaciones;
  }

  public function getCancelacionesMensajesEnviadosPorDia($fechainicio, $fechafin)
  {
    $mensajesEnviados = filterCancelacionesMensajesEnviadosPorDia($this->conHerokuChecSgcb, $fechainicio, $fechafin);
    return $mensajesEnviados;
  }

  public function getCancelacionesMensajesEnviadosPorOrden($fechainicio, $fechafin)
  {
    $mensajesEnviados = filterCancelacionesMensajesEnviadosPorOrden($this->conHerokuChecSgcb, $fechainicio, $fechafin);
    return $mensajesEnviados;
  }

  //reglas
  public function getReglas()
  {
    $nom = '';
    $cont = 1;
    $totalReglas = [];
    $reglas = filterReglas($this->conDifusion);
    $reglasTotales = filterReglasTotales($this->conDifusion);
    foreach ($reglas as $clave => $valor) {
      foreach ($valor->nombres as $segmento => $nombre) {
        if ($cont == count($valor->nombres)) {
          $reglas[$clave]->nombre = $nom .= $nombre;
        } else {
          $reglas[$clave]->nombre = $nom .= $nombre . ' - ';
        }
        $cont++;
      }
      unset($valor->nombres);
      $cont = 1;
      $nom = '';
    }

    $totalReglas['reglas'] = $reglas;
    $totalReglas['reglasTotales'] = $reglasTotales;

    return $totalReglas;
  }

  public function promocionLucyMensual($promocion)
  {
    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;


    foreach ($promocion as $clave => $valor) {
      $fechaDb =  $valor;

      $separarFecha = explode('-', $fechaDb);
      $mes = $separarFecha[1];

      switch ($mes) {
        case '01':
          $response['enero'] += 1;
          break;
        case '02':
          $response['febrero'] += 1;
          break;
        case '03':
          $response['marzo'] += 1;
          break;
        case '04':
          $response['abril'] += 1;
          break;
        case '05':
          $response['mayo'] += 1;
          break;
        case '06':
          $response['junio'] += 1;
          break;
        case '07':
          $response['julio'] += 1;
          break;
        case '08':
          $response['agosto'] += 1;
          break;
        case '09':
          $response['septiembre'] += 1;
          break;
        case '10':
          $response['octubre'] += 1;
          break;
        case '11':
          $response['noviembre'] += 1;
          break;
        case '12':
          $response['diciembre'] += 1;
          break;
      }
    }

    return $response;
  }


  //mesajes totales enviados
  public function difusionAperturaCierreTotalMeses($fechainicio, $fechafin, $reglas)
  {
    $difundidosAperturaCierre = array();
    $catidadDifundida = difusionCantidadDifundida2($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas); //log_difusion_niu
    $diusionTotal['total_msm_enviados'] = count($catidadDifundida['aperturas']) + count($catidadDifundida['cierres']); //221565
    //$diusionTotal['total_msm_enviados_mes'] = $this->getResultadoDifusionEnviada($fechainicio, $fechafin, $aperturas, $cierres);



    $catidadDifundidaTotales = difusionCantidadDifundidaTotal2($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas); //log_difusion_niu
    $aperturasTotales = $catidadDifundidaTotales['aperturas'];
    $cierresTotales = $catidadDifundidaTotales['cierres'];

    //$diusionTotal['total_msm_enviados'] = count($catidadDifundidaTotales['aperturas']) + count($catidadDifundidaTotales['cierres']);
    $diusionTotal['total_msm_enviados_mes'] = $this->getResultadoDifusionEnviada($fechainicio, $fechafin, $aperturasTotales, $cierresTotales);
    return $diusionTotal;
  }

  //mesajes enviados por segementos
  public function getDifusiobSegmentos($fechainicio, $fechafin, $reglas)
  {

    $difundidosAperturaCierre = array();
    //$prueba = difusionUbcacion($this->conHerokuChec, $fechainicio, $fechafin, $reglas);
    $catidadDifundida = difusionSegementos($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas); //log_difusion_niu
    $aperturasCierres = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $aperturas = $catidadDifundida['aperturas'];

    foreach ($aperturas as $calve => $valor) {
      array_push($aperturasCierres, $valor);
    }

    $cierres = $catidadDifundida['cierres'];

    foreach ($cierres as $calve => $valor) {
      array_push($aperturasCierres, $valor);
    }


    foreach ($aperturasCierres as $key => $value) {

      switch ($value->SEGMENTO) {
        case 'Hogares':
          $responseSeg['hogares'] += 1;
          break;
        case 'Empresas':
          $responseSeg['empresas'] += 1;
          break;
        case 'Grandes Clientes':
          $responseSeg['grandesClientes'] += 1;
          break;
        case 'Gobierno':
          $responseSeg['gobierno'] += 1;
          break;
      }
    }
    //$diusionTotal['total_msm_enviados'] = count($catidadDifundida['aperturas']) + count($catidadDifundida['cierres']);
    //$diusionTotal['total_msm_enviados_mes'] = $this->getResultadoDifusionEnviada($fechainicio, $fechafin, $aperturas, $cierres);


    return $responseSeg;
  }

  //mesajes enviados por ubicacion
  public function getDifusiobUbicacion($fechainicio, $fechafin, $reglas)
  {

    $catidadDifundida = difusionUbcacion($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas);
    $difusionTotal = array();
    $responseSeg['rural'] = 0;
    $responseSeg['urbano'] = 0;


    $aperturas = $catidadDifundida['aperturas'];
    foreach ($aperturas as $calve => $valor) {
      if (isset($valor->UBICACION[0]) > 0) {

        array_push($difusionTotal, $valor->UBICACION[0]);
      }
    }

    $cierres = $catidadDifundida['cierres'];
    foreach ($cierres as $calve => $valor) {
      if (isset($valor->UBICACION[0]) > 0) {

        array_push($difusionTotal, $valor->UBICACION[0]);
      }
    }


    foreach ($difusionTotal as $key => $value) {

      switch ($value) {
        case 'R':
          $responseSeg['rural'] += 1;
          break;
        case 'U':
          $responseSeg['urbano'] += 1;
          break;
      }
    }
    //$diusionTotal['total_msm_enviados'] = count($catidadDifundida['aperturas']) + count($catidadDifundida['cierres']);
    //$diusionTotal['total_msm_enviados_mes'] = $this->getResultadoDifusionEnviada($fechainicio, $fechafin, $aperturas, $cierres);


    return $responseSeg;
  }

  //obtener mensajes enviados de apertura y cierre, y obtener mensajes entregados de apertura y cierre
  public function getDataAperturaCierre2($fechainicio, $fechafin, $reglas)
  {

    $notificacion = array(); //59276 43974

    $msgEnviados = $this->getDifusionCantidadUsuarios($fechainicio, $fechafin, $reglas); //mensajes enviados de aperturas y de cierres - log_difusion_niu
    //$msgEntregados = $this->getAcuseReciboAperturaCierre($fechainicio, $fechafin, $reglas); //mensajes entregados y no entregados, de apertura y de cierre -log_acuseRecibido_dinp
    $msgEntregados = $this->getAcuseReciboAperturaCierre($fechainicio, $fechafin, $reglas); //mensajes entregados y no entregados, de apertura y de cierre -log_acuseRecibido_dinp

    if (count($msgEntregados) == 0) {
      $notificacion['msj_enviados_apertura'] = 0;
      $notificacion['porc_entregados_apertura'] = 0;
      $notificacion['msj_enviados_cierre'] = 0;
      $notificacion['porc_entregados_cierre'] = 0;
    } else {
      if ($msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_APERTURAS'] > 0) {
        $notificacion['msj_enviados_apertura'] = $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_APERTURAS'];
        $notificacion['porc_entregados_apertura'] = (float)number_format((($msgEntregados['entregadoApertura'] * 100) / $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_APERTURAS']), 1, '.', ',');
      } else {
        $notificacion['msj_enviados_apertura'] = 0;
        $notificacion['porc_entregados_apertura'] = 0;
      }
      if ($msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_CIERRES'] > 0) {
        $notificacion['msj_enviados_cierre'] = $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_CIERRES'];
        $notificacion['porc_entregados_cierre'] = (float)number_format((($msgEntregados['entregadoCierre'] * 100) / $msgEnviados['criterio']['CANTIDAD_DIFUNDIDA_CIERRES']), 1, '.', ',');
      } else {
        $notificacion['msj_enviados_cierre'] = 0;
        $notificacion['porc_entregados_cierre'] = 0;
      }
    }


    return $notificacion; //28321 26413
  }



  //meses en los que se enviaron difusiones por mes y regla
  public function getResultadoDifusionEnviada($fechainicio, $fechafin, $aperturas, $cierres)
  {
    $response = array();
    $response['enero'] = 0;
    $response['febrero'] = 0;
    $response['marzo'] = 0;
    $response['abril'] = 0;
    $response['mayo'] = 0;
    $response['junio'] = 0;
    $response['julio'] = 0;
    $response['agosto'] = 0;
    $response['septiembre'] = 0;
    $response['octubre'] = 0;
    $response['noviembre'] = 0;
    $response['diciembre'] = 0;

    foreach ($aperturas as $key => $value) {
      $separarFecha = explode('-', $value->FECHA_ENVIO_APERTURA);
      $mes = $separarFecha[1];
      switch ($mes) {
        case '01':
          $response['enero'] += 1;
          break;
        case '02':
          $response['febrero'] += 1;
          break;
        case '03':
          $response['marzo'] += 1;
          break;
        case '04':
          $response['abril'] += 1;
          break;
        case '05':
          $response['mayo'] += 1;
          break;
        case '06':
          $response['junio'] += 1;
          break;
        case '07':
          $response['julio'] += 1;
          break;
        case '08':
          $response['agosto'] += 1;
          break;
        case '09':
          $response['septiembre'] += 1;
          break;
        case '10':
          $response['octubre'] += 1;
          break;
        case '11':
          $response['noviembre'] += 1;
          break;
        case '12':
          $response['diciembre'] += 1;
          break;
      }
    }

    foreach ($cierres as $key => $value) {
      $separarFecha = explode('-', $value->FECHA_ENVIO_CIERRE);
      $mes = $separarFecha[1];
      switch ($mes) {
        case '01':
          $response['enero'] += 1;
          break;
        case '02':
          $response['febrero'] += 1;
          break;
        case '03':
          $response['marzo'] += 1;
          break;
        case '04':
          $response['abril'] += 1;
          break;
        case '05':
          $response['mayo'] += 1;
          break;
        case '06':
          $response['junio'] += 1;
          break;
        case '07':
          $response['julio'] += 1;
          break;
        case '08':
          $response['agosto'] += 1;
          break;
        case '09':
          $response['septiembre'] += 1;
          break;
        case '10':
          $response['octubre'] += 1;
          break;
        case '11':
          $response['noviembre'] += 1;
          break;
        case '12':
          $response['diciembre'] += 1;
          break;
      }
    }

    return $response;
  }

  public function tablaDifusionDINP($fechainicio, $fechafin, $reglas)
  {

    //$getConsultasSegmentosUbicacionMunicipioTotal = $this->getConsultasSegmentosUbicacionMunicipioDINP('1900-01-01 00:00', "2040-11-28 23:59", $reglas);
    return $this->getConsultasSegmentosUbicacionMunicipioDINP($fechainicio, $fechafin, $reglas, '', true);
  }

  public function tablaDifusionDINP2($fechainicio, $fechafin, $reglas)
  {


    $result = filterConsultasSegmentosUbicacionMunicipio3($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas);
    //$resultUsuariosporMunicipio = filterConsultaUsuariosXMunicipio($this->conDifusion);
    //$catidadDifundida = difusionCantidadDifundida2($this->conDifusion, $fechainicio, $fechafin, $reglas); //log_difusion_niu
    //$diusionTotal = count($catidadDifundida['aperturas']) + count($catidadDifundida['cierres']);
    $respuestaFinal = array();
    $responseSeg = array();
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;
    $responseCls = array();
    $objTable = array();
    $cont = 0;
    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $contU = 0;
    $contR = 0;
    $contM = 0;
    $cont = 0;
    $totalDatosTabla = array();

    //separar la cantidad de municipios y su cantidad de urbano y rural
    foreach ($result as $clave => $valor) {
      $municipio = strtoupper($valor->MUNICIPIO);
      foreach ($totalDatosTabla as $clave2 => $valor2) {
        if ($valor2->municipio == $municipio) {
          $cont += 1;
        }
      }

      if ($cont == 0) {
        $datosTabla = new stdClass();
        $datosTabla->municipio = $municipio;
        $datosTabla->municipioNum = 1;
        $datosTabla->urbano = 0;
        $datosTabla->rural = 0;
        if ($valor->UBICACION == 'U') {
          $datosTabla->urbano = 1;
        }

        if ($valor->UBICACION == 'R') {
          $datosTabla->rural = 1;
        }

        array_push($totalDatosTabla, $datosTabla);
      } else {

        foreach ($totalDatosTabla as $clave3 => $valor3) {
          if ($valor3->municipio == $municipio) {
            $totalDatosTabla[$clave3]->municipioNum = $totalDatosTabla[$clave3]->municipioNum + 1;
            if ($valor->UBICACION == 'U') {
              $totalDatosTabla[$clave3]->urbano = $totalDatosTabla[$clave3]->urbano + 1;
            }

            if ($valor->UBICACION == 'R') {
              $totalDatosTabla[$clave3]->rural = $totalDatosTabla[$clave3]->rural + 1;
            }
          }
        }
      }
      $cont = 0;
    }

    $dataTable = array();
    foreach ($totalDatosTabla as $clave4 => $valor4) {

      //porcentaje de municipio
      $valor4->porMunicipio = (float)number_format((($valor4->municipioNum * 100) / count($result)), 1, '.', ',');

      //porcentaje de rural
      $valor4->porcRural = (float)number_format((($valor4->rural * 100) / $valor4->municipioNum), 1, '.', ',');

      //porcentaje de urbano
      $valor4->porUrbano = (float)number_format((($valor4->urbano * 100) / $valor4->municipioNum), 1, '.', ',');


      array_push($dataTable, $valor4);
    }


    //ordenamiento de los municipios por numero de consultas
    $volume = [];
    foreach ($dataTable as $key => $row) {
      $volume[$key]  = $row->municipioNum;
    }
    array_multisort($volume, SORT_DESC, $dataTable);

    //anexar municipios que no han tenido consultas
    $contMunicipio = 0;
    foreach ($municipiosArray as $municipio => $numMunicipio) {
      foreach ($dataTable as $claveTabla => $valorTabla) {
        if ($municipio == $valorTabla->municipio) {
          $contMunicipio = $contMunicipio + 1;
        }
      }

      if ($contMunicipio == 0) {
        $nuevoMunicipio = new stdClass();
        $nuevoMunicipio->municipio = $municipio;
        $nuevoMunicipio->municipioNum = 0;
        $nuevoMunicipio->urbano = 0;
        $nuevoMunicipio->rural = 0;
        $nuevoMunicipio->porMunicipio = 0;
        $nuevoMunicipio->porcRural = 0;
        $nuevoMunicipio->porUrbano = 0;
        array_push($dataTable, $nuevoMunicipio);
      }

      $contMunicipio = 0;
    }


    //seleccion de municipios con numero de consultas
    $municipios = array();
    foreach ($dataTable as $clave => $valor) {
      $municipios[$valor->municipio] = $valor->municipioNum;
    }

    //var_dump($municipiosArray);
    $respuestaFinal['dataTable'] = $dataTable;
    $respuestaFinal['municipio'] = $municipios;


    /*$cont = 0;
    foreach ($dataTable as $clave => $valor) {
      $cont = $cont + $valor->municipioNum;
    }*/

    return $respuestaFinal;
  }


  public function getConsultasSegmentosUbicacionMunicipioDINP($fechainicio, $fechafin, $reglas, $resulTotal = '', $flag = false)
  {
    $result = filterConsultasSegmentosUbicacionMunicipio2($this->conDifusion, $fechainicio, $fechafin, $reglas);
    $resultUsuariosporMunicipio = filterConsultaUsuariosXMunicipio($this->conDifusion);
    $catidadDifundida = difusionCantidadDifundida2($this->conHerokuChec, $fechainicio, $fechafin, $reglas); //log_difusion_niu
    $diusionTotal = count($catidadDifundida['aperturas']) + count($catidadDifundida['cierres']);
    $respuestaFinal = array();
    $responseSeg = array();
    $responseSeg['hogares'] = 0;
    $responseSeg['empresas'] = 0;
    $responseSeg['grandesClientes'] = 0;
    $responseSeg['gobierno'] = 0;
    $responseUbi = array();
    $responseUbi['urbano'] = 0;
    $responseUbi['rural'] = 0;
    $responseCls = array();
    $responseCls['alumbrado'] = 0;
    $responseCls['comercial'] = 0;
    $responseCls['industria'] = 0;
    $responseCls['oficial'] = 0;
    $responseCls['otros'] = 0;
    $responseCls['residencial'] = 0;
    $responseCls['asistencial'] = 0;
    $responseCls['educativo'] = 0;
    $responseCls['areasComunes'] = 0;
    $responseCls['oxigeno'] = 0;
    $responseCls['provisional'] = 0;
    $objTable = array();
    $cont = 0;

    /*foreach($result as $clave => $valor){
      $cont = $cont + $valor->CANTIDAD_DIFUNDIDA;
    }*/

    $municipiosArray = array(
      "MANIZALES" => 0, "DOSQUEBRADAS" => 0, "LA VIRGINIA" => 0, "CHINCHINA" => 0, "PALESTINA" => 0, "VILLAMARIA" => 0,
      "MARSELLA" => 0, "SANTA ROSA" => 0, "RISARALDA" => 0, "ANSERMA" => 0, "VITERBO" => 0, "BELEN DE UMBRIA" => 0, "NEIRA" => 0,
      "MARMATO" => 0, "PACORA" => 0, "SUPIA" => 0, "VICTORIA" => 0, "NORCASIA" => 0, "LA DORADA" => 0,
      "TAMESIS" => 0, "JARDIN" => 0, "ANDES" => 0, "ABEJORRAL" => 0, "SANTA BARBARA" => 0, "LA PINTADA" => 0, "VALPARAISO" => 0,
      "CARAMANTA" => 0, "NARIÑO" => 0, "ARGELIA" => 0, "SONSON" => 0,
      "MARULANDA" => 0, "PENSILVANIA" => 0, "SAMANA" => 0, "SALAMINA" => 0, "AGUADAS" => 0, "ARANZAZU" => 0, "QUINCHIA" => 0,
      "SAN JOSE" => 0, "BELALCAZAR" => 0, "APIA" => 0, "SANTUARIO" => 0, "MISTRATO" => 0, "FILADELFIA" => 0, "LA MERCED" => 0,
      "RIOSUCIO" => 0, "GUATICA" => 0, "MARQUETALIA" => 0, "MANZANARES" => 0, "BALBOA" => 0, "LA CELIA" => 0, "PUEBLO RICO" => 0, "PEREIRA" => 0
    );

    $responseMunicipioUrbano = $municipiosArray;
    $responseMunicipioRural = $municipiosArray;
    $usuariosMunicipio = $municipiosArray;

    $sumaCiudad = 0;
    $cantidadCiudad = 0;

    if (count($resultUsuariosporMunicipio) > 0) {
      foreach ($resultUsuariosporMunicipio as $key => $value) {
        if (isset($usuariosMunicipio[strtoupper($value->_id)])) {
          $usuariosMunicipio[strtoupper($value->_id)] += $value->cantidad;
        } else {
          $cabecera = filterConsultaMunicipio($this->conDifusion, $value->_id);
          if (count($cabecera) > 0) {
            $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);

            if (isset($usuariosMunicipio[$cabecera])) {
              $usuariosMunicipio[$cabecera] += $value->cantidad;
            }
          }
        }
      }
    }

    if (count($result) > 0) {

      foreach ($result as $key => $value) {
        $rural = 0;
        $urbano = 0;
        if (isset($value->UBICACION->R)) {
          $rural = $value->UBICACION->R;
        }
        if (isset($value->UBICACION->U)) {
          $urbano = $value->UBICACION->U;
        }

        foreach ($value->UBICACION as $ubicacion => $cantidad) {
          switch (strtoupper($ubicacion)) {
            case 'U':
              $responseUbi['urbano'] += $cantidad;
              break;
            case 'R':
              $responseUbi['rural'] += $cantidad;
              break;
          }
        }

        foreach ($value->MUNICIPIOS as $municipio => $cantidad) {

          if (isset($municipiosArray[strtoupper($municipio)])) {
            $municipiosArray[strtoupper($municipio)] = $municipiosArray[strtoupper($municipio)] + $cantidad;

            if (isset($value->UBICACION->R, $value->UBICACION->U)) {

              if ($value->UBICACION->U >= $value->UBICACION->R) {
                if (count((array) $value->MUNICIPIOS) > 1) {
                  $cantidadMunicipio = $cantidad;

                  while ($cantidadMunicipio > 0) {

                    if ($urbano > 0) {

                      if ($cantidadMunicipio < $urbano) {

                        $responseMunicipioUrbano[strtoupper($municipio)] += $cantidadMunicipio;
                        $urbano = $urbano - $cantidadMunicipio;
                        $cantidadMunicipio = 0;
                      } else {

                        $responseMunicipioUrbano[strtoupper($municipio)] += $urbano;
                        $cantidadMunicipio = $cantidadMunicipio - $urbano;
                        $urbano = 0;
                      }
                    } else {
                      if ($cantidadMunicipio < $rural) {

                        $responseMunicipioRural[strtoupper($municipio)] += $cantidadMunicipio;
                        $rural = $rural - $cantidadMunicipio;
                        $cantidadMunicipio = 0;
                      } else {

                        $responseMunicipioRural[strtoupper($municipio)] += $rural;
                        $cantidadMunicipio = $cantidadMunicipio - $rural;
                        $rural = 0;
                      }
                    }
                  }
                } else {
                  $responseMunicipioUrbano[strtoupper($municipio)] += $value->UBICACION->U;
                  $responseMunicipioRural[strtoupper($municipio)] += $value->UBICACION->R;
                }
              } else {

                if (count((array) $value->MUNICIPIOS) > 1) {
                  $cantidadMunicipio = $cantidad;

                  while ($cantidadMunicipio > 0) {
                    if ($rural > 0) {

                      if ($cantidadMunicipio < $rural) {

                        $responseMunicipioRural[strtoupper($municipio)] += $cantidadMunicipio;
                        $rural = $rural - $cantidadMunicipio;
                        $cantidadMunicipio = 0;
                      } else {

                        $responseMunicipioRural[strtoupper($municipio)] += $rural;
                        $cantidadMunicipio = $cantidadMunicipio - $rural;
                        $rural = 0;
                      }
                    } else {
                      if ($cantidadMunicipio < $urbano) {

                        $responseMunicipioUrbano[strtoupper($municipio)] += $cantidadMunicipio;
                        $urbano = $urbano - $cantidadMunicipio;
                        $cantidadMunicipio = 0;
                      } else {

                        $responseMunicipioUrbano[strtoupper($municipio)] += $urbano;
                        $cantidadMunicipio = $cantidadMunicipio - $urbano;
                        $urbano = 0;
                      }
                    }
                  }
                } else {

                  $responseMunicipioUrbano[strtoupper($municipio)] += $value->UBICACION->U;
                  $responseMunicipioRural[strtoupper($municipio)] += $value->UBICACION->R;
                }
              }
            } else if (isset($value->UBICACION->R)) {

              $responseMunicipioRural[strtoupper($municipio)] += $cantidad;
            } else if (isset($value->UBICACION->U)) {

              $responseMunicipioUrbano[strtoupper($municipio)] += $cantidad;
            }
          } else {

            $cabecera = filterConsultaMunicipio($this->conDifusion, $municipio);
            if (count($cabecera) > 0) {
              $cabecera = strtoupper($cabecera[0]->MUNICIPIO_CABECERA);
            } else {
              $cabecera = '';
            }

            if (isset($municipiosArray[$cabecera])) {

              $municipiosArray[$cabecera] += $cantidad;

              if (isset($value->UBICACION->R, $value->UBICACION->U)) {

                if ($value->UBICACION->U >= $value->UBICACION->R) {
                  if (count((array) $value->MUNICIPIOS) > 1) {
                    $cantidadMunicipio = $cantidad;

                    while ($cantidadMunicipio > 0) {

                      if ($urbano > 0) {

                        if ($cantidadMunicipio < $urbano) {

                          $responseMunicipioUrbano[$cabecera] += $cantidadMunicipio;
                          $urbano = $urbano - $cantidadMunicipio;
                          $cantidadMunicipio = 0;
                        } else {

                          $responseMunicipioUrbano[$cabecera] += $urbano;
                          $cantidadMunicipio = $cantidadMunicipio - $urbano;
                          $urbano = 0;
                        }
                      } else {
                        if ($cantidadMunicipio < $rural) {

                          $responseMunicipioRural[$cabecera] += $cantidadMunicipio;
                          $rural = $rural - $cantidadMunicipio;
                          $cantidadMunicipio = 0;
                        } else {

                          $responseMunicipioRural[$cabecera] += $rural;
                          $cantidadMunicipio = $cantidadMunicipio - $rural;
                          $rural = 0;
                        }
                      }
                    }
                  } else {
                    $responseMunicipioUrbano[$cabecera] += $value->UBICACION->U;
                    $responseMunicipioRural[$cabecera] += $value->UBICACION->R;
                  }
                } else {

                  if (count((array) $value->MUNICIPIOS) > 1) {
                    $cantidadMunicipio = $cantidad;

                    while ($cantidadMunicipio > 0) {
                      if ($rural > 0) {

                        if ($cantidadMunicipio < $rural) {

                          $responseMunicipioRural[$cabecera] += $cantidadMunicipio;
                          $rural = $rural - $cantidadMunicipio;
                          $cantidadMunicipio = 0;
                        } else {

                          $responseMunicipioRural[$cabecera] += $rural;
                          $cantidadMunicipio = $cantidadMunicipio - $rural;
                          $rural = 0;
                        }
                      } else {
                        if ($cantidadMunicipio < $urbano) {

                          $responseMunicipioUrbano[$cabecera] += $cantidadMunicipio;
                          $urbano = $urbano - $cantidadMunicipio;
                          $cantidadMunicipio = 0;
                        } else {

                          $responseMunicipioUrbano[$cabecera] += $urbano;
                          $cantidadMunicipio = $cantidadMunicipio - $urbano;
                          $urbano = 0;
                        }
                      }
                    }
                  } else {

                    $responseMunicipioUrbano[$cabecera] += $value->UBICACION->U;
                    $responseMunicipioRural[$cabecera] += $value->UBICACION->R;
                  }
                }
              } else if (isset($value->UBICACION->R)) {

                $responseMunicipioRural[$cabecera] += $cantidad;
              } else if (isset($value->UBICACION->U)) {

                $responseMunicipioUrbano[$cabecera] += $cantidad;
              }
            }
          }
        }
        foreach ($value->SEGMENTOS as $segmento => $cantidad) {

          switch (strtoupper($segmento)) {
            case 'HOGARES':
              $responseSeg['hogares'] += $cantidad;
              break;
            case 'EMPRESAS':
              $responseSeg['empresas'] += $cantidad;
              break;
            case 'GRANDES CLIENTES':
              $responseSeg['grandesClientes'] += $cantidad;
              break;
            case 'GOBIERNO':
              $responseSeg['gobierno'] += $cantidad;
              break;
          }
        }

        foreach ($value->CLASE_SERVICIO as $clase => $cantidad) {
          switch (strtoupper($clase)) {
            case 'ALUMBRADO PUBLICO':
              $responseCls['alumbrado'] += $cantidad;
              break;
            case 'COMERCIAL':
              $responseCls['comercial'] += $cantidad;
              break;
            case 'INDUSTRIAL':
              $responseCls['industria'] += $cantidad;
              break;
            case 'SERVICIOS Y OFICIAL':
              $responseCls['oficial'] += $cantidad;
              break;
            case 'OTROS':
              $responseCls['otros'] += $cantidad;
              break;
            case 'RESIDENCIAL':
              $responseCls['residencial'] += $cantidad;
              break;
            case 'ESPECIAL ASISTENCIAL':
              $responseCls['asistencial'] += $cantidad;
              break;
            case 'ESPECIAL EDUCATIVO':
              $responseCls['educativo'] += $cantidad;
              break;
            case 'AREAS COMUNES':
              $responseCls['areasComunes'] += $cantidad;
              break;
            case 'OXIGENODEPENDIENTES':
              $responseCls['oxigeno'] += $cantidad;
              break;
            case 'PROVISIONAL':
              $responseCls['provisional'] += $cantidad;
              break;
          }
        }
      }

      /*if ($flag) {
        $objTable = array();
        $cont = 0;
        foreach ($municipiosArray as $clave => $valor) {
          $objTable[$cont]['municipio'] = $clave;
          $objTable[$cont]['num'] = $valor;
          if ($resulTotal['municipio'][$clave] > 0) {
            $objTable[$cont]['porcon'] = (float)number_format((($valor * 100) / $resulTotal['municipio'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcon'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioUrbano as $clave => $valor) {
          if ($resulTotal['municipioUrbano'][$clave] > 0) {
            $objTable[$cont]['porUrbano'] = (float)number_format((($valor * 100) / $resulTotal['municipioUrbano'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porUrbano'] = 0;
          }
          $cont += 1;
        }
        $cont = 0;
        foreach ($responseMunicipioRural as $clave => $valor) {
          if ($resulTotal['municipioRural'][$clave] > 0) {
            $objTable[$cont]['porcRural'] = (float)number_format((($valor * 100) / $resulTotal['municipioRural'][$clave]), 1, '.', ',');
          } else {
            $objTable[$cont]['porcRural'] = 0;
          }
          $cont += 1;
        }
        $respuestaFinal['dataTable'] = $objTable;
      }*/

      foreach ($municipiosArray as $clave2 => $valor2) {
        foreach ($responseMunicipioUrbano as $clave3 => $valor3) {
          foreach ($responseMunicipioRural as $clave4 => $valor4) {
            if ($clave2 == $clave3 && $clave2 == $clave4) {

              //$totalMunicipios = array_sum($municipiosArray);
              $objTable[$cont]['municipio'] = $clave2;

              $objTable[$cont]['num'] = $valor2;

              if ($valor2 > 0) {
                //$porceReportes[$clave1] = (float)number_format((($valor1 * 100) / $valor2), 1, '.', ',');
                $objTable[$cont]['porcon'] = (float)number_format((($valor2 * 100) / $diusionTotal), 1, '.', ',');
              } else {
                //$porceReportes[$clave1] = 0;
                $objTable[$cont]['porcon'] = 0;
              }

              if ($valor3 > 0) {
                //$responseporcenMunicipioUrbano[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
                $objTable[$cont]['porUrbano'] = (float)number_format((($valor3 * 100) / $valor2), 1, '.', ',');
              } else {
                //$responseporcenMunicipioUrbano[$clave1] = 0;
                $objTable[$cont]['porUrbano'] = 0;
              }

              if ($valor4 > 0) {
                //$responseporcenMunicipioRural[$clave1] = (float)number_format((($valor2 * 100) / $valor1), 1, '.', ',');
                $objTable[$cont]['porcRural'] = (float)number_format((($valor4 * 100) / $valor2), 1, '.', ',');
              } else {
                //$responseporcenMunicipioRural[$clave1] = 0;
                $objTable[$cont]['porcRural'] = 0;
              }

              $cont = $cont + 1;
            }
          }
        }
      }

      $volume = [];
      foreach ($objTable as $key => $row) {
        $volume[$key]  = $row['num'];
      }

      array_multisort($volume, SORT_DESC, $objTable);

      //var_dump($municipiosArray);
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['clasesServicio'] = $responseCls;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
      $respuestaFinal['usuariosMunicipio'] = $usuariosMunicipio;
      $respuestaFinal['dataTable'] = $objTable;

      return $respuestaFinal;
    } else {
      $respuestaFinal['segmentos'] = $responseSeg;
      $respuestaFinal['ubicacion'] = $responseUbi;
      $respuestaFinal['clasesServicio'] = $responseCls;
      $respuestaFinal['municipio'] = $municipiosArray;
      $respuestaFinal['municipioUrbano'] = $responseMunicipioUrbano;
      $respuestaFinal['municipioRural'] = $responseMunicipioRural;
      $respuestaFinal['usuariosMunicipio'] = $usuariosMunicipio;
      $respuestaFinal['dataTable'] = $objTable;
    }
    //var_dump($respuestaFinal);
    return $respuestaFinal;
  }

  //mensajes enviados por regla
  public function getDifusioPorReglaDINP($fechainicio, $fechafin, $reglas)
  {
    $reglasDifusionValores = array();
    $reglasDifusionIndices = array();
    $reglasDifusion = array();
    $cont = 0;
    $difusionReglas = filterDifusionPorReglas($this->conHerokuChecSgcb, $fechainicio, $fechafin, $reglas);
    //apertura
    $apertura = $this->countReglas($difusionReglas, 'reglas_apertura');
    arsort($apertura);
    foreach ($apertura as $clave => $valor) {
      if ($cont < 5) {
        array_push($reglasDifusionValores, $valor);
        array_push($reglasDifusionIndices, $clave);
      }
      $cont += 1;
    }

    //cierre
    $cont = 0;
    $cierre =  $this->countReglas($difusionReglas, 'reglas_cierre');
    arsort($cierre);
    foreach ($cierre as $clave => $valor) {
      if ($cont < 5) {
        array_push($reglasDifusionValores, $valor);
        array_push($reglasDifusionIndices, $clave);
      }
      $cont += 1;
    }

    $reglasDifusion['indices'] =  $reglasDifusionIndices;
    $reglasDifusion['valores'] =  $reglasDifusionValores;

    return $reglasDifusion;
  }

  public function countReglas($difusionReglas, $campo)
  {
    $reglas = array();

    $reglas['3'] = 0;
    $reglas['4'] = 0;
    $reglas['1'] = 0;
    $reglas['2'] = 0;
    $reglas['5'] = 0;
    $reglas['6'] = 0;
    $reglas['7'] = 0;
    $reglas['8'] = 0;
    $reglas['9'] = 0;
    $reglas['10'] = 0;
    $reglas['11'] = 0;
    $reglas['12'] = 0;
    $reglas['13'] = 0;
    $reglas['14'] = 0;
    $reglas['15'] = 0;
    $reglas['16'] = 0;
    $reglas['17'] = 0;
    $reglas['18'] = 0;
    $reglas['19'] = 0;
    $reglas['20'] = 0;
    $reglas['21'] = 0;
    $reglas['22'] = 0;
    $reglas['23'] = 0;
    $reglas['24'] = 0;
    $reglas['25'] = 0;
    $reglas['26'] = 0;
    $reglas['27'] = 0;
    $reglas['28'] = 0;
    $reglas['29'] = 0;
    $reglas['30'] = 0;
    $reglas['31'] = 0;

    if (count($difusionReglas) > 0) {
      foreach ($difusionReglas[$campo] as $clave => $valor) {
        switch ($valor->REGLA) {
          case ' 3':
            $reglas['3'] += 1;
            break;
          case ' 4':
            $reglas['4'] += 1;
            break;
          case ' 1':
            $reglas['1'] += 1;
            break;
          case ' 2':
            $reglas['2'] += 1;
            break;
          case ' 5':
            $reglas['5'] += 1;
            break;
          case ' 6':
            $reglas['6'] += 1;
            break;
          case ' 7':
            $reglas['7'] += 1;
            break;
          case ' 8':
            $reglas['8'] += 1;
            break;
          case ' 9':
            $reglas['9'] += 1;
            break;
          case '10':
            $reglas['10'] += 1;
            break;
          case '11':
            $reglas['11'] += 1;
            break;
          case '12':
            $reglas['12'] += 1;
            break;
          case '13':
            $reglas['13'] += 1;
            break;
          case '14':
            $reglas['14'] += 1;
            break;
          case '15':
            $reglas['15'] += 1;
            break;
          case '16':
            $reglas['16'] += 1;
            break;
          case '17':
            $reglas['17'] += 1;
            break;
          case '18':
            $reglas['18'] += 1;
            break;
          case '19':
            $reglas['19'] += 1;
            break;
          case '20':
            $reglas['20'] += 1;
            break;
          case '21':
            $reglas['21'] += 1;
            break;
          case '22':
            $reglas['22'] += 1;
            break;
          case '23':
            $reglas['23'] += 1;
            break;
          case '24':
            $reglas['24'] += 1;
            break;
          case '25':
            $reglas['25'] += 1;
            break;
          case '26':
            $reglas['26'] += 1;
            break;
          case '27':
            $reglas['27'] += 1;
            break;
          case '28':
            $reglas['28'] += 1;
            break;
          case '29':
            $reglas['29'] += 1;
            break;
          case '30':
            $reglas['30'] += 1;
            break;
          case '31':
            $reglas['31'] += 1;
            break;
        }
      }
    }

    return $reglas;
  }


  //consultas por año

  public function getHorasAnio($consultas)
  {
    $completarHoras = array();
    $totalConsultas = array();

    for ($i = 0; $i <= date('m') - 1; $i++) {
      $completarHoras[$i]['_id'] = (string) $i;
      $completarHoras[$i]['cantidad'] = 0;
    }

    foreach ($consultas as $claveConsultas => $valorConsultas) {
      foreach ($completarHoras as $claveHoras => $valorHoras) {
        if ($claveConsultas == $claveHoras) {
          $completarHoras[$claveConsultas]['_id'] = $valorConsultas->_id;
          $completarHoras[$claveConsultas]['cantidad'] = $valorConsultas->cantidad;
        }
      }
    }


    return $completarHoras;
  }

  public function getHorasAnio2($consultas)
  {
    $completarHoras = array();
    $totalConsultas = array();

    for ($i = 0; $i <= date('m') - 1; $i++) {
      $completarHoras[$i]['_id'] = (string) $i;
      $completarHoras[$i]['cantidad'] = 0;
    }

    foreach ($consultas as $claveConsultas => $valorConsultas) {
      foreach ($completarHoras as $claveHoras => $valorHoras) {
        if ($valorHoras['_id'] == ($valorConsultas->_id - 1)) {
          $completarHoras[((int) $valorConsultas->_id) - 1]['_id'] = $valorConsultas->_id;
          $completarHoras[((int) $valorConsultas->_id) - 1]['cantidad'] = $valorConsultas->cantidad;
        }
      }
    }


    return $completarHoras;
  }

  public function getConsultasLucyAno($ano)
  {

    $completarHoras = array();
    $consultas = getConsultas_LucyAno($this->conHerokuChec, $ano);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= date('m') - 1; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasAnio2($consultas);

      return $resultado;
    }
  }
  public function getMensajesDifusionAno($ano)
  {
    $completarHoras = array();
    $consultas = getMensajes_DifusionAno($this->conHerokuChecSgcb, $ano);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= date('m') - 1; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasAnio2($consultas);

      return $resultado;
    }
  }
  public function getTipificacionAno($ano)
  {

    $completarHoras = array();

    $consultas = get_TipificacionAno($this->conDifusion, $ano);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= date('m') - 1; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasAnio2($consultas);

      return $resultado;
    }
  }
  public function getTurnosAno($ano)
  {

    $completarHoras = array();
    $consultas = get_TurnosAno($this->conDifusion, $ano);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= date('m') - 1; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasAnio2($consultas);

      return $resultado;
    }
  }
  public function getAvisosSuspensionesAno($ano)
  {

    $completarHoras = array();
    $consultas = get_AvisosSuspensionesAno($this->conHerokuChec, $ano);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= date('m') - 1; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasAnio2($consultas);

      return $resultado;
    }
  }

  public function getAvisosSuspensionesAno2($ano)
  {
    $completarHoras = array();
    $consultas = get_AvisosSuspensionesAno($this->conHerokuChec, $ano);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= date('m') - 1; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasAnio2($consultas);

      return $resultado;
    }
  }



  //consultas por mes y año
  public function get_ConsultasLucyDifllamMesAno($ano, $mes, $campo, $coleccion)
  {
    $diasTemp = $this->diasMes($ano, $mes);
    $consultasAnio = [];
    $dias = [];
    if ($campo == '$FECHA_ENVIO_APERTURA') {
      $respuesta1 = get_ConsultasLucy_Dif_llam_MesAnoApertura2($this->conHerokuChecSgcb, $ano, $mes, $campo, $coleccion);
      $respuesta2 = get_ConsultasLucy_Dif_llam_MesAnoCierre2($this->conHerokuChecSgcb, $ano, $mes, $coleccion);

      foreach ($respuesta1 as $clave => $valor) {
        foreach ($respuesta2 as $clave2 => $valor2) {
          if ($valor->_id == $valor2->_id) {
            $valor->suma = $valor->suma + $valor2->suma;
          }
        }
      }

      $response = $respuesta1; //8:4051 - 19:3722
    } else {
      $response = get_ConsultasLucy_Dif_llam_MesAno($this->conDifusion,$this->conHerokuChecSgcb,  $ano, $mes, $campo, $coleccion);
    }
    foreach ($response as $clave => $valor) {
      $consultasAnio[$valor->_id] =  $valor->suma;
    }
    ksort($consultasAnio);

    for ($i = 1; $i <= count($diasTemp); $i++) {
      $dias[$i] =  0;
    }

    foreach ($consultasAnio as $key => $value) {
      foreach ($diasTemp as $clave => $valor) {
        if ($valor == $key) {
          $dias[$valor] = $value;
        }
      }
    }
    //unset($dias[0]);
    //$dias[count($dias) - 1] = 0;


    return $dias;
  }

  public function diasMes($ano, $mes)
  {

    $prueba = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
    $dias = [];
    if (date('m') == $mes) {
      $diaActual = date('d');
      for ($i = 1; $i <= $diaActual; $i++) {
        //array_push($dias, $i);
        $dias[$i] = $i;
      }
    } else if ($mes < date('m')) {


      //encontrar el mes que viene y contar los dias que tiene
      $numDias = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
      for ($i = 1; $i <= $numDias; $i++) {
        //array_push($dias, $i);
        $dias[$i] = $i;
      }
    } else if ($mes > date('m')) {
      for ($i = 1; $i <= 0; $i++) {
        //array_push($dias, 0);
        $dias[$i] = 0;
      }
    }

    return $dias;
  }



  //consultas por día
  public function getHorasDia($consultas)
  {
    $completarHoras = array();
    $totalConsultas = array();

    for ($i = 0; $i <= 6; $i++) {
      $completarHoras[$i]['_id'] = (string) $i;
      $completarHoras[$i]['cantidad'] = 0;
    }

    foreach ($consultas as $claveConsultas => $valorConsultas) {
      foreach ($completarHoras as $claveHoras => $valorHoras) {
        if ($claveConsultas == $claveHoras) {
          $completarHoras[$claveConsultas]['_id'] = $valorConsultas->_id;
          $completarHoras[$claveConsultas]['cantidad'] = $valorConsultas->cantidad;
        }
      }
    }


    return $completarHoras;
  }

  public function getConsultasLucyDia($fechaInicio, $fechaFin)
  {
    $completarHoras = array();
    $consultas = obtenerConsultasLucyDia($this->conHerokuChec, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 6; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasDia($consultas);

      return $resultado;
    }
  }

  public function getMensajesDifusionDia($fechaInicio, $fechaFin)
  {


    $completarHoras = array();
    $consultas = getMensajes_DifusionDia($this->conHerokuChecSgcb, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 6; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasDia($consultas);

      return $resultado;
    }
  }

  public function getTipificacionDia($fechaInicio, $fechaFin)
  {

    $completarHoras = array();
    $consultas = get_TipificacionDia($this->conDifusion, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 6; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasDia($consultas);

      return $resultado;
    }
  }

  public function getTurnosDia($fechaInicio, $fechaFin)
  {

    $completarHoras = array();
    $consultas = get_TurnosDia($this->conDifusion, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 6; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasDia($consultas);

      return $resultado;
    }
  }

  public function getAvisosSuspensionesDia($fechaInicio, $fechaFin)
  {

    $completarHoras = array();
    $consultas = get_AvisosSuspensionesDia($this->conHerokuChec, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 6; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHorasDia($consultas);

      return $resultado;
    }
  }


  public function getHoras($consultas)
  {
    $completarHoras = array();
    $totalConsultas = array();

    for ($i = 0; $i <= 23; $i++) {
      $completarHoras[$i]['_id'] = (string) $i;
      $completarHoras[$i]['cantidad'] = 0;
    }

    foreach ($consultas as $claveConsultas => $valorConsultas) {
      foreach ($completarHoras as $claveHoras => $valorHoras) {
        if ($claveConsultas == $claveHoras) {
          $completarHoras[$claveConsultas]['_id'] = $valorConsultas->_id;
          $completarHoras[$claveConsultas]['cantidad'] = $valorConsultas->cantidad;
        }
      }
    }


    return $completarHoras;
  }

  public function getHoras2($consultas)
  {
    $completarHoras = array();
    $totalConsultas = array();

    for ($i = 0; $i <= 23; $i++) {
      $completarHoras[$i]['_id'] = (string) $i;
      $completarHoras[$i]['cantidad'] = 0;
    }

    foreach ($completarHoras as $claveHoras => $valorHoras) {
      foreach ($consultas as $claveConsultas => $valorConsultas) {
        if (strval($valorHoras['_id']) == strval($valorConsultas->_id)) {
          if ($valorConsultas->cantidad != 0) {
            $completarHoras[$claveHoras]['_id'] = $valorConsultas->_id;
            $completarHoras[$claveHoras]['cantidad'] = $valorConsultas->cantidad;
          }
        }
      }
    }



    return $completarHoras;
  }

  //consultas por hora
  public function getConsultasLucyHora($fechaInicio, $fechaFin)
  {
    $consultas = $this->getHoras(getConsultas_LucyHora($this->conHerokuChec, $fechaInicio, $fechaFin));

    return $consultas;
  }


  public function getMensajesDifusionHora($fechaInicio, $fechaFin)
  {
    $completarHoras = array();

    $consultas = getMensajes_DifusionHora($this->conHerokuChecSgcb, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 23; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHoras2($consultas);

      return $resultado;
    }
  }

  public function getTipificacionHora($fechaInicio, $fechaFin)
  {

    $completarHoras = array();
    $consultas = get_TipificacionHora($this->conDifusion, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 23; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHoras2($consultas);

      return $resultado;
    }
  }

  public function getTurnosHora($fechaInicio, $fechaFin)
  {

    $completarHoras = array();
    $consultas = get_TurnosHora($this->conDifusion, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 23; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {

      $resultado = $this->getHoras2($consultas);

      return $resultado;
    }
  }

  public function getAvisosSuspensionesHora($fechaInicio, $fechaFin)
  {

    $completarHoras = array();
    $consultas = get_AvisosSuspensionesHora($this->conHerokuChec, $fechaInicio, $fechaFin);
    if (count($consultas) == 0) {
      for ($i = 0; $i <= 23; $i++) {
        $completarHoras[$i]['_id'] = (string) $i;
        $completarHoras[$i]['cantidad'] = 0;
      }
      return $completarHoras;
    } else {
      return $consultas;
    }
  }


  //obtener usuarios por criterio de busqueda
  public function obtenerUsuarios($criterio, $valor)
  {
    $palabras = $valor;

    if (strcmp(strtolower($criterio), 'nombre') == 0) {
      $palabras = explode(" ", strtoupper($this->deleteSymbols($valor)));
    } else if (strcmp(strtolower($criterio), 'direccion') == 0) {
      $palabras = $this->processAddress($valor);
    }

    return filtrarUusarios($this->conHerokuChec, $criterio, $palabras);
  }


  //limpiar nombres
  public function deleteSymbols($direccion)
  {

    $direccion = trim($direccion);

    $direccion = str_replace(
      array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
      array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
      $direccion
    );

    $direccion = str_replace(
      array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
      array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
      $direccion
    );

    $direccion = str_replace(
      array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
      array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
      $direccion
    );

    $direccion = str_replace(
      array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
      array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
      $direccion
    );

    $direccion = str_replace(
      array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
      array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
      $direccion
    );

    $direccion = str_replace(
      array('ñ', 'Ñ', 'ç', 'Ç'),
      array('n', 'N', 'c', 'C'),
      $direccion
    );

    $direccion = str_replace(
      array(
        "\\", "¨", "º", "-", "_", "~",
        "#", "@", "|", "!", "\"",
        "·", "$", "%", "&", "/",
        "(", ")", "?", "'", "¡",
        "¿", "[", "^", "<code>", "]",
        "+", "}", "{", "¨", "´",
        ">", "< ", ";", ",", ":",
        ".", "N°", " "
      ),
      ' ',
      $direccion
    );
    $direccion = str_replace(
      array("NUM", "num"),
      ' ',
      $direccion
    );
    return $direccion;
  }


  //limpiar direcciones
  public function processAddress($direccion)
  {

    $direcNoSymbols = $this->deleteSymbols($direccion);

    if (strpos($direccion, 'número')) {
      $direcNoSymbols = substr_replace($direcNoSymbols, ' ', strpos($direcNoSymbols, 'número'), 1);
    }
    if (strpos($direccion, 'numero')) {
      $direcNoSymbols = substr_replace($direcNoSymbols, ' ', strpos($direcNoSymbols, 'numero'), 1);
    }
    $res = preg_replace("/[^a-zA-Z0-9\s]/", "", $direcNoSymbols);
    $output = preg_replace('!\s+!', ' ', $res);
    $array = explode(" ", strtoupper($output));

    foreach ($array as $i => $value) {

      if (
        $value == "CARRERA" || $value == "CRA" || $value == "CAR" || $value == "CR" || $value == "KRRA" || $value == "CARRERAS" || $value == "KRA"
        || $value == "KRR" || $value == "KARRERA" || $value == "KRRERA" || $value == "KARR" || $value == "CRR" || $value == "CRRA" || $value == "K"
      ) {
        $array[$i] = "CRA";
      }
      if (
        $value == "CALLE" || $value == "CLL" || $value == "CLLL" || $value == "CALL" || $value == "CAYE" || $value == "CL" || $value == "CALLES"
        || $value == "KALLE" || $value == "KLL" || $value == "CAL" || $value == "KLLE" || $value == "KL"
      ) {
        $array[$i] = "CLL";
      }
      if ($value == "AVENIDA" || $value == "AV" || $value == "AVE" || $value == "AVDA") {
        $array[$i] = "AVE";
      }
      if ($value == "EDIFICIO" || $value == "ED") {
        $array[$i] = "EDI";
      }
      if ($value == "N" || $value == "NO" || $value == "NUMERO" || $value == "NÚMERO") {
        $array[$i] = " ";
      }
      if ($value == "APARTAMENTO" || $value == "APTO" || $value == "APT" || $value == "AP" || $value == "APARTAMENTOS" || $value == "APTOS") {
        $array[$i] = "APT";
      }
      if ($value == "BLOQUE" || $value == "BLOQUES" || $value == "BLQ" || $value == "BL" || $value == "BLOKE") {
        $array[$i] = "BLQ";
      }
      if ($value == "LOCAL" || $value == "LOC") {
        $array[$i] = "LOC";
      }
      if ($value == "VEREDA" || $value == "VDA") {
        $array[$i] = "VDA";
      }
      if ($value == "SECTOR" || $value == "SEC" || $value == "SECT") {
        $array[$i] = "SEC";
      }
      if ($value == "DIAGONAL" || $value == "DIAG" || $value == "DIA" || $value == "DNAL") {
        $array[$i] = "DIG";
      }
      //FALTA
      if ($value == "CASA" || $value == "CASAS" || $value == "CA" || $value == "CSA" || $value == "CAS") {
        $array[$i] = "CAS";
      }
      if ($value == "INTERIOR" || $value == "INTE" || $value == "INTER" || $value == "IN") {
        $array[$i] = "INT";
      }
      if ($value == "PISO" || $value == "PISOS" || $value == "P" || $value == "PIS" || $value == "PSO") {
        $array[$i] = "PSO";
      }
      //
      if (
        $value == "BARRIO" || $value == "BARR" || $value == "BAR" || $value == "BRIO"
        || $value == "VARRIO" || $value == "VARR"
      ) {
        $array[$i] = "BRR";
      }
      if ($value == "FINCA" || $value == "FNCA" || $value == "FINC" || $value == "FNC") {
        $array[$i] = "FCA";
      }
      if ($value == "SALIDA" || $value == "SDA" || $value == "SLIDA" || $value == "SA") {
        $array[$i] = "SAL";
      }
      if ($value == "MANZANA" || $value == "MANZ" || $value == "MA" || $value == "MZ" || $value == "MNAZ") {
        $array[$i] = "MNZ";
      }
      if ($value == "VILLA" || $value == "VILL" || $value == "VI" || $value == "V") {
        $array[$i] = "VIL";
      }
      if ($value == "TOR" || $value == "TORE" || $value == "TORRE" || $value == "TRR") {
        $array[$i] = "TORRE";
      }
      if ($value == "URBANIZACION") {
        $array[$i] = "URB";
      }
      if ($value == "CABAÑA" || $value == "CABAA") {
        $array[$i] = "CABANA";
      }
      if ($value == "LOTE" || $value == "LOTES" || $value == "LT") {
        $array[$i] = "LTE";
      }
      if ($value == "OFICINA" || $value == "OFICINAS" || $value == "OF") {
        $array[$i] = "OFI";
      }
    }

    return $array;
  }


  //ontener historial del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerHistorial($niu, $fechaInicio, $fechaFin)
  {
    $historial = array();
    $promocionLucy = getAcuseReciboHitorial_PromocionLucy($this->conHerokuChecSgcb, $niu, $fechaInicio, $fechaFin);
    $dinp = difusion($this->conHerokuChecSgcb, $niu,  $fechaInicio, $fechaFin);
    $avisoSuspension = '';
    $chats = '';

    $historial['pormocion_lucy'] = $promocionLucy;
    $historial['dinp'] = $dinp;
    $historial['aviso_susp'] = $avisoSuspension;
    $historial['chats'] = $chats;

    return $historial;
  }

  //obtener info preliminar  del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerPreliminar($niu, $fechaInicio, $fechaFin)
  {
    $datosPromocion = array();
    $datosDinp = array();
    $datosInvitacionSusp = array();
    $datosLucy = array();
    $historial = array();
    $llamadas = array();
    $dateTimeInicio = new DateTime($fechaInicio);
    $dateTimeFinal = new DateTime($fechaFin);
    $fechaInicial = $dateTimeInicio->format('Uv');
    $fechafinal = $dateTimeFinal->format('Uv');

    $promocionLucy = getAcuseReciboHitorial_PromocionLucy($this->conHerokuChecSgcb, $niu, $fechaInicio, $fechaFin);
    $dinp = difusion($this->conHerokuChecSgcb, $niu,  $fechaInicio, $fechaFin);
    $llamadasdb = llamadasContact($this->conDifusion, $niu, $fechaInicio, $fechaFin);
    $chats = $this->getConversacionesLucy($niu, $fechaInicial, $fechafinal);
    $invitacionSuspension = invitacionSusProgramadas($this->conHerokuChecSgcb, $niu, $fechaInicio, $fechaFin);
    $avisoSuspension = array();


    if (count($llamadasdb['consulta_no_filtrada']) > 0) {
      $llamadas['nro_consultas'] = count($llamadasdb['consulta_filtrada']);
    } else {
      $llamadas['nro_consultas'] = '0';
    }

    if (count($promocionLucy['consulta_no_filtrada']) > 0) {
      $datosPromocion['nro_consultas'] = count($promocionLucy['consulta_filtrada']);
    } else {
      $datosPromocion['nro_consultas'] = '0';
    }

    if (count($dinp['aperturas_no_filtradas']) + count($dinp['cierres_no_filtrados'])) {
      $datosDinp['nro_consultas'] = count($dinp['aperturas_filtradas']) + count($dinp['cierres_filtrados']);
    } else {
      $datosDinp['nro_consultas'] = '0';
    }

    if (count($invitacionSuspension['consulta_no_filtrada']) > 0) {
      $datosInvitacionSusp['nro_consultas'] = count($invitacionSuspension['consulta_filtrada']);
    } else {
      $datosInvitacionSusp['nro_consultas'] = '0';
    }

    $chatsFiltrados = array();
    $chatsNoFiltrados = array();
    if (count($chats) > 0) {
      foreach ($chats['chats_filtrados'] as $clave => $valor) {
        if (count($valor) != 0) {
          array_push($chatsFiltrados, $valor);
        }
      }

      foreach ($chats['chats_no_filtrados'] as $clave => $valor) {
        if (count($valor) != 0) {
          array_push($chatsNoFiltrados, $valor);
        }
      }

      if (count($chatsNoFiltrados) > 0) {
        $datosLucy['nro_consultas'] = count($chatsFiltrados);
      } else {
        $datosLucy['nro_consultas'] = '0';
      }
    } else {
      $datosLucy['nro_consultas'] = '0';
    }


    $total = ($datosPromocion['nro_consultas'] + $datosDinp['nro_consultas'] + $datosInvitacionSusp['nro_consultas'] + $datosLucy['nro_consultas'] + $llamadas['nro_consultas']);
    if ($total <= 0) {
      $historial['ppormocion_lucy'] = 0;
      $historial['pdinp'] = 0;
      $historial['pinvitacion_susp'] = 0;
      $historial['pchats'] = 0;
      $historial['pllamadas'] = 0;
    } else {
      $historial['ppormocion_lucy'] = (float)number_format((($datosPromocion['nro_consultas'] * 100) / $total), 1, '.', ',');
      $historial['pdinp'] = (float)number_format((($datosDinp['nro_consultas'] * 100) / $total), 1, '.', ',');;
      $historial['pinvitacion_susp'] = (float)number_format((($datosInvitacionSusp['nro_consultas'] * 100) / $total), 1, '.', ',');
      $historial['pchats'] = (float)number_format((($datosLucy['nro_consultas'] * 100) / $total), 1, '.', ',');
      $historial['pllamadas'] = (float)number_format((($llamadas['nro_consultas'] * 100) / $total), 1, '.', ',');
    }

    return $historial;
  }

  //obtener info preliminar  del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerPreliminarChats($niu, $fechaInicio, $fechaFin)
  {
    $datosLucy = array();
    $historial = array();
    $dateTimeInicio = new DateTime($fechaInicio);
    $dateTimeFinal = new DateTime($fechaFin);
    $fechaInicial = $dateTimeInicio->format('Uv');
    $fechafinal = $dateTimeFinal->format('Uv');
    $chats = $this->getConversacionesLucy($niu, $fechaInicial, $fechafinal);

    $chatsFiltrados = array();
    $chatsNoFiltrados = array();
    if (count($chats) > 0) {
      foreach ($chats['chats_filtrados'] as $clave => $valor) {
        if (count($valor) != 0) {
          array_push($chatsFiltrados, $valor);
        }
      }

      foreach ($chats['chats_no_filtrados'] as $clave => $valor) {
        if (count($valor) != 0) {
          array_push($chatsNoFiltrados, $valor);
        }
      }

      if (count($chatsNoFiltrados) > 0) {
        $datosLucy['fuente'] = array('fuente' => 'Lucy', 'idFuente' => 'idLucy');
        if (count($chatsFiltrados) > 0) {
          $fechaUnix = $chatsFiltrados['0'][(count($chatsFiltrados['0']) - 1)]->mensaje[0]->fecha;
          $datosLucy['fecha'] = date('Y-m-d h:i:s', $fechaUnix / 1000);
        } else {
          $datosLucy['fecha'] = '';
        }
        $datosLucy['nro_consultas'] = count($chatsFiltrados);
        $datosLucy['porce_consultas'] = (float)number_format((count($chatsFiltrados) * 100) / count($chatsNoFiltrados), 1, '.', ',');
      } else {
        $datosLucy['fuente'] = array('fuente' => 'Lucy', 'idFuente' => 'idLucy');
        $datosLucy['fecha'] = '';
        $datosLucy['nro_consultas'] = '0';
        $datosLucy['porce_consultas'] = '0';
      }
    } else {
      $datosLucy['fuente'] = array('fuente' => 'Lucy', 'idFuente' => 'idLucy');
      $datosLucy['fecha'] = '';
      $datosLucy['nro_consultas'] = '0';
      $datosLucy['porce_consultas'] = '0';
    }

    $historial['chats'] = $datosLucy;
    $historial['conversaciones'] = $chatsFiltrados;
    return $historial;
  }

  //obtener info preliminar  del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerPreliminarInvitacionSusp($niu, $fechaInicio, $fechaFin)
  {

    $datosInvitacionSusp = array();
    $historial = array();
    $invitacionSuspension = invitacionSusProgramadas($this->conHerokuChecSgcb, $niu, $fechaInicio, $fechaFin);

    if (count($invitacionSuspension['consulta_no_filtrada']) > 0) {
      $datosInvitacionSusp['fuente'] = array('fuente' => 'Invitación aviso susp SMS', 'idFuente' => 'idInvitacionSus');
      if (count($invitacionSuspension['consulta_filtrada']) > 0) {
        $datosInvitacionSusp['fecha'] = $invitacionSuspension['consulta_filtrada'][count($invitacionSuspension['consulta_filtrada']) - 1]->FECHA_PROMOCION_PROGRAMADAS;
      } else {
        $datosInvitacionSusp['fecha'] = '';
      }
      $datosInvitacionSusp['nro_consultas'] = count($invitacionSuspension['consulta_filtrada']);
      $datosInvitacionSusp['porce_consultas'] = (float)number_format((count($invitacionSuspension['consulta_filtrada']) * 100) / count($invitacionSuspension['consulta_no_filtrada']), 1, '.', ',');
    } else {
      $datosInvitacionSusp['fuente'] = array('fuente' => 'Invitación aviso susp SMS', 'idFuente' => 'idInvitacionSus');
      $datosInvitacionSusp['fecha'] = '';
      $datosInvitacionSusp['nro_consultas'] = '0';
      $datosInvitacionSusp['porce_consultas'] = '0';
    }


    $historial['aviso_susp'] = $datosInvitacionSusp;
    return $historial;
  }

  //obtener info preliminar  del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerPreliminarDinp($niu, $fechaInicio, $fechaFin)
  {
    $datosDinp = array();
    $historial = array();
    $dinp = difusion($this->conHerokuChecSgcb, $niu,  $fechaInicio, $fechaFin);

    if (count($dinp['aperturas_no_filtradas']) + count($dinp['cierres_no_filtrados'])) {
      $datosDinp['fuente'] = array('fuente' => 'DINP', 'idFuente' => 'idDinp');
      if (count($dinp['aperturas_filtradas']) > 0) {
        $datosDinp['fecha'] = $dinp['aperturas_filtradas'][count($dinp['aperturas_filtradas']) - 1]->FECHA_ENVIO_APERTURA;
      } else {
        $datosDinp['fecha'] = '';
      }
      $datosDinp['nro_consultas'] = count($dinp['aperturas_filtradas']) + count($dinp['cierres_filtrados']);
      $totalAperturCierreFiltrados = count($dinp['aperturas_filtradas']) + count($dinp['cierres_filtrados']);
      $totalAperturCierreNoFiltrados = count($dinp['aperturas_no_filtradas']) + count($dinp['cierres_no_filtrados']);
      $datosDinp['porce_consultas'] = (float)number_format(($totalAperturCierreFiltrados * 100) / $totalAperturCierreNoFiltrados, 1, '.', ',');
    } else {
      $datosDinp['fuente'] = array('fuente' => 'DINP', 'idFuente' => 'idDinp');
      $datosDinp['fecha'] = '';
      $datosDinp['nro_consultas'] = '0';
      $datosDinp['porce_consultas'] = '0';
    }

    $historial['dinp'] = $datosDinp;
    return $historial;
  }

  //obtener info preliminar  del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerPreliminarPromocionLucy($niu, $fechaInicio, $fechaFin)
  {
    $datosPromocion = array();
    $historial = array();
    $promocionLucy = getAcuseReciboHitorial_PromocionLucy($this->conHerokuChecSgcb, $niu, $fechaInicio, $fechaFin);

    if (count($promocionLucy['consulta_no_filtrada']) > 0) {
      $datosPromocion['fuente'] = array('fuente' => 'Promoción Lucy SMS', 'idFuente' => 'idPromocion');
      if (count($promocionLucy['consulta_filtrada'])) {
        $datosPromocion['fecha'] = $promocionLucy['consulta_filtrada'][0]->FECHA_ENTREGA;
      } else {
        $datosPromocion['fecha'] = '';
      }
      $datosPromocion['nro_consultas'] = count($promocionLucy['consulta_filtrada']);
      $datosPromocion['porce_consultas'] = (float)number_format((count($promocionLucy['consulta_filtrada']) * 100) / count($promocionLucy['consulta_no_filtrada']), 1, '.', ',');
    } else {
      $datosPromocion['fuente'] = array('fuente' => 'Promoción Lucy SMS', 'idFuente' => 'idPromocion');
      $datosPromocion['fecha'] = '';
      $datosPromocion['nro_consultas'] = '0';
      $datosPromocion['porce_consultas'] = '0';
    }

    $historial['pormocion_lucy'] = $datosPromocion;
    return $historial;
  }


  //obtener info preliminar  del usuarios(lucy, dinp, promoción lucy, etc)
  public function obtenerPreliminarLlamadas($niu, $fechaInicio, $fechaFin)
  {
    $historial = array();
    $llamadas = array();
    $llamadasdb = llamadasContact($this->conDifusion, $niu, $fechaInicio, $fechaFin);

    if (count($llamadasdb['consulta_no_filtrada']) > 0) {
      $llamadas['fuente'] = array('fuente' => 'Llamadas', 'idFuente' => 'idLlamadas');
      if (count($llamadasdb['consulta_filtrada']) > 0) {
        $llamadas['fecha'] = $llamadasdb['consulta_filtrada'][(count($llamadasdb['consulta_filtrada']) - 1)]->Fecha . ' ' . $llamadasdb['consulta_filtrada'][(count($llamadasdb['consulta_filtrada']) - 1)]->Hora;
      } else {
        $llamadas['fecha'] = '';
      }
      $llamadas['nro_consultas'] = count($llamadasdb['consulta_filtrada']);
      $llamadas['porce_consultas'] = (float)number_format((count($llamadasdb['consulta_filtrada']) * 100) / count($llamadasdb['consulta_no_filtrada']), 1, '.', ',');
    } else {
      $llamadas['fuente'] = array('fuente' => 'LLamadas', 'idFuente' => 'idLlamadas');
      $llamadas['fecha'] = '';
      $llamadas['nro_consultas'] = '0';
      $llamadas['porce_consultas'] = '0';
    }

    $historial['llamadas'] = $llamadas;
    return $historial;
  }

  //obtener info preliminar  de los fallbacks
  public function obtenerPreliminarFallback($fechaInicio, $fechaFin)
  {
    $datosPromocion = array();
    $datosDinp = array();
    $datosAvisoSusp = array();
    $datosLucy = array();
    $historial = array();
    $llamadas = array();
    $dateTimeInicio = new DateTime($fechaInicio);
    $dateTimeFinal = new DateTime($fechaFin);
    $fechaInicial = $dateTimeInicio->format('Uv');
    $fechafinal = $dateTimeFinal->format('Uv');

    //$chats = $this->getConversacionesLucy($niu, $fechaInicial, $fechafinal);
    $chats = getConversacionFallback($this->conDifusion, $this->conHerokuChec, $fechaInicio, $fechaFin, $fechaInicial, $fechafinal);
    $chatsFiltrados = array();


    if (count($chats) > 0) {
      foreach ($chats['chats_filtrados'] as $clave => $valor) {
        if (count($valor) != 0) {
          array_push($chatsFiltrados, $valor);
        }
      }
    }

    $newArray = array_reverse($chatsFiltrados);
    $historial['conversaciones'] = $newArray;

    return $historial;
  }


  //obtener cnversacion de un usurio con Lucy
  public function getConversacionesLucy($niu, $fechaInicio, $fechafin)
  {

    $conversacion = getConversacionLucy($this->conDifusion, $niu, $fechaInicio, $fechafin);
    return $conversacion;
  }

  //obtener las conversaciones con lucy
  public function getChatsUsuariosLucy()
  {

    $sesiones = array();

    $obtenerSesiones = orderSessions($this->conDifusion);
    foreach ($obtenerSesiones as $clave => $valor) {
      array_push($sesiones, $valor->sessionId);
    }

    $ordenarSesiones = array_values(array_unique($sesiones));

    $chats = getChacts($this->conDifusion, $ordenarSesiones);


    return $chats;
  }

  public function getFallbacks($fechaInicio, $fechaFin)
  {
    $fallBacks = filterFallbacks($this->conHerokuChec, $fechaInicio, $fechaFin);
    if ($fallBacks == false) {
      return 0;
    } else {
      return  $fallBacks->n;
    }
  }

  public function getAccesoSubmenu($fechainicio, $fechafin)
  {
    $accesosSubmenu = filterAccesosSubmenu($this->conHerokuChec, $fechainicio, $fechafin);
    return $accesosSubmenu;
  }

  //obtener los top de usuarios que mas consultan falla de energia y copia de factura
  public function getTopConsultas($fechainicio, $fechafin)
  {
    $resultado = array();
    $cont1 = 0;
    $cont2 = 0;
    $topConsultas = getTopDescargasReportes($this->conHerokuChec, $fechainicio, $fechafin);

    //$this->getTopsConsultas($topConsultas['descargas']);
    if ($topConsultas['descargas'] != 0) {
      foreach ($topConsultas['descargas'] as $claveDescarga => $valorDescarga) {
        if ($valorDescarga->NIU != '369741402' && $cont1 <= 6) {
          array_push($resultado, $valorDescarga);
          $cont1 += 1;
        }
      }
    }

    if ($topConsultas['reportes'] != 0) {
      foreach ($topConsultas['reportes'] as $claveReporte => $valorReporte) {
        if ($valorReporte->NIU != '369741402' && $cont2 <= 6) {
          array_push($resultado, $valorReporte);
          $cont2 += 1;
        }
      }
    }

    //ordenamiento de los municipios por numero de consultas
    $volume = [];
    foreach ($resultado as $key => $row) {
      $volume[$key]  = $row->NUMCONSULTAS;
    }
    array_multisort($volume, SORT_DESC, $resultado);

    return $resultado;
  }

  //Acceso al menu de Lucy
  public function getAccesosMenuMes($fechaInicio, $fechaFin)
  {
    $resultados = array();
    $result = filterAccesosMenuMes($this->conHerokuChec, $fechaInicio, $fechaFin);
    //$result = filterResultadoMenus($this->conDifusion, $fechaInicio, $fechaFin);
    //$totalResult = totalAccesosMenuMes($this->conDifusion);
    //$acceso_menu_total =  $this->accesosMenu($resultTotal);
    $acceso_menu_filtrado =  $this->accesosMenu($result);
    $total = $acceso_menu_filtrado['faltaEnergia'] + $acceso_menu_filtrado['copia'] + $acceso_menu_filtrado['vacantes'] + $acceso_menu_filtrado['pqr'] + $acceso_menu_filtrado['pagoFactura'] + $acceso_menu_filtrado['asesorRemoto'] + $acceso_menu_filtrado['puntosAtencion'] + $acceso_menu_filtrado['fraudes'];
    $accesosFiltrados = array();
    $accesosFiltrados[0] = 0;
    $accesosFiltrados[1] = 0;
    $accesosFiltrados[2] = 0;
    $accesosFiltrados[3] = 0;
    $accesosFiltrados[4] = 0;
    $accesosFiltrados[5] = 0;
    $accesosFiltrados[6] = 0;
    $accesosFiltrados[7] = 0;


    if (count($result) > 0) {
      foreach ($result as $key => $value) {
        $fecha = strtotime($value->FECHA_RESULTADO);
        switch (date('M', $fecha)) {
          case 'Jan':
            if (isset($resultados[0])) {
              if (isset($resultados[0][$value->MENU])) {
                $resultados[0][$value->MENU] += 1;
              } else {
                $resultados[0][$value->MENU] = 1;
              }
            } else {
              $resultados[0]['Falta de Energia'] = 0;
              $resultados[0]['Pqr'] = 0;
              $resultados[0]['Puntos de Atencion'] = 0;
              $resultados[0]['Vacantes'] = 0;
              $resultados[0]['Pago factura'] = 0;
              $resultados[0]['Fraudes'] = 0;
              $resultados[0]['Copia factura'] = 0;
              $resultados[0]['Asesor remoto'] = 0;
              $resultados[0][$value->MENU] = 1;
            }
            break;
          case 'Feb':
            if (isset($resultados[1])) {
              if (isset($resultados[1][$value->MENU])) {
                $resultados[1][$value->MENU] += 1;
              } else {
                $resultados[1][$value->MENU] = 1;
              }
            } else {
              $resultados[1]['Falta de Energia'] = 0;
              $resultados[1]['Pqr'] = 0;
              $resultados[1]['Puntos de Atencion'] = 0;
              $resultados[1]['Vacantes'] = 0;
              $resultados[1]['Pago factura'] = 0;
              $resultados[1]['Fraudes'] = 0;
              $resultados[1]['Copia factura'] = 0;
              $resultados[1]['Asesor remoto'] = 0;
              $resultados[1][$value->MENU] = 1;
            }
            break;
          case 'Mar':
            if (isset($resultados[2])) {
              if (isset($resultados[2][$value->MENU])) {
                $resultados[2][$value->MENU] += 1;
              } else {
                $resultados[2][$value->MENU] = 1;
              }
            } else {
              $resultados[2]['Falta de Energia'] = 0;
              $resultados[2]['Pqr'] = 0;
              $resultados[2]['Puntos de Atencion'] = 0;
              $resultados[2]['Vacantes'] = 0;
              $resultados[2]['Pago factura'] = 0;
              $resultados[2]['Fraudes'] = 0;
              $resultados[2]['Copia factura'] = 0;
              $resultados[2]['Asesor remoto'] = 0;
              $resultados[2][$value->MENU] = 1;
            }
            break;
          case 'Apr':
            if (isset($resultados[3])) {
              if (isset($resultados[3][$value->MENU])) {
                $resultados[3][$value->MENU] += 1;
              } else {
                $resultados[3][$value->MENU] = 1;
              }
            } else {
              $resultados[3]['Falta de Energia'] = 0;
              $resultados[3]['Pqr'] = 0;
              $resultados[3]['Puntos de Atencion'] = 0;
              $resultados[3]['Vacantes'] = 0;
              $resultados[3]['Pago factura'] = 0;
              $resultados[3]['Fraudes'] = 0;
              $resultados[3]['Copia factura'] = 0;
              $resultados[3]['Asesor remoto'] = 0;
              $resultados[3][$value->MENU] = 1;
            }
            break;
          case 'May':
            if (isset($resultados[4])) {
              if (isset($resultados[4][$value->MENU])) {
                $resultados[4][$value->MENU] += 1;
              } else {
                $resultados[4][$value->MENU] = 1;
              }
            } else {
              $resultados[4]['Falta de Energia'] = 0;
              $resultados[4]['Pqr'] = 0;
              $resultados[4]['Puntos de Atencion'] = 0;
              $resultados[4]['Vacantes'] = 0;
              $resultados[4]['Pago factura'] = 0;
              $resultados[4]['Fraudes'] = 0;
              $resultados[4]['Copia factura'] = 0;
              $resultados[4]['Asesor remoto'] = 0;
              $resultados[4][$value->MENU] = 1;
            }
            break;
          case 'Jun':
            if (isset($resultados[5])) {
              if (isset($resultados[5][$value->MENU])) {
                $resultados[5][$value->MENU] += 1;
              } else {
                $resultados[5][$value->MENU] = 1;
              }
            } else {
              $resultados[5]['Falta de Energia'] = 0;
              $resultados[5]['Pqr'] = 0;
              $resultados[5]['Puntos de Atencion'] = 0;
              $resultados[5]['Vacantes'] = 0;
              $resultados[5]['Pago factura'] = 0;
              $resultados[5]['Fraudes'] = 0;
              $resultados[5]['Copia factura'] = 0;
              $resultados[5]['Asesor remoto'] = 0;
              $resultados[5][$value->MENU] = 1;
            }
            break;
          case 'Jul':
            if (isset($resultados[6])) {
              if (isset($resultados[6][$value->MENU])) {
                $resultados[6][$value->MENU] += 1;
              } else {
                $resultados[6][$value->MENU] = 1;
              }
            } else {
              $resultados[6]['Falta de Energia'] = 0;
              $resultados[6]['Pqr'] = 0;
              $resultados[6]['Puntos de Atencion'] = 0;
              $resultados[6]['Vacantes'] = 0;
              $resultados[6]['Pago factura'] = 0;
              $resultados[6]['Fraudes'] = 0;
              $resultados[6]['Copia factura'] = 0;
              $resultados[6]['Asesor remoto'] = 0;
              $resultados[6][$value->MENU] = 1;
            }
            break;
          case 'Aug':
            if (isset($resultados[7])) {
              if (isset($resultados[7][$value->MENU])) {
                $resultados[7][$value->MENU] += 1;
              } else {
                $resultados[7][$value->MENU] = 1;
              }
            } else {
              $resultados[7]['Falta de Energia'] = 0;
              $resultados[7]['Pqr'] = 0;
              $resultados[7]['Puntos de Atencion'] = 0;
              $resultados[7]['Vacantes'] = 0;
              $resultados[7]['Pago factura'] = 0;
              $resultados[7]['Fraudes'] = 0;
              $resultados[7]['Copia factura'] = 0;
              $resultados[7]['Asesor remoto'] = 0;
              $resultados[7][$value->MENU] = 1;
            }
            break;
          case 'Sep':
            if (isset($resultados[8])) {
              if (isset($resultados[8][$value->MENU])) {
                $resultados[8][$value->MENU] += 1;
              } else {
                $resultados[8][$value->MENU] = 1;
              }
            } else {
              $resultados[8]['Falta de Energia'] = 0;
              $resultados[8]['Pqr'] = 0;
              $resultados[8]['Puntos de Atencion'] = 0;
              $resultados[8]['Vacantes'] = 0;
              $resultados[8]['Pago factura'] = 0;
              $resultados[8]['Fraudes'] = 0;
              $resultados[8]['Copia factura'] = 0;
              $resultados[8]['Asesor remoto'] = 0;
              $resultados[8][$value->MENU] = 1;
            }
            break;
          case 'Oct':
            if (isset($resultados[9])) {
              if (isset($resultados[9][$value->MENU])) {
                $resultados[9][$value->MENU] += 1;
              } else {
                $resultados[9][$value->MENU] = 1;
              }
            } else {
              $resultados[9]['Falta de Energia'] = 0;
              $resultados[9]['Pqr'] = 0;
              $resultados[9]['Puntos de Atencion'] = 0;
              $resultados[9]['Vacantes'] = 0;
              $resultados[9]['Pago factura'] = 0;
              $resultados[9]['Fraudes'] = 0;
              $resultados[9]['Copia factura'] = 0;
              $resultados[9]['Asesor remoto'] = 0;
              $resultados[9][$value->MENU] = 1;
            }
            break;
          case 'Nov':
            if (isset($resultados[10])) {
              if (isset($resultados[10][$value->MENU])) {
                $resultados[10][$value->MENU] += 1;
              } else {
                $resultados[10][$value->MENU] = 1;
              }
            } else {
              $resultados[10]['Falta de Energia'] = 0;
              $resultados[10]['Pqr'] = 0;
              $resultados[10]['Puntos de Atencion'] = 0;
              $resultados[10]['Vacantes'] = 0;
              $resultados[10]['Pago factura'] = 0;
              $resultados[10]['Fraudes'] = 0;
              $resultados[10]['Copia factura'] = 0;
              $resultados[10]['Asesor remoto'] = 0;
              $resultados[10][$value->MENU] = 1;
            }
            break;
          case 'Dec':
            if (isset($resultados[11])) {
              if (isset($resultados[11][$value->MENU])) {
                $resultados[11][$value->MENU] += 1;
              } else {
                $resultados[11][$value->MENU] = 1;
              }
            } else {
              $resultados[11]['Falta de Energia'] = 0;
              $resultados[11]['Pqr'] = 0;
              $resultados[11]['Puntos de Atencion'] = 0;
              $resultados[11]['Vacantes'] = 0;
              $resultados[11]['Pago factura'] = 0;
              $resultados[11]['Fraudes'] = 0;
              $resultados[11]['Copia factura'] = 0;
              $resultados[11]['Asesor remoto'] = 0;
              $resultados[11][$value->MENU] = 1;
            }
            break;
        }
      }
    }


    foreach ($resultados as $clave => $valor) {

      foreach ($valor as $key => $value) {

        switch ($key) {

          case 'Falta de Energia':
            $accesosFiltrados[0] += $value;
            break;
          case 'Pqr':
            $accesosFiltrados[1] += $value;
            break;
          case 'Puntos de Atencion':
            $accesosFiltrados[2] += $value;
            break;
          case 'Vacantes':
            $accesosFiltrados[3] += $value;
            break;
          case 'Pago factura':
            $accesosFiltrados[4] += $value;
            break;
          case 'Fraudes':
            $accesosFiltrados[5] += $value;
            break;
          case 'Copia factura':
            $accesosFiltrados[6] += $value;
            break;
          case 'Asesor remoto':
            $accesosFiltrados[7] += $value;
            break;
        }
      }
    }

    if ($total <= 0) {
      $resultados[12]['porcen_Falta_Energia'] = 0;
      $resultados[12]['porcen_Pqr'] = 0;
      $resultados[12]['porcen_Puntos_Atencion'] = 0;
      $resultados[12]['porcen_Vacantes'] = 0;
      $resultados[12]['porcen_Pago_factura'] = 0;
      $resultados[12]['porcen_Fraudes'] = 0;
      $resultados[12]['porcen_Copia_factura'] = 0;
      $resultados[12]['porcen_Asesor_remoto'] = 0;
    } else {
      //$total = $this->totalAccesoLucy($totalResult);
      $resultados[12]['porcen_Falta_Energia'] = (float)number_format((($accesosFiltrados[0] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Pqr'] = (float)number_format((($accesosFiltrados[1] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Puntos_Atencion'] = (float)number_format((($accesosFiltrados[2] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Vacantes'] = (float)number_format((($accesosFiltrados[3] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Pago_factura'] = (float)number_format((($accesosFiltrados[4] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Fraudes'] = (float)number_format((($accesosFiltrados[5] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Copia_factura'] = (float)number_format((($accesosFiltrados[6] * 100) / $total), 1, '.', ',');
      $resultados[12]['porcen_Asesor_remoto'] = (float)number_format((($accesosFiltrados[7] * 100) / $total), 1, '.', ',');
    }



    return $resultados; //2877
  }

  public function totalAccesoLucy($result)
  {
    $resultados = [];
    $resultados[0] = 0;
    $resultados[1] = 0;
    $resultados[2] = 0;
    $resultados[3] = 0;
    $resultados[4] = 0;
    $resultados[5] = 0;
    $resultados[6] = 0;
    $resultados[7] = 0;

    foreach ($result as $key => $value) {

      switch ($value->MENU) {

        case 'Falta de Energia':
          $resultados[0] += 1;
          break;
        case 'Pqr':
          $resultados[1] += 1;
          break;
        case 'Puntos de Atencion':
          $resultados[2] += 1;
          break;
        case 'Vacantes':
          $resultados[3] += 1;
          break;
        case 'Pago factura':
          $resultados[4] += 1;
          break;
        case 'Fraudes':
          $resultados[5] += 1;
          break;
        case 'Copia factura':
          $resultados[6] += 1;
          break;
        case 'Asesor remoto':
          $resultados[7] += 1;
          break;
      }
    }

    return $resultados;
  }

  //modulo de reportes
  //usuarios totales de lucy
  public function getUsuariosTotales($anio, $mes)
  {
    $ordenarUsarios = [];
    //$users = getUsuarios($this->conDifusion, $anio, $mes);
    $users = getUsuarios2($this->conHerokuChec, $anio, $mes);


    if (count($users) == 0) {
      return 'No hay datos';
    } else {
      /*foreach ($users as $clave => $valor) {
        foreach ($valor as $value) {
          array_push($ordenarUsarios, $value);
        }
      }
      return $this->obtenerUsuariosTotales($ordenarUsarios, "usuariosTtotalesLucy.xlsx");*/
      return $users;
    }
  }

  public function obtenerUsuariosTotales($users, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("Usuarios totales")
      ->setSubject("Usuarios totales")
      ->setDescription("LISTADO DE USUARIOS TOTALES QUE HAN CONSULTADO LUCY")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'NOMBRE');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'DIRECCION');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'MUNICIPIO');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'ESTRATO');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'NIU');

    $i = 2;

    foreach ($users as $key => $value) {
      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $value[0]->NOMBRE);
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value[0]->DIRECCION);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value[0]->MUNICIPIO);
      $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value[0]->ESTRATO);
      $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value[0]->NIU);

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('NOMBRE');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }

  //reportes de calificaciones negativas
  public function getcalnegativas($anio, $mes)
  {
    $calnegativas = getcalnegativas($this->conHerokuChec, $mes, $anio);
    $totalCalNegativas = array();
    if (count($calnegativas) == 0) {
      $newCalNegativas = new stdClass();
      $newCalNegativas->CALIFICACION = '';
      $newCalNegativas->FECHA = '';
      $newCalNegativas->VOC = '';
      $newCalNegativas->SOURCE = '';
      $newCalNegativas->CUENTA = '';
      return [$newCalNegativas];
    } else {

      foreach ($calnegativas as $clave => $valor) {
        $newCalNegativas = new stdClass();
        if (isset($valor->calificacion)) {
          $newCalNegativas->CALIFICACION = $valor->calificacion;
        } else {
          $newCalNegativas->CALIFICACION = '';
        }

        if (isset($valor->fecha)) {
          $newCalNegativas->FECHA = $valor->fecha;
        } else {
          $newCalNegativas->FECHA = '';
        }

        if (isset($valor->voc)) {
          $newCalNegativas->VOC = $valor->voc;
        } else {
          $newCalNegativas->VOC = '';
        }

        if (isset($valor->source)) {
          $newCalNegativas->SOURCE = $valor->source;
        } else {
          $newCalNegativas->SOURCE = '';
        }

        if (isset($valor->niu)) {
          $newCalNegativas->CUENTA = $valor->niu;
        } else {
          $newCalNegativas->CUENTA = '';
        }

        array_push($totalCalNegativas, $newCalNegativas);
      }
      //return $this->crearArchivogetcalnegativas($calnegativas, "calificacionesLucy.xlsx");
      return $totalCalNegativas;
    }
  }

  public function crearArchivogetcalnegativas($calnegativas, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("Calificaciones Negativas")
      ->setSubject("Calificaciones Negativas")
      ->setDescription("LISTADO DE CALIFICACIONES NEGATIVAS DE LUCY")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'CALIFICACION');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'FECHA');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'VOC');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'SOURCE');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'NIU');

    $i = 2;

    foreach ($calnegativas as $key => $value) {
      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $value->calificacion);
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value->fecha);
      if (isset($value->voc)) {
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value->voc);
      }
      if (isset($value->source)) {
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value->source);
      }
      if (isset($value->niu)) {
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value->niu);
      }

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('CALIFICACION');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }



  //reportes de usuarios frecuentes
  public function getConsultasUsuarios($month, $year)
  {
    $consultasUsuarios = getConsultas_Usuarios($this->conHerokuChec, $year, $month);
    $totalData = array();
    if (count($consultasUsuarios) == 0) {
      $newData = new stdClass();
      $newData->FECHA = '';
      $newData->SOURCE = '';
      $newData->IDCONVERSATION = '';
      $newData->CUENTA = '';
      $newData->CANTIDAD_CONSULTAS = '';
      $newData->MUNICIPIO = '';
      $newData->CELULAR = '';
      $newData->SEGMENTO = '';
      return [$newData];
    } else {

      foreach ($consultasUsuarios as $clave => $valor) {
        $newData = new stdClass();
        if (isset($valor->fecha)) {
          $newData->FECHA = $valor->fecha;
        } else {
          $newData->FECHA = '';
        }

        if (isset($valor->source)) {
          $newData->SOURCE = $valor->source;
        } else {
          $newData->SOURCE = '';
        }

        if (isset($valor->idconversation)) {
          $newData->IDCONVERSATION = $valor->idconversation;
        } else {
          $newData->IDCONVERSATION = '';
        }

        if (isset($valor->niu)) {
          $newData->CUENTA = $valor->niu;
        } else {
          $newData->CUENTA = '';
        }

        if (isset($valor->total)) {
          $newData->CANTIDAD_CONSULTAS = $valor->total;
        } else {
          $newData->CANTIDAD_CONSULTAS = '';
        }

        if (count($valor->usuario) > 0) {
          if (isset($valor->usuario[0]->MUNICIPIO)) {
            $newData->MUNICIPIO = $valor->usuario[0]->MUNICIPIO;
          } else {
            $newData->MUNICIPIO = '';
          }

          if (isset($valor->usuario[0]->CELULAR)) {
            $newData->CELULAR = $valor->usuario[0]->CELULAR;
          } else {
            $newData->CELULAR = '';
          }

          if (isset($valor->usuario[0]->SEGMENTO)) {
            $newData->SEGMENTO = $valor->usuario[0]->SEGMENTO;
          } else {
            $newData->SEGMENTO = '';
          }
        }
        array_push($totalData, $newData);
      }
      return $totalData;
      //return $this->crearArchivogetConsultasUsuarios($consultasUsuarios, "usuariosFrecuentesLucy.xlsx");
    }
  }

  public function crearArchivogetConsultasUsuarios($usuarios, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("CONSULTAS DE USUARIOS")
      ->setSubject("CONSULTAS DE USUARIOS")
      ->setDescription("CONSULTAS DE USUARIOS")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'FECHA');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'SOURCE');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'IDCONVERSATION');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'NIU');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'CANTIDAD_CONSULTAS');
    $objPHPExcel->getActiveSheet()->setCellValue('F1', 'MUNICIPIO');
    $objPHPExcel->getActiveSheet()->setCellValue('G1', 'CELULAR');
    $objPHPExcel->getActiveSheet()->setCellValue('H1', 'SEGMENTO');

    $i = 2;

    foreach ($usuarios as $key => $value) {
      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $value->fecha);
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value->source);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value->idconversation);
      $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value->niu);
      $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value->total);
      if (count($value->usuario) > 0) {
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $value->usuario[0]->MUNICIPIO);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value->usuario[0]->CELULAR);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $value->usuario[0]->SEGMENTO);
      }

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('CONSULTAS DE USUARIOS');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }

  //reportes de usuarios por segmento
  public function getUsuariosSegmentos($month, $year)
  {
    $UsuariosSegmentos = getUsuarios_Segmentos($this->conHerokuChec, $year, $month);
    $totalData = array();
    if (count($UsuariosSegmentos) == 0) {
      $newData = new stdClass();
      $newData->SOURCE = '';
      $newData->CUENTA = '';
      $newData->SEGMENTO = '';
      return [$newData];
    } else {

      foreach ($UsuariosSegmentos as $clave => $valor) {
        $newData = new stdClass();

        if (isset($valor->source)) {
          $newData->SOURCE = $valor->source;
        } else {
          $newData->SOURCE = '';
        }

        if (isset($valor->niu)) {
          $newData->CUENTA = $valor->niu;
        } else {
          $newData->CUENTA = '';
        }

        if ($valor->segmento[0]) {
          if (isset($valor->segmento[0])) {
            $newData->SEGMENTO = $valor->segmento[0];
          } else {
            $newData->SEGMENTO = '';
          }
        }

        array_push($totalData, $newData);
      }
      //return $this->crearArchivogetUsuariosSegmentos($UsuariosSegmentos, "usuariosSegmentoLucy.xlsx");
      return $totalData;
    }
  }



  public function crearArchivogetUsuariosSegmentos($usuariosSegmentos, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("SEGMENTOS")
      ->setSubject("SEGMENTOS")
      ->setDescription("USUARIOS POR SEGMENTO QUE CONSULTAN LUCY")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'SOURCE');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'NIU');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'SEGMENTO');

    $i = 2;

    foreach ($usuariosSegmentos as $key => $value) {
      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $value->source);
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value->niu);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value->segmento[0]);

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('SEGMENTOS');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }


  //reporte de usuarios inscritos
  public function getUsuariosInscritos($anio, $mes)
  {
    //$usuarios = getUsuarios_inscritos($this->conDifusion, $mes, $anio);
    $usuarios = getUsuarios_inscritos2($this->conDifusion, $mes, $anio);

    $newData = new stdClass();
    $totalData = array();
    if (count($usuarios) == 0) {
      $newData->NOMBRE = '';
      $newData->DIRECCION = '';
      $newData->MUNICIPIO = '';
      $newData->ESTRATO = '';
      $newData->CUENTA = '';
      return [$newData];
    } else {
      //return $this->crearArchivoUsuariosInscritos($usuarios, "usuariosInscritosDinp.xlsx");
      return $usuarios;
    }
  }


  public function crearArchivoUsuariosInscritos($users, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("Usuarios inscritos DINP")
      ->setSubject("Usuarios inscritos DINP")
      ->setDescription("LISTADO DE USUARIOS INSCRITOS DINP")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'NOMBRE');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'DIRECCION');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'MUNICIPIO');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'ESTRATO');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'NIU');

    $i = 2;

    foreach ($users as $key => $value) {
      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $value[0]->NOMBRE);
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value[0]->DIRECCION);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value[0]->MUNICIPIO);
      $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value[0]->ESTRATO);
      $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value[0]->NIU);

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('NOMBRE');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }

  //reporte de usuarios a los que se les ha enviado mensaje de difusion
  public function getAcuseReciboDifusion($month, $year)
  {
    $acuseRecibo = getAcuseRecibo_Difusion($this->conHerokuChecSgcb, $year, $month);
    $totalData = array();
    if (count($acuseRecibo) == 0) {
      $newData = new stdClass();
      $newData->TIPO_MENSAJE = '';
      $newData->NIU = '';
      $newData->TELEFONO = '';
      $newData->ESTADO_APERTURA = '';
      $newData->FECHA_APERTURA = '';
      $newData->ESTADO_CIERRE = '';
      $newData->FECHA_CIERRE = '';
      return [$newData];
    } else {

      foreach ($acuseRecibo as $clave => $valor) {
        $newData = new stdClass();
        $newData->TIPO_MENSAJE = 'INTERRUPCIÓN NO PROGRAMADA';

        if (isset($valor->niu)) {
          $newData->CUENTA = $valor->niu;
        } else {
          $newData->CUENTA = '';
        }

        if (isset($valor->telefono)) {
          $newData->TELEFONO = $valor->telefono;
        } else {
          $newData->TELEFONO = '';
        }

        if (isset($valor->estadoApertura)) {
          $newData->ESTADO_APERTURA = $valor->estadoApertura;
        } else {
          $newData->ESTADO_APERTURA = '';
        }

        if (isset($valor->fechaApertura)) {
          $newData->FECHA_APERTURA = $valor->fechaApertura;
        } else {
          $newData->FECHA_APERTURA = '';
        }

        if (isset($valor->estadoCierre)) {
          $newData->ESTADO_CIERRE = $valor->estadoCierre;
        } else {
          $newData->ESTADO_CIERRE = '';
        }

        if (isset($valor->fechaCierre)) {
          $newData->FECHA_CIERRE = $valor->fechaCierre;
        } else {
          $newData->FECHA_CIERRE = '';
        }

        array_push($totalData, $newData);
      }
      //return $this->crearArchivogetAcuseReciboDifusion($acuseRecibo, "usuariosRecibidosMsmDinp.xlsx");
      return $totalData;
    }
  }

  public function crearArchivogetAcuseReciboDifusion($acuseRecibo, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("TEL No DE CUENTA DIFUNDIDOS")
      ->setSubject("SEGMENTOS SISTEMA DE DIFUSION")
      ->setDescription("ACUSE DE RECIBO")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'TIPO_MENSAJE');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'NIU');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'TELEFONO');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'ESTADO_APERTURA');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'FECHA_APERTURA');
    $objPHPExcel->getActiveSheet()->setCellValue('F1', 'ESTADO_CIERRE');
    $objPHPExcel->getActiveSheet()->setCellValue('G1', 'FECHA_CIERRE');

    $i = 2;

    foreach ($acuseRecibo as $key => $value) {

      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'INTERRUPCIÓN NO PROGRAMADA');
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value->niu);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value->telefono);
      $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value->estadoApertura);
      $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value->fechaApertura);
      $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $value->estadoCierre);
      $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value->fechaCierre);

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('TEL No DE CUENTA DIFUNDIDOS');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }

  //reporte de usuarios a los que se les ha enviado mensaje de difusion por segmento
  public function getAcuseReciboDifusionSegmento($month, $year)
  {
    $acuseRecibo = getAcuseRecibo_DifusionSegmntos2($this->conHerokuChecSgcb, $month, $year);

    $newData = new stdClass();
    $totalData = array();
    if (count($acuseRecibo) == 0) {
      $newData->TIPO_MENSAJE = 'INTERRUPCIÓN NO PROGRAMADA';
      $newData->NIU = '';
      $newData->TELEFONO = '';
      $newData->ESTADO_APERTURA = '';
      $newData->FECHA_APERTURA = '';
      $newData->ESTADO_CIERRE = '';
      $newData->FECHA_CIERRE = '';
      $newData->SEGMENTO = '';
      return [$newData];
    } else {

      //return $this->crearArchivogetAcuseReciboDifusionDifusion($acuseRecibo, "usuariosRecibidosMsmDinpSegmento.xlsx");
      return $acuseRecibo;
    }
  }

  public function crearArchivogetAcuseReciboDifusionDifusion($acuseRecibo, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("TEL No DE CUENTA DIFUNDIDOS POR SEGMENTO")
      ->setSubject("SEGMENTOS SISTEMA DE DIFUSION")
      ->setDescription("ACUSE DE RECIBO")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'TIPO_MENSAJE');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'NIU');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'TELEFONO');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'ESTADO_APERTURA');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'FECHA_APERTURA');
    $objPHPExcel->getActiveSheet()->setCellValue('F1', 'ESTADO_CIERRE');
    $objPHPExcel->getActiveSheet()->setCellValue('G1', 'FECHA_CIERRE');
    $objPHPExcel->getActiveSheet()->setCellValue('G1', 'SEGMENTO');

    $i = 2;

    foreach ($acuseRecibo as $key => $value) {

      $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'INTERRUPCIÓN NO PROGRAMADA');
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value['niu']);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value['telefono']);
      $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value['estadoApertura']);
      $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value['fechaApertura']);
      $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $value['estadoCierre']);
      $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value['fechaCierre']);
      $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value['segmento']);

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('MSM DIFUNDIDOS POR SEGMENTO');
    $objPHPExcel->setActiveSheetIndex(0);

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }

  //reporte usuarios que han recibido mensajes de promoción
  public function getAcuseReciboPromocion($month, $year)
  {
    $acuseRecibo = getAcuseRecibo_DifusionPromocion2($this->conHerokuChecSgcb, $year, $month);
    $newData = new stdClass();

    if (count($acuseRecibo) == 0) {
      $newData->TIPO_PROMOCION = '';
      $newData->NIU = '';
      $newData->TELEFONO = '';
      $newData->ESTADO_PROMOCION = '';
      $newData->FECHA_PROMOCION = '';
      return [$newData];
    } else {

      //return $this->crearArchivogetAcuseReciboPromocion($acuseRecibo, "usuariosRecibidosMsmDinpPromocion.xlsx");
      return $acuseRecibo;
    }
  }

  public function crearArchivogetAcuseReciboPromocion($acuseRecibo, $file)
  {
    require_once "PHPExcel-1.8.1/Classes/PHPExcel.php";
    //require_once "./PHPExcel-1.8.1/Classes/PHPExcel/Writer/Excel2007/";
    require_once 'IOFactory.php';

    $nombreArchivo = '../../assets/archivosDescargar/' . $file;

    // Crea un nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();

    // Establecer propiedades
    $objPHPExcel->getProperties()
      ->setCreator("Datalab")
      ->setLastModifiedBy("Datalab")
      ->setTitle("TEL No DE CUENTA DIFUNDIDOS")
      ->setSubject("SEGMENTOS SISTEMA DE DIFUSION")
      ->setDescription("ACUSE DE RECIBO")
      ->setKeywords("Excel Office")
      ->setCategory("Excel");

    //Asigno la hoja de calculo activa
    $objPHPExcel->setActiveSheetIndex(0);

    $objPHPExcel->getActiveSheet()->setCellValue('A1', 'TIPO_PROMOCION');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', 'NIU');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', 'TELEFONO');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', 'ESTADO_PROMOCION');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', 'FECHA_PROMOCION');

    $i = 2;


    foreach ($acuseRecibo as $key => $value) {
      if (isset($value->fechaPromocion)) {

        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'LUCY');
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value->estadoPromocion);
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value->fechaPromocion);
      } else if (isset($value->fechaPromocionProgramadas)) {

        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'INCRIPCIÓN A SUSPENSIONES PROGRAMADAS');
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value->estadoPromocionProgramadas);
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value->fechaPromocionProgramadas);
      }
      $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value->niu);
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value->telefono);

      $i++;
    }
    $objPHPExcel->getActiveSheet()->setTitle('TEL No DE CUENTA DIFUNDIDOS');
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($nombreArchivo);
    chmod($nombreArchivo, 0666);

    return 'ok';
  }


  //modulo de contact center

  //llamadas cuentas validas
  public function LlamadasCuentasValidas($fechainicio, $fechafin, $municipio, $ubicacion)
  {
    $cunetasValidas = [];
    if (strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasCuentasValidas($this->conDifusion, $fechainicio, $fechafin);
      if (gettype($llamadasCuentasValidas['cuentas_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['cuentas_validas'] * 100) / $llamadasCuentasValidas['cuentas_totales']->n), 1, '.', ',');
      }
      $cunetasValidas['num_cuentas_validas'] = $llamadasCuentasValidas['cuentas_validas'];
      $cunetasValidas['por_cuentas_validas'] =  $porcentajeLlamadasCuentasValidas;
    } else if (!strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasCuentasValidasXMunicipios($this->conDifusion, $fechainicio, $fechafin, $municipio);
      if (gettype($llamadasCuentasValidas['cuentas_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['cuentas_validas'] * 100) / $llamadasCuentasValidas['cuentas_totales']->n), 1, '.', ',');
      }
      $cunetasValidas['num_cuentas_validas'] = $llamadasCuentasValidas['cuentas_validas'];
      $cunetasValidas['por_cuentas_validas'] =  $porcentajeLlamadasCuentasValidas;
    } else if (strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasCuentasValidasXUbicacion2($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
      if (gettype($llamadasCuentasValidas['cuentas_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
        $cunetasValidas['num_cuentas_validas'] = $llamadasCuentasValidas['cuentas_validas'];
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['cuentas_validas']->n * 100) / $llamadasCuentasValidas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidas['num_cuentas_validas'] = $llamadasCuentasValidas['cuentas_validas']->n;
      }
      $cunetasValidas['por_cuentas_validas'] =  $porcentajeLlamadasCuentasValidas;
    } else if (!strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasCuentasValidasXUbicacionMunicipio2($this->conDifusion, $fechainicio, $fechafin, $municipio, $ubicacion);
      if (gettype($llamadasCuentasValidas['cuentas_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
        $cunetasValidas['num_cuentas_validas'] = $llamadasCuentasValidas['cuentas_validas'];
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['cuentas_validas']->n * 100) / $llamadasCuentasValidas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidas['num_cuentas_validas'] = $llamadasCuentasValidas['cuentas_validas']->n;
      }
      $cunetasValidas['por_cuentas_validas'] =  $porcentajeLlamadasCuentasValidas;
    }

    return $cunetasValidas;
  }


  //llamadas telefono validas
  public function LlamadasTelefonoValidas($fechainicio, $fechafin, $municipio, $ubicacion)
  {
    $cunetasValidas = [];
    if (strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasTelefonosValidas3($this->conDifusion, $fechainicio, $fechafin);
      if (gettype($llamadasCuentasValidas['telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
        $cunetasValidas['num_telefonos_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['telefonos_validas']->n * 100) / $llamadasCuentasValidas['telefonos_totales']->n), 1, '.', ',');
        $cunetasValidas['num_telefonos_validos'] = $llamadasCuentasValidas['telefonos_validas']->n;
      }
      $cunetasValidas['por_telefonos_validos'] =  $porcentajeLlamadasCuentasValidas;
    } else if (!strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

      $llamadasCuentasValidas = getLlamadasTelefonosValidasXMunicipios3($this->conDifusion, $fechainicio, $fechafin, $municipio);
      if (gettype($llamadasCuentasValidas['telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
        $cunetasValidas['num_telefonos_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['telefonos_validas']->n * 100) / $llamadasCuentasValidas['telefonos_totales']->n), 1, '.', ',');
        $cunetasValidas['num_telefonos_validos'] = $llamadasCuentasValidas['telefonos_validas']->n;
      }
      $cunetasValidas['por_telefonos_validos'] =  $porcentajeLlamadasCuentasValidas;
    } else if (strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasTelefonosValidasXUbicacion2($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
      if (gettype($llamadasCuentasValidas['telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
        $cunetasValidas['num_telefonos_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['telefonos_validas']->n * 100) / $llamadasCuentasValidas['telefonos_totales']->n), 1, '.', ',');
        $cunetasValidas['num_telefonos_validos'] = $llamadasCuentasValidas['telefonos_validas']->n;
      }
      $cunetasValidas['por_telefonos_validos'] =  $porcentajeLlamadasCuentasValidas;
    } else if (!strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {
      $llamadasCuentasValidas = getLlamadasTelefonosValidasXUbicacionMunicipio3($this->conDifusion, $fechainicio, $fechafin, $municipio, $ubicacion);
      if (gettype($llamadasCuentasValidas['telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidas = 0;
        $cunetasValidas['num_telefonos_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidas = (float)number_format((($llamadasCuentasValidas['telefonos_validas']->n * 100) / $llamadasCuentasValidas['telefonos_totales']->n), 1, '.', ',');
        $cunetasValidas['num_telefonos_validos'] = $llamadasCuentasValidas['telefonos_validas']->n;
      }
      $cunetasValidas['por_telefonos_validos'] =  $porcentajeLlamadasCuentasValidas;
    }

    return $cunetasValidas;
  }


  //llamadas cuentas y telefonos validos
  public function LlamadasCunetasTelefonoValidos($fechainicio, $fechafin, $municipio, $ubicacion)
  {

    if (strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

      $cuentasTelefonosValidos = getLlamadasCunetasTelefonoValidos2($this->conDifusion, $fechainicio, $fechafin);
      if (gettype($cuentasTelefonosValidos['cuentas_telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidasTotales = 0;
        $cunetasValidasTotales['num_total_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidasTotales = (float)number_format((($cuentasTelefonosValidos['cuentas_telefonos_validas']->n * 100) / $cuentasTelefonosValidos['cuentas_telefonos_totales']->n), 3, '.', ',');
        $cunetasValidasTotales['num_total_validos'] = $cuentasTelefonosValidos['cuentas_telefonos_validas']->n;
      }
      $cunetasValidasTotales['por_total_validos'] =  $porcentajeLlamadasCuentasValidasTotales;
    } else if (!strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {
      $cuentasTelefonosValidos = getLlamadasCunetasTelefonoValidosMunicipios2($this->conDifusion, $fechainicio, $fechafin, $municipio);
      if (gettype($cuentasTelefonosValidos['cuentas_telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidasTotales = 0;
        $cunetasValidasTotales['num_total_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidasTotales = (float)number_format((($cuentasTelefonosValidos['cuentas_telefonos_validas']->n * 100) / $cuentasTelefonosValidos['cuentas_telefonos_totales']->n), 3, '.', ',');
        $cunetasValidasTotales['num_total_validos'] = $cuentasTelefonosValidos['cuentas_telefonos_validas']->n;
      }
      $cunetasValidasTotales['por_total_validos'] =  $porcentajeLlamadasCuentasValidasTotales;
    } else if (strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {
      $cuentasTelefonosValidos = getLlamadasCunetasTelefonoValidosUbicacion2($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
      if (gettype($cuentasTelefonosValidos['cuentas_telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidasTotales = 0;
        $cunetasValidasTotales['num_total_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidasTotales = (float)number_format((($cuentasTelefonosValidos['cuentas_telefonos_validas']->n * 100) / $cuentasTelefonosValidos['cuentas_telefonos_totales']->n), 3, '.', ',');
        $cunetasValidasTotales['num_total_validos'] = $cuentasTelefonosValidos['cuentas_telefonos_validas']->n;
      }
      $cunetasValidasTotales['por_total_validos'] =  $porcentajeLlamadasCuentasValidasTotales;
    } else if (!strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {
      $cuentasTelefonosValidos = getLlamadasCunetasTelefonoValidosUbicacionMunicipio2($this->conDifusion, $fechainicio, $fechafin, $municipio, $ubicacion);
      if (gettype($cuentasTelefonosValidos['cuentas_telefonos_totales']) != 'object') {
        $porcentajeLlamadasCuentasValidasTotales = 0;
        $cunetasValidasTotales['num_total_validos'] = 0;
      } else {
        $porcentajeLlamadasCuentasValidasTotales = (float)number_format((($cuentasTelefonosValidos['cuentas_telefonos_validas']->n * 100) / $cuentasTelefonosValidos['cuentas_telefonos_totales']->n), 3, '.', ',');
        $cunetasValidasTotales['num_total_validos'] = $cuentasTelefonosValidos['cuentas_telefonos_validas']->n;
      }
      $cunetasValidasTotales['por_total_validos'] =  $porcentajeLlamadasCuentasValidasTotales;
    }
    return $cunetasValidasTotales;
  }

  public function nuevasCuentas($fechainicio, $fechafin, $municipio, $ubicacion)
  {
    $cuentas = [];
    $cunetasValidasTotales = [];
    $totalLLamadas = getTotallamadas($this->conDifusion, $fechainicio, $fechafin);


    if (!isset($totalLLamadas['cuentas_totales']->n)) {
      $cunetasValidasTotales['num_total_nuevas'] = 0;
      $cunetasValidasTotales['por_total_neuvas'] =  0;
    } else {
      if (strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cunetasNuevas($this->conDifusion, $fechainicio, $fechafin);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->C_Cuenta);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraido las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        $cunetasNuevas = array_diff($cuentas, $cunetasDuplicadasUnicas); //extraigo las cunetas nuevas

        $porcentajeCuentasNuevas = (float)number_format(((count($cunetasNuevas) * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_nuevas'] = count($cunetasNuevas);
        $cunetasValidasTotales['por_total_neuvas'] =  $porcentajeCuentasNuevas;
      } else if (!strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasMunicipio($this->conDifusion, $fechainicio, $fechafin, $municipio);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->C_Cuenta);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraido las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        $cunetasNuevas = array_diff($cuentas, $cunetasDuplicadasUnicas); //extraigo las cunetas nuevas


        $porcentajeCuentasNuevas = (float)number_format(((count($cunetasNuevas) * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_nuevas'] = count($cunetasNuevas);
        $cunetasValidasTotales['por_total_neuvas'] =  $porcentajeCuentasNuevas;
      } else if (strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasUbicacion2($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->NIU);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraido las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        $cunetasNuevas = array_diff($cuentas, $cunetasDuplicadasUnicas); //extraigo las cunetas nuevas


        $porcentajeCuentasNuevas = (float)number_format(((count($cunetasNuevas) * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_nuevas'] = count($cunetasNuevas);
        $cunetasValidasTotales['por_total_neuvas'] =  $porcentajeCuentasNuevas;
      } else if (!strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasUbicacionMunicipio2($this->conDifusion, $fechainicio, $fechafin, $municipio, $ubicacion);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->NIU);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraido las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        $cunetasNuevas = array_diff($cuentas, $cunetasDuplicadasUnicas); //extraigo las cunetas nuevas


        $porcentajeCuentasNuevas = (float)number_format(((count($cunetasNuevas) * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_nuevas'] = count($cunetasNuevas);
        $cunetasValidasTotales['por_total_neuvas'] =  $porcentajeCuentasNuevas;
      }
    }
    return $cunetasValidasTotales;
  }


  //cuentas que han sido modificadas el su campo telefono
  public function modificaciones($fechainicio, $fechafin, $municipio, $ubicacion)
  {
    $cuentas = [];
    $cunetasValidasTotales = [];
    $modificaciones = [];
    $totalLLamadas = getTotallamadas($this->conDifusion, $fechainicio, $fechafin);
    if (!isset($totalLLamadas['cuentas_totales']->n)) {
      $cunetasValidasTotales['num_total_modificaciones'] = 0;
      $cunetasValidasTotales['por_total_modificaciones'] =  0;
    } else {
      if (strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cunetasNuevas($this->conDifusion, $fechainicio, $fechafin);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->C_Cuenta);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = Getmodificaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_modificaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_modificaciones'] =  $porcentajeCuentasNuevas;
      } else if (!strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasMunicipio($this->conDifusion, $fechainicio, $fechafin, $municipio);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->C_Cuenta);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = Getmodificaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_modificaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_modificaciones'] =  $porcentajeCuentasNuevas;
      } else if (strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasUbicacion2($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
        //$llamadas = cuentasNuevasUbicacion($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->NIU);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = Getmodificaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_modificaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_modificaciones'] =  $porcentajeCuentasNuevas;
      } else if (!strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasUbicacionMunicipio($this->conDifusion, $fechainicio, $fechafin, $municipio, $ubicacion);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor[0]->NIU);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = Getmodificaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_modificaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_modificaciones'] =  $porcentajeCuentasNuevas;
      }
    }

    return $cunetasValidasTotales;
  }


  //llamadas que registran la misma cuenta y el mismo telefono
  public function confirmaciones($fechainicio, $fechafin, $municipio, $ubicacion)
  {
    $cuentas = [];
    $cunetasValidasTotales = [];
    $modificaciones = [];
    $totalLLamadas = getTotallamadas($this->conDifusion, $fechainicio, $fechafin);


    if (!isset($totalLLamadas['cuentas_totales']->n)) {
      $cunetasValidasTotales['num_total_confirmaciones'] = 0;
      $cunetasValidasTotales['por_total_confirmaciones'] =  0;
    } else {
      if (strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cunetasNuevas($this->conDifusion, $fechainicio, $fechafin);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->C_Cuenta);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas 224756794
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = GetConfirmaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_confirmaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_confirmaciones'] =  $porcentajeCuentasNuevas;
      } else if (!strcmp($municipio, 'todos') == 0 && strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasMunicipio($this->conDifusion, $fechainicio, $fechafin, $municipio);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->C_Cuenta);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = GetConfirmaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_confirmaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_confirmaciones'] =  $porcentajeCuentasNuevas;
      } else if (strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasUbicacion2($this->conDifusion, $fechainicio, $fechafin, $ubicacion);
        //$llamadas = cuentasNuevasUbicacion($this->conDifusion, $fechainicio, $fechafin, $ubicacion);

        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor->NIU);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = GetConfirmaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_confirmaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_confirmaciones'] =  $porcentajeCuentasNuevas;
      } else if (!strcmp($municipio, 'todos') == 0 && !strcmp($ubicacion, 'todos') == 0) {

        $llamadas = cuentasNuevasUbicacionMunicipio($this->conDifusion, $fechainicio, $fechafin, $municipio, $ubicacion);
        foreach ($llamadas as $clave => $valor) {
          array_push($cuentas, $valor[0]->NIU);
        }
        $cunetaUnicas = array_unique($cuentas); //elimno las cuentas duplicadas
        $cunetasDuplicadas = array_diff_assoc($cuentas, $cunetaUnicas); //extraigo las cuentas dupliadas en un array
        $cunetasDuplicadasUnicas = array_unique($cunetasDuplicadas);    // Eliminamos los elementos repetidos de las cuentas duplicadas
        foreach ($cunetasDuplicadasUnicas as $clave => $valor) {
          $numModificaciones = GetConfirmaciones($this->conDifusion, $valor);
          array_push($modificaciones, $numModificaciones);
        }

        $modificaciones = array_sum($modificaciones);
        $porcentajeCuentasNuevas = (float)number_format((($modificaciones * 100) / $totalLLamadas['cuentas_totales']->n), 1, '.', ',');
        $cunetasValidasTotales['num_total_confirmaciones'] = $modificaciones;
        $cunetasValidasTotales['por_total_confirmaciones'] =  $porcentajeCuentasNuevas;
      }
    }

    return $cunetasValidasTotales;
  }

  //kpi nivel de eficacia
  public function porcentajeEficacia2($fechainicio, $fechafin)
  {

    $porcentajes = getPorcentajeEficacia($this->conDifusion, $fechainicio, $fechafin);
    $sumaEficaciaSucio = [];
    $sumaAbandonoSucio = [];
    $sumaNivelServicioSucio = [];
    $sumaOcupacionSucio = [];
    $sumaAbandonadasSucio = [];

    foreach ($porcentajes as $clave => $valor) {
      array_push($sumaEficaciaSucio, $valor->porcentajeEficacia);
      array_push($sumaAbandonoSucio, $valor->porcentajeAbandono);
      array_push($sumaNivelServicioSucio, $valor->porcentajeNivelServicio);
      array_push($sumaOcupacionSucio, $valor->porcentajeOcupacion);
      array_push($sumaAbandonadasSucio, $valor->llamadasAbandonadas);
    }

    $sumaEficacia = [];
    foreach ($sumaEficaciaSucio as $clave => $valor) {
      if (is_nan($valor)) {
        $valor = 0;
        array_push($sumaEficacia, $valor);
      } else {
        array_push($sumaEficacia, $valor);
      }
    }

    $sumaAbandono = [];
    foreach ($sumaAbandonoSucio as $clave => $valor) {
      if (is_nan($valor)) {
        $valor = 0;
        array_push($sumaAbandono, $valor);
      } else {
        array_push($sumaAbandono, $valor);
      }
    }
    $sumaNivelServicio = [];
    foreach ($sumaNivelServicioSucio as $clave => $valor) {
      if (is_nan($valor)) {
        $valor = 0;
        array_push($sumaNivelServicio, $valor);
      } else {
        array_push($sumaNivelServicio, $valor);
      }
    }
    $sumaOcupacion = [];
    foreach ($sumaOcupacionSucio as $clave => $valor) {
      if (is_nan($valor)) {
        $valor = 0;
        array_push($sumaOcupacion, $valor);
      } else {
        array_push($sumaOcupacion, $valor);
      }
    }
    $sumaAbandonadas = [];
    foreach ($sumaAbandonadasSucio as $clave => $valor) {
      if (is_nan($valor)) {
        $valor = 0;
        array_push($sumaAbandonadas, $valor);
      } else {
        array_push($sumaAbandonadas, $valor);
      }
    }

    $porcentajesFinales['porcentaje_eficacia'] = array_sum($sumaEficacia) / count($sumaEficacia);
    $porcentajesFinales['porcentaje_abandono'] = array_sum($sumaAbandono) / count($sumaAbandono);
    $porcentajesFinales['porcentaje_nivelServicio'] = array_sum($sumaNivelServicio) / count($sumaNivelServicio);
    $porcentajesFinales['porcentaje_ocupacion'] = array_sum($sumaOcupacion) / count($sumaOcupacion);
    $porcentajesFinales['llamadas_abandonadas'] = array_sum($sumaAbandonadas) / count($sumaAbandonadas);

    return $porcentajesFinales;
  }

  //kpi nivel de eficacia
  public function porcentajesKpi($fechainicio, $fechafin)
  {

    $porcentajes = getPorcentajeEficacia($this->conDifusion, $fechainicio, $fechafin);
    $llamadasEntrantes = [];
    $llamadasContestadas = [];
    $numLlamadasAbandonadas = [];
    $porcentajeNivelServicio = [];
    $servicioXentrante = [];
    $porcentajeEficacia = [];

    if (count($porcentajes) > 0) {

      //separar los valores de cada kpi necesario
      foreach ($porcentajes as $clave => $valor) {
        array_push($llamadasEntrantes, $valor->llamadasEntrantes);
        array_push($llamadasContestadas, $valor->llamadasContestadas);
        array_push($numLlamadasAbandonadas, $valor->llamadasAbandonadas);
        array_push($porcentajeNivelServicio, $valor->porcentajeNivelServicio);
      }

      //en ocaciones algunos campos de porcetaje de nivel de servicio, llegan en NAN, lo que se cambia por 0 en este for
      $NivelServicio = [];
      foreach ($porcentajeNivelServicio as $clave => $valor) {
        if (is_nan($valor)) {
          $valor = 0;
          array_push($NivelServicio, $valor);
        } else {
          array_push($NivelServicio, $valor);
        }
      }

      //multiplicando cada valor de llamdas entrantes con cada valor de porcenaje de nivel de servicio
      foreach ($NivelServicio as $key => $value) {
        $servicioXentrante[$key] = $value  * $llamadasEntrantes[$key];
      }

      //sua de todos los array obtenidos
      $sumaEntrantes = array_sum($llamadasEntrantes);
      $sumaContestadas = array_sum($llamadasContestadas);
      $numAbandonadas = array_sum($numLlamadasAbandonadas);
      $sumaEntrantesXnivelServicio = array_sum($servicioXentrante);


      $porcentajeEficacia['llamadas_entrantes'] = $llamadasEntrantes;
      $porcentajeEficacia['llamadas_contestadas'] = $llamadasContestadas;
      $porcentajeEficacia['por_eficacia'] =  (float)number_format((($sumaContestadas / $sumaEntrantes) * 100), 2, '.', ',');
      $porcentajeEficacia['por_abandono'] =  (float)number_format((100 - (($sumaContestadas / $sumaEntrantes) * 100)), 2, '.', ',');
      $porcentajeEficacia['num_abandono'] = $numAbandonadas;
      $porcentajeEficacia['por_nivel_servicio'] = (float)number_format((($sumaEntrantesXnivelServicio / $sumaEntrantes) * 100), 2, '.', ',');
    } else {

      $porcentajeEficacia['llamadas_entrantes'] = 0;
      $porcentajeEficacia['llamadas_contestadas'] = 0;
      $porcentajeEficacia['por_eficacia'] =  0;
      $porcentajeEficacia['por_abandono'] =  0;
      $porcentajeEficacia['num_abandono'] = 0;
      $porcentajeEficacia['por_nivel_servicio'] = 0;
    }



    return $porcentajeEficacia;
  }

  //obtener llamadas entrantes y llamadas contestadas por dia del mes para gestion diaria
  public function getDataGraficaDia($fechainicio, $fechafin)
  {
    $data = obtenerConsultasLlamadasEntrantesContestadasDia($this->conDifusion, $fechainicio, $fechafin);
    $numLlamadas[1]['e'] = 0;
    $numLlamadas[1]['c'] = 0;
    $numLlamadas[2]['e'] = 0;
    $numLlamadas[2]['c'] = 0;
    $numLlamadas[3]['e'] = 0;
    $numLlamadas[3]['c'] = 0;
    $numLlamadas[4]['e'] = 0;
    $numLlamadas[4]['c'] = 0;
    $numLlamadas[5]['e'] = 0;
    $numLlamadas[5]['c'] = 0;
    $numLlamadas[6]['e'] = 0;
    $numLlamadas[6]['c'] = 0;
    $numLlamadas[7]['e'] = 0;
    $numLlamadas[7]['c'] = 0;
    $numLlamadas[8]['e'] = 0;
    $numLlamadas[8]['c'] = 0;
    $numLlamadas[9]['e'] = 0;
    $numLlamadas[9]['c'] = 0;
    $numLlamadas[10]['e'] = 0;
    $numLlamadas[10]['c'] = 0;
    $numLlamadas[11]['e'] = 0;
    $numLlamadas[11]['c'] = 0;
    $numLlamadas[12]['e'] = 0;
    $numLlamadas[12]['c'] = 0;
    $numLlamadas[13]['e'] = 0;
    $numLlamadas[13]['c'] = 0;
    $numLlamadas[14]['e'] = 0;
    $numLlamadas[14]['c'] = 0;
    $numLlamadas[15]['e'] = 0;
    $numLlamadas[15]['c'] = 0;
    $numLlamadas[16]['e'] = 0;
    $numLlamadas[16]['c'] = 0;
    $numLlamadas[17]['e'] = 0;
    $numLlamadas[17]['c'] = 0;
    $numLlamadas[18]['e'] = 0;
    $numLlamadas[18]['c'] = 0;
    $numLlamadas[19]['e'] = 0;
    $numLlamadas[19]['c'] = 0;
    $numLlamadas[20]['e'] = 0;
    $numLlamadas[20]['c'] = 0;
    $numLlamadas[21]['e'] = 0;
    $numLlamadas[21]['c'] = 0;
    $numLlamadas[22]['e'] = 0;
    $numLlamadas[22]['c'] = 0;
    $numLlamadas[23]['e'] = 0;
    $numLlamadas[23]['c'] = 0;
    $numLlamadas[24]['e'] = 0;
    $numLlamadas[24]['c'] = 0;
    $numLlamadas[25]['e'] = 0;
    $numLlamadas[25]['c'] = 0;
    $numLlamadas[26]['e'] = 0;
    $numLlamadas[26]['c'] = 0;
    $numLlamadas[27]['e'] = 0;
    $numLlamadas[27]['c'] = 0;
    $numLlamadas[28]['e'] = 0;
    $numLlamadas[28]['c'] = 0;
    $numLlamadas[29]['e'] = 0;
    $numLlamadas[29]['c'] = 0;
    $numLlamadas[30]['e'] = 0;
    $numLlamadas[30]['c'] = 0;
    $numLlamadas[31]['e'] = 0;
    $numLlamadas[31]['c'] = 0;




    foreach ($data as $clave => $valor) {
      $separarFeha = explode('-', $valor->fecha);
      $dia = $separarFeha[count($separarFeha) - 1];
      switch ($dia) {

        case '01':
          $numLlamadas[1]['c'] += $valor->llamadasContestadas;
          $numLlamadas[1]['e'] += $valor->llamadasEntrantes;
          break;
        case '02':
          $numLlamadas[2]['c'] += $valor->llamadasContestadas;
          $numLlamadas[2]['e'] += $valor->llamadasEntrantes;
          break;
        case '03':
          $numLlamadas[3]['c'] += $valor->llamadasContestadas;
          $numLlamadas[3]['e'] += $valor->llamadasEntrantes;
          break;
        case '04':
          $numLlamadas[4]['c'] += $valor->llamadasContestadas;
          $numLlamadas[4]['e'] += $valor->llamadasEntrantes;
          break;
        case '05':
          $numLlamadas[5]['c'] += $valor->llamadasContestadas;
          $numLlamadas[5]['e'] += $valor->llamadasEntrantes;
          break;
        case '06':
          $numLlamadas[6]['c'] += $valor->llamadasContestadas;
          $numLlamadas[6]['e'] += $valor->llamadasEntrantes;
          break;
        case '07':
          $numLlamadas[7]['c'] += $valor->llamadasContestadas;
          $numLlamadas[7]['e'] += $valor->llamadasEntrantes;
          break;
        case '08':
          $numLlamadas[8]['c'] += $valor->llamadasContestadas;
          $numLlamadas[8]['e'] += $valor->llamadasEntrantes;
          break;
        case '09':
          $numLlamadas[9]['c'] += $valor->llamadasContestadas;
          $numLlamadas[9]['e'] += $valor->llamadasEntrantes;
          break;
        case '10':
          $numLlamadas[10]['c'] += $valor->llamadasContestadas;
          $numLlamadas[10]['e'] += $valor->llamadasEntrantes;
          break;
        case '11':
          $numLlamadas[11]['c'] += $valor->llamadasContestadas;
          $numLlamadas[11]['e'] += $valor->llamadasEntrantes;
          break;
        case '12':
          $numLlamadas[12]['c'] += $valor->llamadasContestadas;
          $numLlamadas[12]['e'] += $valor->llamadasEntrantes;
          break;
        case '13':
          $numLlamadas[13]['c'] += $valor->llamadasContestadas;
          $numLlamadas[13]['e'] += $valor->llamadasEntrantes;
          break;
        case '14':
          $numLlamadas[14]['c'] += $valor->llamadasContestadas;
          $numLlamadas[14]['e'] += $valor->llamadasEntrantes;
          break;
        case '15':
          $numLlamadas[15]['c'] += $valor->llamadasContestadas;
          $numLlamadas[15]['e'] += $valor->llamadasEntrantes;
          break;
        case '16':
          $numLlamadas[16]['c'] += $valor->llamadasContestadas;
          $numLlamadas[16]['e'] += $valor->llamadasEntrantes;
          break;
        case '17':
          $numLlamadas[17]['c'] += $valor->llamadasContestadas;
          $numLlamadas[17]['e'] += $valor->llamadasEntrantes;
          break;
        case '18':
          $numLlamadas[18]['c'] += $valor->llamadasContestadas;
          $numLlamadas[18]['e'] += $valor->llamadasEntrantes;
          break;
        case '19':
          $numLlamadas[19]['c'] += $valor->llamadasContestadas;
          $numLlamadas[19]['e'] += $valor->llamadasEntrantes;
          break;
        case '20':
          $numLlamadas[20]['c'] += $valor->llamadasContestadas;
          $numLlamadas[20]['e'] += $valor->llamadasEntrantes;
          break;
        case '21':
          $numLlamadas[21]['c'] += $valor->llamadasContestadas;
          $numLlamadas[21]['e'] += $valor->llamadasEntrantes;
          break;
        case '22':
          $numLlamadas[22]['c'] += $valor->llamadasContestadas;
          $numLlamadas[22]['e'] += $valor->llamadasEntrantes;
          break;
        case '23':
          $numLlamadas[23]['c'] += $valor->llamadasContestadas;
          $numLlamadas[23]['e'] += $valor->llamadasEntrantes;
          break;
        case '24':
          $numLlamadas[24]['c'] += $valor->llamadasContestadas;
          $numLlamadas[24]['e'] += $valor->llamadasEntrantes;
          break;
        case '25':
          $numLlamadas[25]['c'] += $valor->llamadasContestadas;
          $numLlamadas[25]['e'] += $valor->llamadasEntrantes;
          break;
        case '26':
          $numLlamadas[26]['c'] += $valor->llamadasContestadas;
          $numLlamadas[26]['e'] += $valor->llamadasEntrantes;
          break;
        case '27':
          $numLlamadas[27]['c'] += $valor->llamadasContestadas;
          $numLlamadas[27]['e'] += $valor->llamadasEntrantes;
          break;
        case '28':
          $numLlamadas[28]['c'] += $valor->llamadasContestadas;
          $numLlamadas[28]['e'] += $valor->llamadasEntrantes;
          break;
        case '29':
          $numLlamadas[29]['c'] += $valor->llamadasContestadas;
          $numLlamadas[29]['e'] += $valor->llamadasEntrantes;
          break;
        case '30':
          $numLlamadas[30]['c'] += $valor->llamadasContestadas;
          $numLlamadas[30]['e'] += $valor->llamadasEntrantes;
          break;
        case '31':
          $numLlamadas[31]['c'] += $valor->llamadasContestadas;
          $numLlamadas[31]['e'] += $valor->llamadasEntrantes;
          break;
      }
    }


    if ($fechafin == date('n')) {
      $fecha = date('j');
    } else {
      $fecha = cal_days_in_month(CAL_GREGORIAN, $fechafin, $fechainicio);
    }

    foreach ($numLlamadas as $clave => $valor) {
      if ($clave > intval($fecha)) {
        unset($numLlamadas[$clave]);
      }
    }

    return $numLlamadas;
  }

  //obtener llamadas entrantes y llamadas contestadas por mes para gestion diaria
  public function getDataGraficaMes($anio)
  {
    $data = obtenerConsultasLlamadasEntrantesContestadasMes($this->conDifusion, $anio);
    $numLlamadas[1]['e'] = 0;
    $numLlamadas[1]['c'] = 0;
    $numLlamadas[2]['e'] = 0;
    $numLlamadas[2]['c'] = 0;
    $numLlamadas[3]['e'] = 0;
    $numLlamadas[3]['c'] = 0;
    $numLlamadas[4]['e'] = 0;
    $numLlamadas[4]['c'] = 0;
    $numLlamadas[5]['e'] = 0;
    $numLlamadas[5]['c'] = 0;
    $numLlamadas[6]['e'] = 0;
    $numLlamadas[6]['c'] = 0;
    $numLlamadas[7]['e'] = 0;
    $numLlamadas[7]['c'] = 0;
    $numLlamadas[8]['e'] = 0;
    $numLlamadas[8]['c'] = 0;
    $numLlamadas[9]['e'] = 0;
    $numLlamadas[9]['c'] = 0;
    $numLlamadas[10]['e'] = 0;
    $numLlamadas[10]['c'] = 0;
    $numLlamadas[11]['e'] = 0;
    $numLlamadas[11]['c'] = 0;
    $numLlamadas[12]['e'] = 0;
    $numLlamadas[12]['c'] = 0;


    foreach ($data as $clave => $valor) {
      $separarFeha = explode('-', $valor->fecha);
      $mes = $separarFeha[1];
      switch ($mes) {

        case '01':
          $numLlamadas[1]['c'] += $valor->llamadasContestadas;
          $numLlamadas[1]['e'] += $valor->llamadasEntrantes;
          break;
        case '02':
          $numLlamadas[2]['c'] += $valor->llamadasContestadas;
          $numLlamadas[2]['e'] += $valor->llamadasEntrantes;
          break;
        case '03':
          $numLlamadas[3]['c'] += $valor->llamadasContestadas;
          $numLlamadas[3]['e'] += $valor->llamadasEntrantes;
          break;
        case '04':
          $numLlamadas[4]['c'] += $valor->llamadasContestadas;
          $numLlamadas[4]['e'] += $valor->llamadasEntrantes;
          break;
        case '05':
          $numLlamadas[5]['c'] += $valor->llamadasContestadas;
          $numLlamadas[5]['e'] += $valor->llamadasEntrantes;
          break;
        case '06':
          $numLlamadas[6]['c'] += $valor->llamadasContestadas;
          $numLlamadas[6]['e'] += $valor->llamadasEntrantes;
          break;
        case '07':
          $numLlamadas[7]['c'] += $valor->llamadasContestadas;
          $numLlamadas[7]['e'] += $valor->llamadasEntrantes;
          break;
        case '08':
          $numLlamadas[8]['c'] += $valor->llamadasContestadas;
          $numLlamadas[8]['e'] += $valor->llamadasEntrantes;
          break;
        case '09':
          $numLlamadas[9]['c'] += $valor->llamadasContestadas;
          $numLlamadas[9]['e'] += $valor->llamadasEntrantes;
          break;
        case '10':
          $numLlamadas[10]['c'] += $valor->llamadasContestadas;
          $numLlamadas[10]['e'] += $valor->llamadasEntrantes;
          break;
        case '11':
          $numLlamadas[11]['c'] += $valor->llamadasContestadas;
          $numLlamadas[11]['e'] += $valor->llamadasEntrantes;
          break;
        case '12':
          $numLlamadas[12]['c'] += $valor->llamadasContestadas;
          $numLlamadas[12]['e'] += $valor->llamadasEntrantes;
          break;
      }
    }

    //$fecha = date('n'); //apartir del año que se recibe, calcular cuantos meses han transcurrido
    $fecha = 12; //apartir del año que se recibe, calcular cuantos meses han transcurrido
    foreach ($numLlamadas as $clave => $valor) {
      if ($clave > intval($fecha)) {
        unset($numLlamadas[$clave]);
      }
    }

    return $numLlamadas;
  }


  //obtener llamadas entrantes y llamadas contestadas por semana para gestion diaria
  public function getDataGraficaSemana($fechainicio, $fechafin)
  {
    $data = obtenerConsultasLlamadasEntrantesContestadasSemana($this->conDifusion, $fechainicio, $fechafin);

    $cont = 0;
    $numLlamadas[1]['e'] = 0;
    $numLlamadas[1]['c'] = 0;
    $numLlamadas[2]['e'] = 0;
    $numLlamadas[2]['c'] = 0;
    $numLlamadas[3]['e'] = 0;
    $numLlamadas[3]['c'] = 0;
    $numLlamadas[4]['e'] = 0;
    $numLlamadas[4]['c'] = 0;
    $numLlamadas[5]['e'] = 0;
    $numLlamadas[5]['c'] = 0;
    $numLlamadas[6]['e'] = 0;
    $numLlamadas[6]['c'] = 0;
    $numLlamadas[7]['e'] = 0;
    $numLlamadas[7]['c'] = 0;



    foreach ($data as $clave => $valor) {
      $cont = $cont + 1;
      $diaSemana = $this->saber_dia($valor->fecha);
      switch ($diaSemana) {

        case 'Lunes':
          $numLlamadas[1]['c'] += $valor->llamadasContestadas;
          $numLlamadas[1]['e'] += $valor->llamadasEntrantes;
          break;
        case 'Martes':
          $numLlamadas[2]['c'] += $valor->llamadasContestadas;
          $numLlamadas[2]['e'] += $valor->llamadasEntrantes;
          break;
        case 'Miercoles':
          $numLlamadas[3]['c'] += $valor->llamadasContestadas;
          $numLlamadas[3]['e'] += $valor->llamadasEntrantes;
          break;
        case 'Jueves':
          $numLlamadas[4]['c'] += $valor->llamadasContestadas;
          $numLlamadas[4]['e'] += $valor->llamadasEntrantes;
          break;
        case 'Viernes':
          $numLlamadas[5]['c'] += $valor->llamadasContestadas;
          $numLlamadas[5]['e'] += $valor->llamadasEntrantes;
          break;
        case 'Sabado':
          $numLlamadas[6]['c'] += $valor->llamadasContestadas;
          $numLlamadas[6]['e'] += $valor->llamadasEntrantes;
          break;
        case 'Domingo':
          $numLlamadas[7]['c'] += $valor->llamadasContestadas;
          $numLlamadas[7]['e'] += $valor->llamadasEntrantes;
          break;
      }
    }


    return $numLlamadas;
  }

  function saber_dia($nombredia)
  {
    $dias = array('', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo');
    $fecha = $dias[date('N', strtotime($nombredia))];
    return $fecha;
  }
}
