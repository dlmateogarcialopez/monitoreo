<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();

if (isset($input['startDate']) && isset($input['endDate'])) {

  $fechainicio = $input['startDate'];
  $fechafin =  $input['endDate'];

  //grafica invocar chatbot
  $json['monitoreo_lucy']['invocar'] = $api->getResultadoInvocar($fechainicio, $fechafin);

  //grafica invocar chatbot mes
  $json['monitoreo_lucy']['invocarMesesTelegram'] = $api->getResultadoInvocarMesTelegram($fechainicio, $fechafin);
  $json['monitoreo_lucy']['invocarMesesChatWeb'] = $api->getResultadoInvocarMesChatWeb($fechainicio, $fechafin);
  $json['monitoreo_lucy']['invocarMesesTotales'] = $api->getResultadoInvocarMesTotal();

  //cantidad de comentarios
  $json['monitoreo_lucy']['comentarios'] = $api->getComentario($fechainicio, $fechafin);

  //CALIFICACIONES
  $json['monitoreo_lucy']['calificacion'] = $api->getCalificaciones($fechainicio, $fechafin);

  //grafica acceso menu
  $json['monitoreo_lucy']['menus'] = $api->getResultadoMenus($fechainicio, $fechafin);

  //acceso a menu por meses
  $json['monitoreo_lucy']['menus_mes'] = $api->getAccesosMenuMes($fechainicio, $fechafin);

  //número de Fallbacks
  $json['monitoreo_lucy']['fallbacks'] = $api->getFallbacks($fechainicio, $fechafin);

  //número de consultas a submenú
  $json['monitoreo_lucy']['submenu'] = $api->getAccesoSubmenu($fechainicio, $fechafin);

  //top de usuarios con mas consultas en falta de energia y copia de factura
  $json['monitoreo_lucy']['topConsultas'] = $api->getTopConsultas($fechainicio, $fechafin);


  /*//reportes hechos
  $json['monitoreo_lucy']['reportes'] = $api->getResultado($fechainicio, $fechafin); //reportes - sankey
  
  //Municipios
  $json['monitoreo_lucy']['segmentos'] = $api->getConsultasSegmentosUbicacionMunicipio($fechainicio, $fechafin);*/
} else if (isset($input['startDate2']) && isset($input['endDate2'])) {

  $fechainicio = $input['startDate2'];
  $fechafin =  $input['endDate2'];

  //Conversaciones fallback
  $json['monitoreo_lucy']['info_preliminar_fallbacks'] = $api->obtenerPreliminarFallback($fechainicio, $fechafin);
} else {
  $json['error'] = false;
  $json['message'] = 'Parametros incorrectos';
}

echo json_encode($json);
