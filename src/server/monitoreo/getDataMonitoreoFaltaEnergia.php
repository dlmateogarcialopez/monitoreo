<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();

if (isset($input['municipios'])) {

  //municipios
  $json['falta_energia']['municipios'] = $api->getMunicipios();
} else if (isset($input['startDate']) && isset($input['endDate']) && isset($input['municipio']) && isset($input['ubicacion'])) {

  $fechai = explode("T", $input['startDate']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate']);
  $fechafin = $fechaf[0];

  $municipio = strtolower($input['municipio']);
  $ubicacion =  strtolower($input['ubicacion']);

  //reportes hechos por fecha-municipio-ubicacion
  $json['falta_energia']['reportes'] = $api->getResportesFechaMunicipioUbicacion($fechainicio, $fechafin, $municipio, $ubicacion);
  
  //consultas totales por falta de energia por fecha-municipio-ubicacion 698
  $json['falta_energia']['reportes_consultas_totales'] = $api->getConsultasFechaMunicipioUbicacion($fechainicio, $fechafin, $municipio, $ubicacion);

  //reportes mensuales chattweb y telegram de consultas por falta de energia
  $json['falta_energia']['reportesMesesSourcesConsultas'] = $api->getResultadoInvocarMesConsultasFaltaEnergia($fechainicio, $fechafin, $municipio, $ubicacion);

  //reportes chatWeb y Telegram por fecha-municipio-ubicacion
  $json['falta_energia']['reportesSources'] = $api->getResportesWebTelegram($fechainicio, $fechafin, $municipio, $ubicacion);
  
  //consultas por criterio, filtrado por fecha-municipio-ubicacion
  $json['falta_energia']['sankey'] =  $api->getConsultasSegmentosUbicacionMunicipioFaltaEneria($fechainicio, $fechafin, $municipio, $ubicacion); //sankey - falta
  
  //reportes mensuales chattweb y telegram de roportes de energia
  $json['falta_energia']['reportesMesesSources'] = $api->getResultadoInvocarMesTelegramFaltaEnergia($fechainicio, $fechafin, $municipio, $ubicacion);
  
  //reportes por municipio, filtrado por fecha y ubicacion - mapa
  $json['falta_energia']['segmentos'] =  $api->reportesMunicipioFaltaDeEnergia($fechainicio, $fechafin, $municipio, $ubicacion, $json['falta_energia']['reportesMesesSources']['consulta']);

  //consultas por municipio, filtrado por fecha y ubicacion - mapa
  $json['falta_energia']['segmentos_consultas'] =  $api->consultasMunicipioFaltaDeEnergia($fechainicio, $fechafin, $municipio, $ubicacion, $json['falta_energia']['segmentos']);
} else {
  $json['error'] = false;
  $json['message'] = 'Parametros incorrectos';
}

echo json_encode($json);
