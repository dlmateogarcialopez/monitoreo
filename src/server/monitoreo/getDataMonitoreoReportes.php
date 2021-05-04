<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();

if (isset($input['anio']) && isset($input['mes']) && isset($input['tipo'])) {

  $anio = $input['anio'];
  $mes = $input['mes'];
  $tipo = $input['tipo'];

  if ($tipo == 'informeGeneralLucy') {
    // $json['reportes'][$tipo] = $api->getcalnegativas($anio, $mes);
  } else if ('calificacionesLucy' === $tipo) {
    $json['reportes'][$tipo] = $api->getcalnegativas($anio, $mes);
  } else if ($tipo == 'usuariosFrecuentesLucy') {
    $json['reportes'][$tipo] = $api->getConsultasUsuarios($anio, $mes);
  } else if ($tipo == 'usuariosSegmentoLucy') {
    $json['reportes'][$tipo] = $api->getUsuariosSegmentos($anio, $mes);
  } else if ($tipo == 'informeGeneralDinp') {
    //$json['reportes'][$tipo] = $api->getcalnegativas($anio, $mes);
  } else if ($tipo == 'usuariosInscritosDinp') {
    $json['reportes'][$tipo] = $api->getUsuariosInscritos($anio, $mes);
  } else if ($tipo == 'usuariosRecibidosMsmDinp') {
    $json['reportes'][$tipo] = $api->getAcuseReciboDifusion($anio, $mes);
  } else if ($tipo == 'usuariosRecibidosMsmDinpSegmento') {
    $json['reportes'][$tipo] = $api->getAcuseReciboDifusionSegmento($mes, $anio); //------------------optimixar consultas
  } else if ($tipo == 'usuariosRecibidosMsmDinpPromocion') {
    $json['reportes'][$tipo] = $api->getAcuseReciboPromocion($anio, $mes);
  } else if ($tipo == 'informeGeneralDod') {
    //$json['reportes'][$tipo] = $api->getcalnegativas($anio, $mes);   
  } else if ($tipo === 'usuariosTtotalesLucy') {
    $json['reportes'][$tipo] = $api->getUsuariosTotales($anio, $mes);
  }
} else if (isset($input['tipo'])) {
  $tipo = $input['tipo'];

  if ($tipo == 'usuariosTotalesLucy') {

    //$json['reportes'][$tipo] = $api->getUsuariosTotales();
  }
} else {
  $json['error'] = false;
  $json['message'] = 'Alguno de los parametros estan incorrectos';
}

echo json_encode($json);
