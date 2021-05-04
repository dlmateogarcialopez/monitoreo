<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();


if (isset($input['startDate']) && isset($input['endDate'])) {

  $fechai = explode("T", $input['startDate']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate']);
  $fechafin = $fechaf[0];

  $json['monitoreo_general']['consultas_lucy_dia'] = $api->getConsultasLucyDia($fechainicio, $fechafin);

  $json['monitoreo_general']['mensajes_difusion_dia'] = $api->getMensajesDifusionDia($fechainicio, $fechafin);

  $json['monitoreo_general']['llamadas_dia'] = $api->getTipificacionDia($fechainicio, $fechafin);

  $json['monitoreo_general']['turnos_dia'] = $api->getTurnosDia($fechainicio, $fechafin);

  $json['monitoreo_general']['avisos_suspensiones'] = $api->getAvisosSuspensionesDia($fechainicio, $fechafin);
} else if (isset($input['startDate2']) && isset($input['endDate2'])) {

  $fechai = explode("T", $input['startDate2']);
  $fechainicio2 = $fechai[0];

  $fechaf =  explode("T", $input['endDate2']);
  $fechafin2 = $fechaf[0];

  $json['monitoreo_general']['consultas_lucy_hora'] = $api->getConsultasLucyHora($fechainicio2, $fechafin2);

  $json['monitoreo_general']['mensajes_difusion_hora'] = $api->getMensajesDifusionHora($fechainicio2, $fechafin2);

  $json['monitoreo_general']['llamadas_hora'] = $api->getTipificacionHora($fechainicio2, $fechafin2);

  $json['monitoreo_general']['turnos_hora'] = $api->getTurnosHora($fechainicio2, $fechafin2);

  $json['monitoreo_general']['avisos_suspensiones'] = $api->getAvisosSuspensionesHora($fechainicio2, $fechafin2);
} else if (isset($input['anio'])) {

  $fecha = explode('-', $input['anio']);
  $anio = $fecha[0];

  $json['monitoreo_general']['consultas_lucy_anio'] = $api->getConsultasLucyAno($anio);

  $json['monitoreo_general']['mensajes_difusion_anio'] = $api->getMensajesDifusionAno($anio);

  $json['monitoreo_general']['llamadas_anio'] = $api->getTipificacionAno($anio);

  $json['monitoreo_general']['turnos_anio'] = $api->getTurnosAno($anio);

  $json['monitoreo_general']['avisos_suspensiones'] = $api->getAvisosSuspensionesAno2($anio);
} else if (isset($input['mes']) && isset($input['anio2'])) {

  $anio = explode('-', $input['anio2']);
  $anio2 = $anio[0];
  $mes = explode('-', $input['mes']);
  $mes = $anio[1];

  $json['monitoreo_general']['consultas_lucy_mesAno'] = $api->get_ConsultasLucyDifllamMesAno($anio2, $mes, '$FECHA_RESULTADO', 'log_menu_usuarios');

  $json['monitoreo_general']['mensajes_difusion_mesAno'] = $api->get_ConsultasLucyDifllamMesAno($anio2, $mes, '$FECHA_ENVIO_APERTURA', 'log_difusion_enviados');

  $json['monitoreo_general']['llamadas_mesAno'] = $api->get_ConsultasLucyDifllamMesAno($anio2, $mes, '$Fecha', 'tipificacion');

  $json['monitoreo_general']['turnos_mesAno'] = $api->get_ConsultasLucyDifllamMesAno($anio2, $mes, '$FechaImpresion', 'turnos');

  $json['monitoreo_general']['avisos_suspensiones'] = $api->get_ConsultasLucyDifllamMesAno($anio2, $mes, '$FECHA_INICIO', 'susp_programadas_avisos');
} else {
  $json['error'] = false;
  $json['message'] = 'Se necesitan las dos fchas';
}

echo json_encode($json);
