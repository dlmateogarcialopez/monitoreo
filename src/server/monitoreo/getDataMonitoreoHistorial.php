<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();


if (isset($input['criterio']) && isset($input['valor'])) {

  $criterio = $input['criterio'];
  $valor = $input['valor'];

  //buscar usuarios por criterio de busqueda
  $json['segumiento_individual']['usuarios'] = $api->obtenerUsuarios($criterio, $valor);
} else if (isset($input['niu']) && isset($input['startDate']) && isset($input['endDate'])) {

  $niu = $input['niu'];
  $posy = strpos($input['startDate'], 'T');
  $posz = strpos($input['startDate'], 'Z');
  if ($posy || $posz) {
    $fechai = explode("T", $input['startDate']);
    $fechaInicio = $fechai[0] . ' ' . '00:00';

    $fechaf =  explode("T", $input['endDate']);
    $fechaFin = $fechaf[0] . ' ' . '23:59';
  } else {

    $fechaInicio = $input['startDate'] . ' ' . '00:00';;
    $fechaFin = $input['endDate'] . ' ' . '23:59';;
  }

  //del usuario seleccionado, consultamos el numero de inteacciones, porcentaje, ultima fcha de interaccion  
  $json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminarLlamadas($niu, $fechaInicio, $fechaFin);
  //$json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminar($niu, $fechaInicio, $fechaFin);

  //del usuario seleccionado, consultamos todo su historial
  //$json['segumiento_individual']['historico_usuario'] = $api->obtenerHistorial($niu, $fechaInicio, $fechaFin);

} else if (isset($input['niu']) && isset($input['startDate2']) && isset($input['endDate2'])) {
  $niu = $input['niu'];
  $posy = strpos($input['startDate2'], 'T');
  $posz = strpos($input['startDate2'], 'Z');
  if ($posy || $posz) {
    $fechai = explode("T", $input['startDate2']);
    $fechaInicio = $fechai[0] . ' ' . '00:00';

    $fechaf =  explode("T", $input['endDate2']);
    $fechaFin = $fechaf[0] . ' ' . '23:59';
  } else {

    $fechaInicio = $input['startDate2'] . ' ' . '00:00';;
    $fechaFin = $input['endDate2'] . ' ' . '23:59';;
  }

  //del usuario seleccionado, consultamos el numero de inteacciones, porcentaje, ultima fcha de interaccion  
  $json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminarPromocionLucy($niu, $fechaInicio, $fechaFin);
} else if (isset($input['niu']) && isset($input['startDate3']) && isset($input['endDate3'])) {
  $niu = $input['niu'];
  $posy = strpos($input['startDate3'], 'T');
  $posz = strpos($input['startDate3'], 'Z');
  if ($posy || $posz) {
    $fechai = explode("T", $input['startDate3']);
    $fechaInicio = $fechai[0] . ' ' . '00:00';

    $fechaf =  explode("T", $input['endDate3']);
    $fechaFin = $fechaf[0] . ' ' . '23:59';
  } else {

    $fechaInicio = $input['startDate3'] . ' ' . '00:00';;
    $fechaFin = $input['endDate3'] . ' ' . '23:59';;
  }

  //del usuario seleccionado, consultamos el numero de inteacciones, porcentaje, ultima fcha de interaccion  
  $json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminarDinp($niu, $fechaInicio, $fechaFin);
} else if (isset($input['niu']) && isset($input['startDate4']) && isset($input['endDate4'])) {
  $niu = $input['niu'];
  $posy = strpos($input['startDate4'], 'T');
  $posz = strpos($input['startDate4'], 'Z');
  if ($posy || $posz) {
    $fechai = explode("T", $input['startDate4']);
    $fechaInicio = $fechai[0] . ' ' . '00:00';

    $fechaf =  explode("T", $input['endDate4']);
    $fechaFin = $fechaf[0] . ' ' . '23:59';
  } else {

    $fechaInicio = $input['startDate4'] . ' ' . '00:00';;
    $fechaFin = $input['endDate4'] . ' ' . '23:59';;
  }

  //del usuario seleccionado, consultamos el numero de inteacciones, porcentaje, ultima fcha de interaccion  
  $json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminarInvitacionSusp($niu, $fechaInicio, $fechaFin);
} else if (isset($input['niu']) && isset($input['startDate5']) && isset($input['endDate5'])) {
  $niu = $input['niu'];
  $posy = strpos($input['startDate5'], 'T');
  $posz = strpos($input['startDate5'], 'Z');
  if ($posy || $posz) {
    $fechai = explode("T", $input['startDate5']);
    $fechaInicio = $fechai[0] . ' ' . '00:00';

    $fechaf =  explode("T", $input['endDate5']);
    $fechaFin = $fechaf[0] . ' ' . '23:59';
  } else {

    $fechaInicio = $input['startDate5'] . ' ' . '00:00';;
    $fechaFin = $input['endDate5'] . ' ' . '23:59';;
  }

  //del usuario seleccionado, consultamos el numero de inteacciones, porcentaje, ultima fcha de interaccion  
  $json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminarChats($niu, $fechaInicio, $fechaFin);
} else if (isset($input['niu']) && isset($input['startDate6']) && isset($input['endDate6'])) {
  $niu = $input['niu'];
  $posy = strpos($input['startDate6'], 'T');
  $posz = strpos($input['startDate6'], 'Z');
  if ($posy || $posz) {
    $fechai = explode("T", $input['startDate6']);
    $fechaInicio = $fechai[0] . ' ' . '00:00';

    $fechaf =  explode("T", $input['endDate6']);
    $fechaFin = $fechaf[0] . ' ' . '23:59';
  } else {

    $fechaInicio = $input['startDate6'] . ' ' . '00:00';;
    $fechaFin = $input['endDate6'] . ' ' . '23:59';;
  }
  $json['segumiento_individual']['info_preliminar'] = $api->obtenerPreliminar($niu, $fechaInicio, $fechaFin);
} else {
  $json['error'] = false;
  $json['message'] = 'Se necesitan las dos fchas';
}

echo json_encode($json);
