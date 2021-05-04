import { Component, OnInit } from '@angular/core';
import { CustomTooltips } from '@coreui/coreui-plugin-chartjs-custom-tooltips';
import { getStyle } from '@coreui/coreui/dist/js/coreui-utilities';
import * as moment from 'moment';
import { ServiceProvider } from '../../../config/services';
import { WEBSERVICE } from '../../../config/webservices';
import { FormGroup, FormControl } from '@angular/forms';
import { ChartOptions, ChartDataSets } from 'chart.js';
import { BreakpointObserver, BreakpointState } from '@angular/cdk/layout';
import { Sort } from '@angular/material/sort';



//grafico hora y día
//declare const initHeatMap: any;

//pantallaxo de los graficos
declare const dinp: any;

//general pdf de la pantalla
declare const getPDF: any;

//mapa
declare const initMapaGraph: any;

export interface DatosTablaDinp {
  municipio: string;
  municipioNum: number;
  porUrbano: number;
  porcRural: number;
  porMunicipio: number;
}


@Component({
  selector: 'app-dinp',
  templateUrl: './dinp.component.html'
})
export class DinpComponent implements OnInit {


  public selected: any = {
    startDate: moment().subtract(10, 'days'),
    endDate: moment(),
  };
  public difusionTotal = 0;
  public kpisDifusion: any = {};
  public promocion = 0;
  public promocionSuspensiones = 0;
  public dataTable: DatosTablaDinp[] = [];
  /** Pivote para mostrar datos ordenados y no afectar `dataTable` */
  public datosOrdenadosTablaMunicipio: DatosTablaDinp[] = [];
  public regla = "";
  public ubicacionPopover: string = "left";
  public dateForm = new FormGroup({
    date: new FormControl({ begin: new Date(this.selected.startDate), end: new Date() })
  });
  public isLoadingDiasSemana: boolean = false;
  public isLoadingMunicipio: boolean = false;
  public isLoadingReglas: boolean = false;
  public isLoadingPromocionLucy: boolean = false;
  public isLoadingTotalMensajes: boolean = false;
  public isLoadingMsjEnviadosEntregados: boolean = false;
  public isLoadingSegmentos: boolean = false;
  public isLoadingUbicacion: boolean = false;
  public isLoadingPromocionSuspensiones: boolean = false;
  public reglas = [
    { regla: "1", nombre: '1 - Empresas - Todos - 10' },
    { regla: "2", nombre: '2 - Empresas - Todos - 5' },
    { regla: "3", nombre: '3 - Gobierno - Todos - 10' },
    { regla: "4", nombre: '4 - Gobierno - Todos - 5' },
    { regla: "5", nombre: '5 - Grandes Clientes - Todos - 10' },
    { regla: "6", nombre: '6 - Grandes Clientes - Todos - 5' },
    { regla: "7", nombre: '7 - Empresas - Todos - 10' },
    { regla: "8", nombre: '8 - Empresas - Todos - 5' },
    { regla: "9", nombre: '9 - Gobierno - Todos - 10' },
    { regla: "10", nombre: '10 - Gobierno - Todos - 5' },
    { regla: "11", nombre: '11 - Grandes Clientes - Todos - 10' },
    { regla: "12", nombre: '12 - Grandes Clientes - Todos - 5' },
    { regla: "13", nombre: '13 - Empresas - Todos - 5' },
    { regla: "14", nombre: '14 - Gobierno - Todos - 5' },
    { regla: "15", nombre: '15 - Grandes Clientes - Todos - 5' },
    { regla: "16", nombre: '16 - Empresas - Todos - 5' },
    { regla: "17", nombre: '17 - Gobierno - Todos - 5' },
    { regla: "18", nombre: '18 - Grandes Clientes - Todos - 5' },
    { regla: "19", nombre: '19 - Hogares - Todos - 10' },
    { regla: "20", nombre: '20 - Hogares - Manizales,Dosquebradas - 5' },
    { regla: "21", nombre: '21 - Hogares - Todos-Manizales,Dosquebradas - 15' },
    { regla: "22", nombre: '22 - Hogares - Todos - 5' },
    { regla: "23", nombre: '23 - Hogares - Todos - 5' },
    { regla: "24", nombre: '24 - Hogares - Todos - 10' },
    { regla: "25", nombre: '25 - Hogares - Manizales,Dosquebradas - 5' },
    { regla: "26", nombre: '26 - Hogares - Todos-Manizales,Dosquebradas - 15' },
    { regla: "27", nombre: '27 - Hogares - Todos - 5' },
    { regla: "28", nombre: '28 - Hogares - Todos - 5' },
    { regla: "29", nombre: '29 - Empresas - Marmato - 10' },
    { regla: "30", nombre: '30 - Hogares - Todos - 10' },
    { regla: "31", nombre: '31 - Hogares - Todos - 10' },
  ];
  mensajesPorReglaTieneDatos: boolean;
  mensajesPorUbicacionTieneDatos: boolean;
  mensajesPorSegmentoTieneDatos: boolean;
  // public reglasDifusion: any[] = [];


  constructor(
    private ServiceProvider: ServiceProvider,
    public breakpointObserver: BreakpointObserver
  ) {

  }

  ngOnInit(): void {
    this.ServiceProvider.setTituloPestana("DINP");
    this.lineChart2Options = this.cargarOpcionesGrafico(100);
    //this.initData(this.invocar);
    this.initCalendar();

    this.breakpointObserver
      .observe(["(max-width: 992px)"])
      .subscribe((state: BreakpointState) => {
        if (state.matches) {
          this.ubicacionPopover = "right";
        } else {
          this.ubicacionPopover = "left";
        }
      });
  }

