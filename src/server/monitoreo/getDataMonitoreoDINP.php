<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();

if (isset($input['startDate']) && isset($input['endDate']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //obtener mensajes enviados de apertura y cierre, y obtener mensajes entregados de apertura y cierre
  $json['monitoreo_dinp']['kpis'] = $api->getDataAperturaCierre($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate2']) && isset($input['endDate2']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate2']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate2']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //difusion total(apertura + cierre) 
  $json['monitoreo_dinp']['difusion_total'] = $api->difusionAperturaCierreTotalMeses($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate3']) && isset($input['endDate3']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate3']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate3']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //publicidad lucy
  $json['monitoreo_dinp']['publicidad'] = $api->getAcuseReciboPromocionLucy($fechainicio, $fechafin);
} else if (isset($input['startDate4']) && isset($input['endDate4']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate4']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate4']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //Mensajes enviados por hora y día de la semana
  $json['monitoreo_dinp']['promedio_hora_dia'] = $api->getDifusionDifusionHoraDia($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate5']) && isset($input['endDate5']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate5']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate5']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];
  //Mensajes enviados por regla
  $json['monitoreo_dinp']['grafico_barras'] = $api->getDifusioPorReglaDINP($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate6']) && isset($input['endDate6']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate6']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate6']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //mapa - tabla- mensajes por segmentos- mensajes por ubicacion 
  //$json['monitoreo_dinp']['tabla'] = $api->tablaDifusionDINP($fechainicio, $fechafin, $reglas);
  $json['monitoreo_dinp']['tabla'] = $api->tablaDifusionDINP2($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate7']) && isset($input['endDate7']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate7']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate7']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //mensajes por segmentos
  $json['monitoreo_dinp']['difusion_segmentos'] = $api->getDifusiobSegmentos($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate8']) && isset($input['endDate8']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate8']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate8']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //mensajes por ubicacion 
  $json['monitoreo_dinp']['difusion_ubicacion'] = $api->getDifusiobUbicacion($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate9']) && isset($input['endDate9']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate9']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate9']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  $json['monitoreo_dinp']['msg_enviados'] = $api->getDataAperturaCierre2($fechainicio, $fechafin, $reglas);
} else if (isset($input['startDate10']) && isset($input['endDate10']) && isset($input['regla'])) {

  $fechai = explode("T", $input['startDate10']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate10']);
  $fechafin = $fechaf[0];

  $reglas = $input['regla'];

  //publicidad suspensiones
  $json['monitoreo_dinp']['publicidadSuspensiones'] = $api->getAcuseReciboPromocionSuspensiones($fechainicio, $fechafin);

}else if (isset($input['startDate11']) && isset($input['endDate11'])) {

  $fechai = explode("T", $input['startDate11']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate11']);
  $fechafin = $fechaf[0];

  //Cantidad de cancelaciones recibidas por día
  $json['monitoreo_dinp']['cancelaciones_recibidas_dia'] = $api->getCancelacionesRecibidasPorDia($fechainicio, $fechafin);

} else if (isset($input['startDate12']) && isset($input['endDate12'])) {

  $fechai = explode("T", $input['startDate12']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate12']);
  $fechafin = $fechaf[0];

  //Cantidad de mensajes enviados por día
  $json['monitoreo_dinp']['cancelaciones_mensajes_enviados_dia'] = $api->getCancelacionesMensajesEnviadosPorDia($fechainicio, $fechafin);
  
}else if (isset($input['startDate13']) && isset($input['endDate13'])) {

  $fechai = explode("T", $input['startDate13']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate13']);
  $fechafin = $fechaf[0];

  //Cantidad de mensajes enviados por orden (tabla)
  $json['monitoreo_dinp']['cancelaciones_mensajes_enviados_orden'] = $api->getCancelacionesMensajesEnviadosPorOrden($fechainicio, $fechafin);
}else if (isset($input['reglas'])) {
  
  $json['monitoreo_dinp']['reglas'] = $api->getReglas();
} else {
  $json['error'] = false;
  $json['message'] = 'Se necesitan las dos fchas';
}

echo json_encode($json);
