<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();

if (isset($input['municipios'])) {

  //municipios
  $json['contact']['municipios'] = $api->getMunicipios();
} else if (isset($input['startDate']) && isset($input['endDate']) && isset($input['municipio']) && isset($input['ubicacion'])) {

  $fechainicio = $input['startDate'];
  $fechafin =  $input['endDate'];
  $municipio = strtolower($input['municipio']);
  $ubicacion =  strtolower($input['ubicacion']);


  //tipificacion kpis
  //llamadas cuentas validas
  $json['contact']['llamadas_cuentas_validas'] = $api->LlamadasCuentasValidas($fechainicio, $fechafin, $municipio, $ubicacion);

  //llamadas telefno validas
  $json['contact']['llamadas_telefono_validos'] = $api->LlamadasTelefonoValidas($fechainicio, $fechafin, $municipio, $ubicacion);

  //llamadas cunetas  y telefonos validos
  $json['contact']['llamadas_telefonoCuentas_validas'] = $api->LlamadasCunetasTelefonoValidos($fechainicio, $fechafin, $municipio, $ubicacion);

  //kpi nuevas cuentas
  $json['contact']['nuevas_cuentas'] = $api->nuevasCuentas($fechainicio, $fechafin, $municipio, $ubicacion);

} else if (isset($input['startDate']) && isset($input['endDate'])) {

  $fechainicio = $input['startDate'];
  $fechafin =  $input['endDate'];

  //gestion diaria kpis
  $json['contact']['gestion_diaria'] = $api->porcentajesKpi($fechainicio, $fechafin);
} else if (isset($input['mes']) && isset($input['anio2'])) {

  $anio = explode('-', $input['anio2']);
  $anio2 = $anio[0];
  $mes = explode('-', $input['mes']);
  $mes = $anio[1];

  //gestion diaria grafics dias
  $json['contact']['gestion_diaria_grafica'] = $api->getDataGraficaDia($anio2, $mes);
} else if (isset($input['anio'])) {

  $fecha = explode('-', $input['anio']);
  $anio = $fecha[0];

  //gestion diaria grafics meses
  $json['contact']['gestion_diaria_grafica'] = $api->getDataGraficaMes($anio);
} else if (isset($input['startDate2']) && isset($input['endDate2'])) {

  $fechai = explode("T", $input['startDate2']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate2']);
  $fechafin = $fechaf[0];

  //gestion diaria grafics semanas
  $json['contact']['gestion_diaria_grafica'] = $api->getDataGraficaSemana($fechainicio, $fechafin);
} else if (isset($input['startDate3']) && isset($input['endDate3'])  && isset($input['municipio']) && isset($input['ubicacion'])) {

  $fechai = explode("T", $input['startDate3']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate3']);
  $fechafin = $fechaf[0];
  $municipio = strtolower($input['municipio']);
  $ubicacion =  strtolower($input['ubicacion']);

  //modificaciones
  $json['contact']['modificaciones'] = $api->modificaciones($fechainicio, $fechafin, $municipio, $ubicacion);
} else if (isset($input['startDate4']) && isset($input['endDate4'])  && isset($input['municipio']) && isset($input['ubicacion'])) {

  $fechai = explode("T", $input['startDate4']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate4']);
  $fechafin = $fechaf[0];
  $municipio = strtolower($input['municipio']);
  $ubicacion =  strtolower($input['ubicacion']);

  //confirmaciones
  $json['contact']['confirmaciones'] = $api->confirmaciones($fechainicio, $fechafin, $municipio, $ubicacion);
} else {
  $json['error'] = false;
  $json['message'] = 'Parametros incorrectos';
}

echo json_encode($json);