  /** Ordena los datos de la tabla Municipios dependiendo de la columna seleccionada por el usuario */
  sortTablaMunicipios(sort: Sort) {
    const data = [...this.dataTable];
    if (!sort.active || sort.direction === "") {
      this.datosOrdenadosTablaMunicipio = data;
      return;
    }
    this.datosOrdenadosTablaMunicipio = data.sort((a, b) => {
      const isAsc = sort.direction === "asc";
      switch (sort.active) {
        case "municipio": return this.ServiceProvider.compararDatosTabla(a.municipio, b.municipio, isAsc);
        case "municipioNum": return this.ServiceProvider.compararDatosTabla(a.municipioNum, b.municipioNum, isAsc);
        case "porUrbano": return this.ServiceProvider.compararDatosTabla(a.porUrbano, b.porUrbano, isAsc);
        case "porcRural": return this.ServiceProvider.compararDatosTabla(a.porcRural, b.porcRural, isAsc);
        case "porMunicipio": return this.ServiceProvider.compararDatosTabla(a.porMunicipio, b.porMunicipio, isAsc);
        default: return 0;
      }
    });
  }

  //traer las reglas
  async getReglas() {
    //this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        reglas: "reglas"
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      let reglass = invocar.monitoreo_dinp.reglas;
      this.reglas = reglass.reglas;
      this.reglasDifusion = reglass.reglasTotales;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }


  //traer datos de las consultas de monitoreo lucy
  /*async getDataMonitoreoLucyFaltaEnergia(startDate, endDate, regla) {
    this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        startDate: startDate,
        endDate: endDate,
        regla: regla
      }
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.difusionTotal = invocar.monitoreo_dinp.difusion_total.total_msm_enviados;

      this.lineChart2Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.difusion_total.total_msm_enviados_mes);
      this.lineChart2Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart2Data[0].data));

      this.kpisDifusion = invocar.monitoreo_dinp.kpis;
      this.barChart3Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.publicidad.promocion_mensual);
      this.promocion = invocar.monitoreo_dinp.publicidad.promocionTotal
      this.dataTable = invocar.monitoreo_dinp.tabla.dataTable
      this.doughnutChartData2 = [invocar.monitoreo_dinp.tabla.ubicacion.urbano, invocar.monitoreo_dinp.tabla.ubicacion.rural];
      this.doughnutChartData = [
        invocar.monitoreo_dinp.tabla.segmentos.hogares,
        invocar.monitoreo_dinp.tabla.segmentos.empresas,
        invocar.monitoreo_dinp.tabla.segmentos.grandesClientes,
        invocar.monitoreo_dinp.tabla.segmentos.gobierno,
      ]
      this.barChartLabels = invocar.monitoreo_dinp.grafico_barras.indices;
      this.barChartData[0].data = invocar.monitoreo_dinp.grafico_barras.valores

      initMapaGraph(invocar.monitoreo_dinp.tabla.municipio);
      this.ordenarGraficoPromedioDiaHora(invocar.monitoreo_dinp.promedio_hora_dia);
      //console.log(invocar.monitoreo_dinp.tabla.municipio);

    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }*/

  //Inicializar valores en 0
  async initData(invocarInicializar) {
    //this.ServiceProvider.preloaderOn();

    //console.log(parameters);
    const invocar: any = invocarInicializar;
    this.difusionTotal = invocar.monitoreo_dinp.difusion_total.total_msm_enviados;

    this.lineChart2Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.difusion_total.total_msm_enviados_mes);
    this.lineChart2Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart2Data[0].data));

    //this.kpisDifusion = invocar.monitoreo_dinp.kpis;
    this.barChart3Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.publicidad.promocion_mensual);
    this.promocion = invocar.monitoreo_dinp.publicidad.promocionTotal;
    this.dataTable = [...invocar.monitoreo_dinp.tabla.dataTable];
    this.datosOrdenadosTablaMunicipio = [...this.dataTable];
    this.doughnutChartData2 = [invocar.monitoreo_dinp.tabla.ubicacion.urbano, invocar.monitoreo_dinp.tabla.ubicacion.rural];
    this.mensajesPorUbicacionTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData2);

    this.doughnutChartData = [
      invocar.monitoreo_dinp.tabla.segmentos.hogares,
      invocar.monitoreo_dinp.tabla.segmentos.empresas,
      invocar.monitoreo_dinp.tabla.segmentos.grandesClientes,
      invocar.monitoreo_dinp.tabla.segmentos.gobierno,
    ];
    this.mensajesPorSegmentoTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData);

    this.barChartLabels = invocar.monitoreo_dinp.grafico_barras.indices;
    const valoresMensajesPorRegla: number[] = invocar.monitoreo_dinp.grafico_barras.valores;
    this.mensajesPorReglaTieneDatos = this.ServiceProvider.arrayTieneDatos(valoresMensajesPorRegla);
    this.barChartData[0].data = valoresMensajesPorRegla;

    initMapaGraph(invocar.monitoreo_dinp.tabla.municipio);
    //this.ordenarGraficoPromedioDiaHora(invocar.monitoreo_dinp.promedio_hora_dia);
    //console.log(invocar.monitoreo_dinp.tabla.municipio);
  }

  //DIFUSION DE MENSAJES ENVIADOS Y ENTREGADOS
  // async getDataMonitoreoLucyFaltaEnergiaMensajesEnviadosEntregadosAperturaCierre(startDate, endDate, regla) {
  //   //this.ServiceProvider.preloaderOn();
  //   this.isLoadingMsjEnviadosEntregados = true;
  //   try {
  //     var parameters = { startDate, endDate, regla };
  //     //console.log(parameters);
  //     const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
  //     this.kpisDifusion = invocar.monitoreo_dinp.kpis;
  //   } catch (error) {
  //     console.error(error);
  //   } finally {
  //     //this.ServiceProvider.preloaderOff();
  //     this.isLoadingMsjEnviadosEntregados = false;
  //   }
  // }

  //DIFUSION TOTAL
  async getDataMonitoreoLucyFaltaEnergiaDifusionTotal(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingTotalMensajes = true;
    try {
      var parameters = {
        startDate2: startDate,
        endDate2: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.difusionTotal = invocar.monitoreo_dinp.difusion_total.total_msm_enviados;
      this.lineChart2Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.difusion_total.total_msm_enviados_mes);
      this.lineChart2Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart2Data[0].data));
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingTotalMensajes = false;

    }
  }

  //PROMOCION LUCY
  async getDataMonitoreoLucyFaltaEnergiaPublicidadLucy(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingPromocionLucy = true;
    try {
      var parameters = {
        startDate3: startDate,
        endDate3: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.barChart3Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.publicidad.promocion_mensual);
      this.promocion = invocar.monitoreo_dinp.publicidad.promocionTotal;

    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingPromocionLucy = false;
    }

  }

  //PROMOCION suspensiones
  async getDataMonitoreoLucyFaltaEnergiaSuspensiones(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingPromocionSuspensiones = true;
    try {
      var parameters = {
        startDate10: startDate,
        endDate10: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      //this.barChart3Data[0].data = this.objetcToArrayReportes(invocar.monitoreo_dinp.publicidad.promocion_mensual);
      this.promocionSuspensiones = invocar.monitoreo_dinp.publicidadSuspensiones.promocionTotalSuspensiones;

    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingPromocionSuspensiones = false;
    }
  }

  //MENSAJES ENVIADOS POR HORA Y DIA DE LA SEMANA
  async getDataMonitoreoLucyFaltaEnergiaMensajeHoraDia(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingDiasSemana = true;
    try {
      var parameters = {
        startDate4: startDate,
        endDate4: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.ordenarGraficoPromedioDiaHora(invocar.monitoreo_dinp.promedio_hora_dia);

    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingDiasSemana = false;
    }
  }

  //Mensajes enviados por regla - grafico
  async getDataMonitoreoLucyFaltaEnergiaReglas(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingReglas = true;
    try {
      var parameters = {
        startDate5: startDate,
        endDate5: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.barChartLabels = invocar.monitoreo_dinp.grafico_barras.indices;
      const valoresMensajesPorRegla: number[] = invocar.monitoreo_dinp.grafico_barras.valores;
      this.mensajesPorReglaTieneDatos = this.ServiceProvider.arrayTieneDatos(valoresMensajesPorRegla);
      this.barChartData[0].data = valoresMensajesPorRegla;


    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingReglas = false;
    }
  }

  //mapa - tabla- ensajes por segmentos- mensajes por ubicacion
  async getDataMonitoreoLucyFaltaEnergiaMapaTabla(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingMunicipio = true;
    try {
      var parameters = {
        startDate6: startDate,
        endDate6: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.dataTable = [...invocar.monitoreo_dinp.tabla.dataTable];
      this.datosOrdenadosTablaMunicipio = [...this.dataTable];
      initMapaGraph(invocar.monitoreo_dinp.tabla.municipio);
      /*this.doughnutChartData2 = [invocar.monitoreo_dinp.difusion_ubicacion.urbano, invocar.monitoreo_dinp.difusion_ubicacion.rural];
      this.doughnutChartData = [
        invocar.monitoreo_dinp.difusion_segmentos.hogares,
        invocar.monitoreo_dinp.difusion_segmentos.empresas,
        invocar.monitoreo_dinp.difusion_segmentos.grandesClientes,
        invocar.monitoreo_dinp.difusion_segmentos.gobierno,
      ]*/


    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingMunicipio = false;
    }
  }


  //mensajes por segmentos
  async getDataMonitoreoLucyFaltaEnergiaSegmentos(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingSegmentos = true;
    try {
      var parameters = {
        startDate7: startDate,
        endDate7: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);

      this.doughnutChartData = [
        invocar.monitoreo_dinp.difusion_segmentos.hogares,
        invocar.monitoreo_dinp.difusion_segmentos.empresas,
        invocar.monitoreo_dinp.difusion_segmentos.grandesClientes,
        invocar.monitoreo_dinp.difusion_segmentos.gobierno,
      ];
      this.mensajesPorSegmentoTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData);

    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingSegmentos = false;
    }
  }

  //mensajes por segmentos
  async getDataMonitoreoLucyFaltaEnergiaUbicacion(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingUbicacion = true;
    try {
      var parameters = {
        startDate8: startDate,
        endDate8: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);

      this.doughnutChartData2 = [invocar.monitoreo_dinp.difusion_ubicacion.urbano, invocar.monitoreo_dinp.difusion_ubicacion.rural];
      this.mensajesPorUbicacionTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData2);

    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingUbicacion = false;
    }
  }

  //mensajes por segmentos
  async getDataMonitoreoLucyMsgDifundidos(startDate, endDate, regla) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingMsjEnviadosEntregados = true;
    try {
      var parameters = {
        startDate9: startDate,
        endDate9: endDate,
        regla: regla
      };
      //console.log(parameters);
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_DINP, parameters);
      this.kpisDifusion = invocar.monitoreo_dinp.msg_enviados;
      
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingMsjEnviadosEntregados = false;
    }
  }


  public jsonDias() {
    return {
      '0': 0,
      '01': 0,
      '02': 0,
      '03': 0,
      '04': 0,
      '05': 0,
      '06': 0,
      '07': 0,
      '08': 0,
      '09': 0,
      '10': 0,
      '11': 0,
      '12': 0,
      '13': 0,
      '14': 0,
      '15': 0,
      '16': 0,
      '17': 0,
      '18': 0,
      '19': 0,
      '20': 0,
      '21': 0,
      '22': 0,
      '23': 0
    };
  }

  ordenarGraficoPromedioDiaHora(valores) {


    var horasLunes = this.jsonDias();
    var horasMartes = this.jsonDias();
    var horasMiercoles = this.jsonDias();
    var horasJueves = this.jsonDias();
    var horasViernes = this.jsonDias();
    var horasSabado = this.jsonDias();
    var horasDomingo = this.jsonDias();

    valores.forEach(element => {

      var hora = element.FECHA_EVENTO.split(' ');
      var fecha = new Date(hora[0]);

      hora = hora[1].split(':');

      if (fecha.getDay() == 0) {
        horasLunes[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      } else if (fecha.getDay() == 1) {
        horasMartes[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      } else if (fecha.getDay() == 2) {
        horasMiercoles[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      } else if (fecha.getDay() == 3) {
        horasJueves[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      } else if (fecha.getDay() == 4) {
        horasViernes[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      } else if (fecha.getDay() == 5) {
        horasSabado[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      } else if (fecha.getDay() == 6) {
        horasDomingo[hora[0]] += element.CANTIDAD_DIFUNDIDA;
      }

    });

    var diasCantidad = {
      0: horasDomingo,
      1: horasLunes,
      2: horasMartes,
      3: horasMiercoles,
      4: horasJueves,
      5: horasViernes,
      6: horasSabado
    };

    //initHeatMap(diasCantidad);
  }

  //metodo para mejar las ecala a la que va a mostrr cada grafico
  calcularMedida(arr1) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    valorMaximo = Math.max.apply(null, arr1);

    return valorMaximo;
  }

  objetcToArrayReportes(mesesPlataforma: object) {
    let meses = [];
    for (let mes in mesesPlataforma) {
      meses.push(mesesPlataforma[mes]);
    }
    return meses;
  }

  initCalendar() {
    let finicio = this.selected.startDate._d;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.selected.endDate._d;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    // this.getReglas();
    //this.getDataMonitoreoLucyFaltaEnergiaMensajesEnviadosEntregadosAperturaCierre(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaDifusionTotal(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaPublicidadLucy(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaSuspensiones(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaMensajeHoraDia('2020-08-30', '2020-09-07', '');
    //console.log('fechas', startDate, endDate);
    //this.getDataMonitoreoLucyFaltaEnergiaMensajeHoraDia(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaReglas(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaMapaTabla(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaSegmentos(startDate, endDate, '');
    this.getDataMonitoreoLucyFaltaEnergiaUbicacion(startDate, endDate, '');
    this.getDataMonitoreoLucyMsgDifundidos(startDate, endDate, '');

    //this.getDataMonitoreoLucyFaltaEnergia(startDate, endDate, '');
  }

  change() {
    let finicio = this.dateForm.value.date.begin;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.dateForm.value.date.end;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    //console.log('cambio', this.dateForm.value.date.begin, this.dateForm.value.date.end);
    //this.getDataMonitoreoLucyFaltaEnergiaMensajesEnviadosEntregadosAperturaCierre(startDate, endDate, '');
    //console.log('this.dateForm.value.date.begin, this.dateForm.value.date.end', this.dateForm.value.date.begin, this.dateForm.value.date.end)
    //console.log('startDate, endDate', startDate, endDate)
    this.regla = this.regla.length ? this.regla : "";
    this.getDataMonitoreoLucyFaltaEnergiaDifusionTotal(startDate, endDate, this.regla);
    this.getDataMonitoreoLucyFaltaEnergiaPublicidadLucy(startDate, endDate, this.regla);
    this.getDataMonitoreoLucyFaltaEnergiaSuspensiones(startDate, endDate, this.regla);
    //this.getDataMonitoreoLucyFaltaEnergiaMensajeHoraDia(this.dateForm.value.date.begin, this.dateForm.value.date.end, this.regla);
    this.getDataMonitoreoLucyFaltaEnergiaReglas(startDate, endDate, this.regla);
    this.getDataMonitoreoLucyFaltaEnergiaMapaTabla(startDate, endDate, this.regla);
    this.getDataMonitoreoLucyFaltaEnergiaSegmentos(startDate, endDate, this.regla);
    this.getDataMonitoreoLucyFaltaEnergiaUbicacion(startDate, endDate, this.regla);
    this.getDataMonitoreoLucyMsgDifundidos(startDate, endDate, this.regla);

    //this.getDataMonitoreoLucyFaltaEnergia(this.dateForm.value.date.begin, this.dateForm.value.date.end, this.regla);
  }

  // lineChart2
  cargarOpcionesGrafico(valorMaximo): ChartOptions {
    return {
      tooltips: {
        enabled: true,
        //custom: CustomTooltips,
        callbacks: {
          label: (tooltipItem, data) => {
            const label = data.datasets[tooltipItem.datasetIndex].label || "";
            const value = this.ServiceProvider.formatNumero(Number(tooltipItem.yLabel));
            return `${label}: ${value}`;
          },
        }
      },
      maintainAspectRatio: false,
      scales: {
        xAxes: [{
          gridLines: {
            color: 'transparent',
            zeroLineColor: 'transparent'
          },
          ticks: {
            fontSize: 2,
            fontColor: 'transparent',
          }

        }],
        yAxes: [{
          display: false,
          ticks: {
            // display: false,
            // min: 1 - 5,
            // max: valorMaximo + Math.round(valorMaximo / 4),
            callback: (value: number, index, values) => this.ServiceProvider.formatNumero(Number(value))
          }
        }],
      },
      elements: {
        line: {
          tension: 0.00001,
          borderWidth: 1
        },
        point: {
          radius: 4,
          hitRadius: 10,
          hoverRadius: 4,
        },
      },
      legend: {
        display: false
      }
    };
  }

  public lineChart2Data: Array<any> = [
    {
      data: [],
      label: 'Mensajes'
    }
  ];
  public lineChart2Labels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart2Options: any = {};
  public lineChart2Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#00782b'
    }
  ];
  public lineChart2Legend = false;
  public lineChart2Type = 'line';


  // Doughnut 1
  public doughnutChartLabels: string[] = ['Hogares', 'Empresas', 'Grandes Clientes', 'Gobierno'];
  public doughnutChartData: number[] = [];
  public doughnutChartOptions: ChartOptions = {
    tooltips: {
      callbacks: {
        label: (tooltipItem, data) => {
          const label = data.labels[tooltipItem.index] || "";
          const value = this.ServiceProvider.formatNumero(Number(data.datasets[0].data[tooltipItem.index]));
          return `${label}: ${value}`;
        },
      }
    },
  };
  public doughnutChartType = 'doughnut';
  public doughnutChartColours: Array<any> = [
    {
      backgroundColor: ["#00782B", "#89ea5f", "#00BF43", "#06f200"]
    }
  ];

  // backgroundColor: [ "#FF6384", "#63FF84", "#84FF63", "#8463FF", "#6384FF"] "#8463FF", "#6384FF"

  // Doughnut 2
  public doughnutChartLabels2: string[] = ['Urbano', 'Rural'];
  public doughnutChartData2 = [];
  public doughnutChartType2 = 'doughnut';
  public doughnutChartColours2: Array<any> = [
    {
      backgroundColor: ["#00782B", "#00BF43"]
    }
  ];

  // social box charts 1
  public brandBoxChartData1: Array<any> = [
    {
      data: [],
      label: 'Twitter'
    }
  ];

  public brandBoxChartLabels1: Array<any> = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
  public brandBoxChartOptions1: any = {
    tooltips: {
      enabled: false,
      custom: CustomTooltips
    },
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      xAxes: [{
        display: false,
      }],
      yAxes: [{
        display: false,
      }]
    },
    elements: {
      line: {
        borderWidth: 2
      },
      point: {
        radius: 0,
        hitRadius: 10,
        hoverRadius: 4,
        hoverBorderWidth: 3,
      }
    },
    legend: {
      display: false
    }
  };
  public brandBoxChartColours1: Array<any> = [
    {
      backgroundColor: 'rgba(255,255,255,.1)',
      borderColor: 'rgba(255,255,255,.55)',
      pointHoverBackgroundColor: '#fff'
    }
  ];
  public brandBoxChartLegend1 = false;
  public brandBoxChartType1 = 'line';


  // social box charts 2
  public brandBoxChartData2: Array<any> = [
    {
      data: [],
      label: 'Twitter'
    }
  ];

  public brandBoxChartLabels: Array<any> = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
  public brandBoxChartOptions: any = {
    tooltips: {
      enabled: false,
      custom: CustomTooltips
    },
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      xAxes: [{
        display: false,
      }],
      yAxes: [{
        display: false,
      }]
    },
    elements: {
      line: {
        borderWidth: 2
      },
      point: {
        radius: 0,
        hitRadius: 10,
        hoverRadius: 4,
        hoverBorderWidth: 3,
      }
    },
    legend: {
      display: false
    }
  };
  public brandBoxChartColours: Array<any> = [
    {
      backgroundColor: 'rgba(255,255,255,.1)',
      borderColor: 'rgba(255,255,255,.55)',
      pointHoverBackgroundColor: '#fff'
    }
  ];
  public brandBoxChartLegend = false;
  public brandBoxChartType = 'line';

  // barChart3
  public barChart3Data: Array<any> = [
    {
      data: [],
      label: 'Mensajes'
    }
  ];
  public barChart3Labels: Array<any> = ['Enero', 'FebrerO', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public barChart3Options: ChartOptions = {
    tooltips: {
      enabled: false,
      custom: CustomTooltips
    },
    maintainAspectRatio: false,
    scales: {
      xAxes: [{
        display: false
      }],
      yAxes: [{
        display: false,
        ticks: {
          callback: (value: number, index, values) => this.ServiceProvider.formatNumero((value))
        }
      }]
    },
    legend: {
      display: false
    }
  };


  public barChart3Secondary: Array<any> = [
    {
      backgroundColor: getStyle('--white'),
      borderColor: 'transparent',
      borderWidth: 1
    }
  ];

  public barChart3Legend = false;
  public barChart3Type = 'bar';


  // barChart apertura
  public barChartOptions: ChartOptions = {
    scales: {
      yAxes: [{
        ticks: {
          callback: (value: number, index, values) => this.ServiceProvider.formatNumero(value)
        }
      }]
    },
    tooltips: {
      callbacks: {
        label: (tooltipItem, data) => {
          const label = data.datasets[tooltipItem.datasetIndex].label || "";
          const value = this.ServiceProvider.formatNumero(Number(tooltipItem.yLabel));
          return `${label}: ${value}`;
        },
      }
    },
  };
  public barChartLabels: string[] = [];
  public barChartType = 'bar';
  public barChartLegend = false;

  public barChartData: ChartDataSets[] = [{
    hoverBorderColor: "#138c0a7a",
    hoverBackgroundColor: "#138c0a7a",
    label: 'Regla',
    data: [],
    backgroundColor: [
      '#00782b',
      '#00782b',
      '#00782b',
      '#00782b',
      '#00782b',
      '#9cc129',
      '#9cc129',
      '#9cc129',
      '#9cc129',
      '#9cc129',
    ],
    borderColor: [
      '#00782b',
      '#00782b',
      '#00782b',
      '#00782b',
      '#00782b',
      '#9cc129',
      '#9cc129',
      '#9cc129',
      '#9cc129',
      '#9cc129',
    ],
    borderWidth: 1
  }];


  //tomar pantallazos de los graficos

  getPDF() {
    getPDF();
  }

  dinp(id) {
    dinp(id);
  }

  public invocar = {
    "monitoreo_dinp": {
      "difusion_total": {
        "total_msm_enviados": 0,
        "total_msm_enviados_mes": {
          "enero": 0,
          "febrero": 0,
          "marzo": 0,
          "abril": 0,
          "mayo": 0,
          "junio": 0,
          "julio": 0,
          "agosto": 0,
          "septiembre": 0,
          "octubre": 0,
          "noviembre": 0,
          "diciembre": 0
        }
      },
      "kpis": {
        "msj_enviados_apertura": 0,
        "porc_entregados_apertura": 0,
        "msj_enviados_cierre": 0,
        "porc_entregados_cierre": 0
      },
      "publicidad": {
        "promocionTotal": 0,
        "promocion_mensual": {
          "enero": 0,
          "febrero": 0,
          "marzo": 0,
          "abril": 0,
          "mayo": 0,
          "junio": 0,
          "julio": 0,
          "agosto": 0,
          "septiembre": 0,
          "octubre": 0,
          "noviembre": 0,
          "diciembre": 0
        }
      },
      "tabla": {
        "dataTable": [
          {
            "municipio": "MANIZALES",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "DOSQUEBRADAS",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "LA VIRGINIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "CHINCHINA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "PALESTINA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "VILLAMARIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "MARSELLA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SANTA ROSA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "RISARALDA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "ANSERMA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "VITERBO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "BELEN DE UMBRIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "NEIRA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "MARMATO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "PACORA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SUPIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "VICTORIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "NORCASIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "LA DORADA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "TAMESIS",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "JARDIN",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "ANDES",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "ABEJORRAL",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SANTA BARBARA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "LA PINTADA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "VALPARAISO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "CARAMANTA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "NARIÑO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "ARGELIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SONSON",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "MARULANDA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "PENSILVANIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SAMANA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SALAMINA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "AGUADAS",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "ARANZAZU",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "QUINCHIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SAN JOSE",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "BELALCAZAR",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "APIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "SANTUARIO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "MISTRATO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "FILADELFIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "LA MERCED",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "RIOSUCIO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "GUATICA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "MARQUETALIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "MANZANARES",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "BALBOA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "LA CELIA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "PUEBLO RICO",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          },
          {
            "municipio": "PEREIRA",
            "num": 0,
            "porcon": 0,
            "porUrbano": 0,
            "porcRural": 0
          }
        ],
        "segmentos": {
          "hogares": 0,
          "empresas": 0,
          "grandesClientes": 0,
          "gobierno": 0
        },
        "ubicacion": {
          "urbano": 0,
          "rural": 0
        },
        "clasesServicio": {
          "alumbrado": 0,
          "comercial": 0,
          "industria": 0,
          "oficial": 0,
          "otros": 0,
          "residencial": 0,
          "asistencial": 0,
          "educativo": 0,
          "areasComunes": 0,
          "oxigeno": 0,
          "provisional": 0
        },
        "municipio": {
          "MANIZALES": 0,
          "DOSQUEBRADAS": 0,
          "LA VIRGINIA": 0,
          "CHINCHINA": 0,
          "PALESTINA": 0,
          "VILLAMARIA": 0,
          "MARSELLA": 0,
          "SANTA ROSA": 0,
          "RISARALDA": 0,
          "ANSERMA": 0,
          "VITERBO": 0,
          "BELEN DE UMBRIA": 0,
          "NEIRA": 0,
          "MARMATO": 0,
          "PACORA": 0,
          "SUPIA": 0,
          "VICTORIA": 0,
          "NORCASIA": 0,
          "LA DORADA": 0,
          "TAMESIS": 0,
          "JARDIN": 0,
          "ANDES": 0,
          "ABEJORRAL": 0,
          "SANTA BARBARA": 0,
          "LA PINTADA": 0,
          "VALPARAISO": 0,
          "CARAMANTA": 0,
          "NARIÑO": 0,
          "ARGELIA": 0,
          "SONSON": 0,
          "MARULANDA": 0,
          "PENSILVANIA": 0,
          "SAMANA": 0,
          "SALAMINA": 0,
          "AGUADAS": 0,
          "ARANZAZU": 0,
          "QUINCHIA": 0,
          "SAN JOSE": 0,
          "BELALCAZAR": 0,
          "APIA": 0,
          "SANTUARIO": 0,
          "MISTRATO": 0,
          "FILADELFIA": 0,
          "LA MERCED": 0,
          "RIOSUCIO": 0,
          "GUATICA": 0,
          "MARQUETALIA": 0,
          "MANZANARES": 0,
          "BALBOA": 0,
          "LA CELIA": 0,
          "PUEBLO RICO": 0,
          "PEREIRA": 0
        },
        "municipioUrbano": {
          "MANIZALES": 0,
          "DOSQUEBRADAS": 0,
          "LA VIRGINIA": 0,
          "CHINCHINA": 0,
          "PALESTINA": 0,
          "VILLAMARIA": 0,
          "MARSELLA": 0,
          "SANTA ROSA": 0,
          "RISARALDA": 0,
          "ANSERMA": 0,
          "VITERBO": 7,
          "BELEN DE UMBRIA": 0,
          "NEIRA": 0,
          "MARMATO": 0,
          "PACORA": 0,
          "SUPIA": 0,
          "VICTORIA": 0,
          "NORCASIA": 0,
          "LA DORADA": 0,
          "TAMESIS": 0,
          "JARDIN": 0,
          "ANDES": 0,
          "ABEJORRAL": 0,
          "SANTA BARBARA": 0,
          "LA PINTADA": 0,
          "VALPARAISO": 0,
          "CARAMANTA": 0,
          "NARIÑO": 0,
          "ARGELIA": 0,
          "SONSON": 0,
          "MARULANDA": 0,
          "PENSILVANIA": 0,
          "SAMANA": 0,
          "SALAMINA": 0,
          "AGUADAS": 0,
          "ARANZAZU": 0,
          "QUINCHIA": 0,
          "SAN JOSE": 0,
          "BELALCAZAR": 0,
          "APIA": 0,
          "SANTUARIO": 0,
          "MISTRATO": 0,
          "FILADELFIA": 0,
          "LA MERCED": 0,
          "RIOSUCIO": 0,
          "GUATICA": 0,
          "MARQUETALIA": 0,
          "MANZANARES": 0,
          "BALBOA": 0,
          "LA CELIA": 0,
          "PUEBLO RICO": 0,
          "PEREIRA": 0
        },
        "municipioRural": {
          "MANIZALES": 0,
          "DOSQUEBRADAS": 0,
          "LA VIRGINIA": 0,
          "CHINCHINA": 0,
          "PALESTINA": 0,
          "VILLAMARIA": 0,
          "MARSELLA": 0,
          "SANTA ROSA": 0,
          "RISARALDA": 0,
          "ANSERMA": 0,
          "VITERBO": 0,
          "BELEN DE UMBRIA": 0,
          "NEIRA": 0,
          "MARMATO": 0,
          "PACORA": 0,
          "SUPIA": 0,
          "VICTORIA": 0,
          "NORCASIA": 0,
          "LA DORADA": 0,
          "TAMESIS": 0,
          "JARDIN": 0,
          "ANDES": 0,
          "ABEJORRAL": 0,
          "SANTA BARBARA": 0,
          "LA PINTADA": 0,
          "VALPARAISO": 0,
          "CARAMANTA": 0,
          "NARIÑO": 0,
          "ARGELIA": 0,
          "SONSON": 0,
          "MARULANDA": 0,
          "PENSILVANIA": 0,
          "SAMANA": 0,
          "SALAMINA": 0,
          "AGUADAS": 0,
          "ARANZAZU": 0,
          "QUINCHIA": 0,
          "SAN JOSE": 0,
          "BELALCAZAR": 0,
          "APIA": 0,
          "SANTUARIO": 0,
          "MISTRATO": 0,
          "FILADELFIA": 0,
          "LA MERCED": 0,
          "RIOSUCIO": 0,
          "GUATICA": 0,
          "MARQUETALIA": 0,
          "MANZANARES": 0,
          "BALBOA": 0,
          "LA CELIA": 0,
          "PUEBLO RICO": 6,
          "PEREIRA": 0
        },
        "usuariosMunicipio": {
          "MANIZALES": 0,
          "DOSQUEBRADAS": 0,
          "LA VIRGINIA": 0,
          "CHINCHINA": 0,
          "PALESTINA": 0,
          "VILLAMARIA": 0,
          "MARSELLA": 0,
          "SANTA ROSA": 0,
          "RISARALDA": 0,
          "ANSERMA": 0,
          "VITERBO": 0,
          "BELEN DE UMBRIA": 0,
          "NEIRA": 0,
          "MARMATO": 0,
          "PACORA": 0,
          "SUPIA": 0,
          "VICTORIA": 0,
          "NORCASIA": 0,
          "LA DORADA": 0,
          "TAMESIS": 0,
          "JARDIN": 0,
          "ANDES": 0,
          "ABEJORRAL": 0,
          "SANTA BARBARA": 0,
          "LA PINTADA": 0,
          "VALPARAISO": 0,
          "CARAMANTA": 0,
          "NARIÑO": 0,
          "ARGELIA": 0,
          "SONSON": 0,
          "MARULANDA": 0,
          "PENSILVANIA": 0,
          "SAMANA": 0,
          "SALAMINA": 0,
          "AGUADAS": 0,
          "ARANZAZU": 0,
          "QUINCHIA": 0,
          "SAN JOSE": 0,
          "BELALCAZAR": 0,
          "APIA": 0,
          "SANTUARIO": 0,
          "MISTRATO": 0,
          "FILADELFIA": 0,
          "LA MERCED": 0,
          "RIOSUCIO": 0,
          "GUATICA": 0,
          "MARQUETALIA": 0,
          "MANZANARES": 0,
          "BALBOA": 0,
          "LA CELIA": 0,
          "PUEBLO RICO": 0,
          "PEREIRA": 0
        }
      },
      "grafico_barras": {
        "indices": [
          0,
          0,
          0,
          0,
          0,
          0,
          0,
          0,
          0,
          0
        ],
        "valores": [
          0,
          0,
          0,
          0,
          0,
          0,
          0,
          0,
          0,
          0
        ]
      }
    }
  };

  public reglasDifusion: any[] = [
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b226"
      },
      "REGLA": "1",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b228"
      },
      "REGLA": "2",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b224"
      },
      "REGLA": "3",
      "SEGMENTO": [
        "Gobierno"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b225"
      },
      "REGLA": "4",
      "SEGMENTO": [
        "Gobierno"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b229"
      },
      "REGLA": "5",
      "SEGMENTO": [
        "Grandes Clientes"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b22a"
      },
      "REGLA": "6",
      "SEGMENTO": [
        "Grandes Clientes"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b22b"
      },
      "REGLA": "7",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "9:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b22c"
      },
      "REGLA": "8",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b22d"
      },
      "REGLA": "9",
      "SEGMENTO": [
        "Gobierno"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b22e"
      },
      "REGLA": "10",
      "SEGMENTO": [
        "Gobierno"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b22f"
      },
      "REGLA": "11",
      "SEGMENTO": [
        "Grandes Clientes"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b230"
      },
      "REGLA": "12",
      "SEGMENTO": [
        "Grandes Clientes"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b231"
      },
      "REGLA": "13",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b232"
      },
      "REGLA": "14",
      "SEGMENTO": [
        "Gobierno"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b233"
      },
      "REGLA": "15",
      "SEGMENTO": [
        "Grandes Clientes"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b234"
      },
      "REGLA": "16",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b235"
      },
      "REGLA": "17",
      "SEGMENTO": [
        "Gobierno"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b236"
      },
      "REGLA": "18",
      "SEGMENTO": [
        "Grandes Clientes"
      ],
      "CLASE_SERVICIO": [
        "Especial Asistencial"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b237"
      },
      "REGLA": "19",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "5:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b238"
      },
      "REGLA": "20",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "5:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Dosquebradas",
        "Manizales",
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b239"
      },
      "REGLA": "21",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "5:00:00",
      "HORA_FIN": "18:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 15
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b23a"
      },
      "REGLA": "22",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Oxigenodependientes"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b23b"
      },
      "REGLA": "23",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Oxigenodependientes"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b23c"
      },
      "REGLA": "24",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b23e"
      },
      "REGLA": "25",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Dosquebradas",
        "Manizales",
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b23d"
      },
      "REGLA": "26",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "8:00:00",
      "HORA_FIN": "17:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 15
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b23f"
      },
      "REGLA": "27",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Oxigenodependientes"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "19:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5cacb6dac388744b9d15b240"
      },
      "REGLA": "28",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Oxigenodependientes"
      ],
      "TIPO_HORARIO": "Nocturno",
      "HORA_INICIO": "19:00:00",
      "HORA_FIN": "7:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 5
    },
    {
      "_id": {
        "$oid": "5e4bec950641c403bcb50e5f"
      },
      "REGLA": "29",
      "SEGMENTO": [
        "Empresas"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Asistencial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "00:00:00",
      "HORA_FIN": "23:59:59",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
        "Domingo",
      ],
      "MUNICIPIO": ["Marmato"],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5ecfe44c8baa4b19dcdd2f0d"
      },
      "REGLA": "30",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "5:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Lunes",
        "Martes",
        "Miercoles",
        "Jueves",
        "Viernes",
        "Sabado",
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
    {
      "_id": {
        "$oid": "5ecfe5088baa4b19dcdd2f0e"
      },
      "REGLA": "31",
      "SEGMENTO": [
        "Hogares"
      ],
      "CLASE_SERVICIO": [
        "Areas comunes",
        "Comercial",
        "Especial Educativo",
        "Industrial",
        "Oficial",
        "Oxigenodependientes",
        "Provisional",
        "Residencial",
        "Servicios y Oficial"
      ],
      "TIPO_HORARIO": "Diurno",
      "HORA_INICIO": "7:00:00",
      "HORA_FIN": "22:00:00",
      "TIPO_DIA": [
        "Domingo",
        "Festivo"
      ],
      "MUNICIPIO": [
        "Aguadas",
        "Anserma",
        "Apia",
        "Aranzazu",
        "Balboa",
        "Belalcazar",
        "Belen De Umbria",
        "Chinchina",
        "Dosquebradas",
        "Filadelfia",
        "Guatica",
        "La Celia",
        "La Dorada",
        "La Merced",
        "La Virginia",
        "Manizales",
        "Manzanares",
        "Mariquita",
        "Marmato",
        "Marquetalia",
        "Marsella",
        "Marulanda",
        "Mistrato",
        "Nariño",
        "Neira",
        "Norcasia",
        "Pacora",
        "Palestina",
        "Pensilvania",
        "Pueblo Rico",
        "Quinchia",
        "Riosucio",
        "Risaralda",
        "Salamina",
        "Samana",
        "San Jose",
        "Santa Rosa",
        "Santuario",
        "Supia",
        "Tado",
        "Victoria",
        "Villamaria",
        "Viterbo"
      ],
      "MINUTOS": 10
    },
  ];

}
