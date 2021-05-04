import { Component, OnInit } from '@angular/core';
import { CustomTooltips } from '@coreui/coreui-plugin-chartjs-custom-tooltips';
import { getStyle } from '@coreui/coreui/dist/js/coreui-utilities';
import * as moment from 'moment';
import { ServiceProvider } from '../../../config/services';
import { WEBSERVICE } from '../../../config/webservices';
import { FormGroup, FormControl } from '@angular/forms';
import { ChartOptions } from 'chart.js';
import { MESSAGES } from '../../../config/messages';
import { Sort } from '@angular/material/sort';
//import { Chart } from 'chart.js';


//diagrama sankey
declare const initSankeyGraph: any;
//mapa
declare const initMapaGraph: any;
//mapa2
//declare const fn_drawMap: any;
//tomar captura de las imagenes
declare const faltaDeEnergiaGraficas: any;
//general pdf de toda la pantalla
declare const getPDF: any;

export interface DatosTablaFaltaEnergia {
  municipio: string;
  num: number;
  porUrbano: number;
  porRural: number;
  porReporte: number;
}

@Component({
  selector: 'app-falta-energia',
  templateUrl: './falta-energia.component.html',
})

export class FaltaEnergiaComponent implements OnInit {
  isVistaCargada: boolean = false;
  public MESSAGES: object = MESSAGES;
  public municipios: any[] = ["Todos"];
  public municipio = 'Todos';
  public ubicacion = 'Todos';
  public reportes = '';
  public reportesTelegram = '';
  public reportesChatWeb = '';
  public mesesChatweb: any = [];
  public mesesTelegram: any = [];
  public mesesTotalesReportes: any = [];
  public dataTable = [];
  public dataTableConsultas: DatosTablaFaltaEnergia[] = [];
  /** Pivote para mostrar datos ordenados y no afectar `dataTableConsultas` */
  public datosOrdenadosTablaMunicipio: DatosTablaFaltaEnergia[] = [];
  public sankey = [];
  public reportes_consultas_totales = 0;
  public selected: any = {
    startDate: moment().subtract(10, 'days'),
    endDate: moment(),
  };
  public dateForm = new FormGroup({
    date: new FormControl({ begin: new Date(this.selected.startDate), end: new Date() })
  });
  public reportesTotales = '';
  reportesUbicacionTieneDatos: boolean;
  reportesSegmentoTieneDatos: boolean;
  consultasUbicacionTieneDatos: boolean;
  consultasSegmentoTieneDatos: boolean;
  sankeyTieneDatos: boolean;

  constructor(private ServiceProvider: ServiceProvider) { }

  ngOnInit(): void {
    //this.getDataMonitoreoLucyFaltaEnergia(moment().subtract(29, 'days'), moment(), 'Todos', 'Todos');
    this.ServiceProvider.setTituloPestana("Falta de energía");
    this.lineChart1Options = this.cargarOpcionesGrafico(100);
    this.lineChart2Options = this.cargarOpcionesGrafico(100);
    this.lineChart3Options = this.cargarOpcionesGrafico(100);
    this.initCalendar();
  }


  /** Ordena los datos de la tabla Municipios dependiendo de la columna seleccionada por el usuario */
  sortTablaMunicipios(sort: Sort) {
    const data = [...this.dataTableConsultas];
    if (!sort.active || sort.direction === "") {
      this.datosOrdenadosTablaMunicipio = data;
      return;
    }
    this.datosOrdenadosTablaMunicipio = data.sort((a, b) => {
      const isAsc = sort.direction === "asc";
      switch (sort.active) {
        case "municipio": return this.ServiceProvider.compararDatosTabla(a.municipio, b.municipio, isAsc);
        case "num": return this.ServiceProvider.compararDatosTabla(a.num, b.num, isAsc);
        case "porUrbano": return this.ServiceProvider.compararDatosTabla(a.porUrbano, b.porUrbano, isAsc);
        case "porRural": return this.ServiceProvider.compararDatosTabla(a.porRural, b.porRural, isAsc);
        case "porReporte": return this.ServiceProvider.compararDatosTabla(a.porReporte, b.porReporte, isAsc);
        default: return 0;
      }
    });
  }

  //metodo para mejar las ecala a la que va a mostrr cada grafico
  calcularMedida(arr1) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    valorMaximo = Math.max.apply(null, arr1);

    return valorMaximo;
  }


  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoLucyFaltaEnergia(startDate, endDate, municipio, ubicacion) {
    this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        startDate: startDate,
        endDate: endDate,
        municipio: municipio,
        ubicacion: ubicacion
      };

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_LUCY_FALTA_ENERGIA, parameters);
      this.reportes = invocar.falta_energia.reportes;
      this.reportes_consultas_totales = invocar.falta_energia.reportes_consultas_totales;
      this.reportesTelegram = invocar.falta_energia.reportesSources.telegram;
      this.reportesChatWeb = invocar.falta_energia.reportesSources.chatWeb;
      this.reportesTotales = invocar.falta_energia.reportesSources.reportesTotales;
      this.mesesChatweb = this.objetcToArrayReportes(invocar.falta_energia.reportesMesesSources.chatWebMes);
      this.lineChart1Data[0].data = this.mesesChatweb;
      this.mesesTelegram = this.objetcToArrayReportes(invocar.falta_energia.reportesMesesSources.telegramMes);
      this.lineChart2Data[0].data = this.mesesTelegram;
      this.mesesTotalesReportes = this.objetcToArrayReportes(invocar.falta_energia.reportesMesesSourcesConsultas.reportesTotales);
      this.lineChart3Data[0].data = this.mesesTotalesReportes;
      this.doughnutChartData2 = [invocar.falta_energia.segmentos.ubicacion.urbano, invocar.falta_energia.segmentos.ubicacion.rural];
      this.reportesUbicacionTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData2);

      this.doughnutChartData = [
        invocar.falta_energia.segmentos.segmentos.hogares,
        invocar.falta_energia.segmentos.segmentos.empresas,
        invocar.falta_energia.segmentos.segmentos.grandesClientes,
        invocar.falta_energia.segmentos.segmentos.gobierno,
      ];
      this.reportesSegmentoTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData);

      this.doughnutChartData4 = [invocar.falta_energia.segmentos_consultas.ubicacion.urbano, invocar.falta_energia.segmentos_consultas.ubicacion.rural];
      this.consultasUbicacionTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData4);
      this.doughnutChartData3 = [
        invocar.falta_energia.segmentos_consultas.segmentos.hogares,
        invocar.falta_energia.segmentos_consultas.segmentos.empresas,
        invocar.falta_energia.segmentos_consultas.segmentos.grandesClientes,
        invocar.falta_energia.segmentos_consultas.segmentos.gobierno,
      ];
      this.consultasSegmentoTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData3);
      //this.dataTable = invocar.falta_energia.segmentos.dataTable
      this.dataTableConsultas = invocar.falta_energia.segmentos_consultas.dataTable;
      this.datosOrdenadosTablaMunicipio = [...this.dataTableConsultas];
      this.lineChart1Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart1Data[0].data));
      this.lineChart2Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart2Data[0].data));
      this.lineChart3Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart3Data[0].data));
      initMapaGraph(invocar.falta_energia.segmentos_consultas.municipio);

      const valoresSankey = invocar.falta_energia.sankey;
      this.sankeyTieneDatos = this.ServiceProvider.arrayTieneDatos(Object.values(valoresSankey));
      initSankeyGraph(valoresSankey);
      //fn_drawMap(invocar.falta_energia.segmentos.municipio);

      //console.log('consultas', this.reportes_consultas_totales);

    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoLucyMunicipios(municipios) {
    //this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        municipios: municipios
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_LUCY_FALTA_ENERGIA, parameters);
      this.municipios = invocar.falta_energia.municipios;

      //console.log(invocar);
    } catch (error) {
      console.error(error);
    } finally {
      // this.ServiceProvider.preloaderOff();
    }
  }

  initCalendar() {
    let finicio = this.dateForm.value.date.begin;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.dateForm.value.date.end;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    Promise.all([
      this.getDataMonitoreoLucyMunicipios('municipios'),
      this.getDataMonitoreoLucyFaltaEnergia(startDate, endDate, 'todos', 'todos')
    ]).finally(() => this.isVistaCargada = true);
  }

  change() {
    this.getDataMonitoreoLucyMunicipios('municipios');
    let finicio = this.dateForm.value.date.begin;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.dateForm.value.date.end;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    //console.log('fechas', startDate, endDate)
    this.getDataMonitoreoLucyFaltaEnergia(startDate, endDate, this.municipio, this.ubicacion);
  }

  objetcToArrayReportes(mesesPlataforma: object) {
    let meses = [];
    for (let mes in mesesPlataforma) {
      meses.push(mesesPlataforma[mes]);
    }
    //console.log(this.mesesChatweb);
    return meses;
  }

  //calendario

  ranges: any = {
    Today: [moment().startOf('day'), moment().endOf('day')],
    Yesterday: [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
    'This Month': [moment().startOf('month'), moment().endOf('month')],
    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
  };


  change1(e): void {
    if (e.endDate != '' && e.startDate != '') {
      //this.getDataMonitoreoLucy(e.startDate._d, e.endDate._d);
    }
  }

  // lineChart1

  cargarOpcionesGrafico(valorMaximo: number): ChartOptions {
    return {
      tooltips: {
        enabled: true,
        // position: "nearest"
        //custom: CustomTooltips
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
            display: false,
            min: 1 - 5,
            max: valorMaximo + 100,
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


  public lineChart1Data: Array<any> = [
    {
      data: [],
      label: 'Reportes'
    }
  ];
  public lineChart1Labels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart1Options: any = {};
  public lineChart1Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#00782B'
    }
  ];
  public lineChart1Legend = false;
  public lineChart1Type = 'line';


  // lineChart2
  public lineChart2Data: Array<any> = [
    {
      data: [],
      label: 'Reportes'
    }
  ];
  public lineChart2Labels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart2Options: any = {};
  public lineChart2Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#9cc129'
    }
  ];
  public lineChart2Legend = false;
  public lineChart2Type = 'line';

  // lineChart3
  public lineChart3Data: Array<any> = [
    {
      data: [],
      label: 'Reportes'
    }
  ];
  public lineChart3Labels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart3Options: any = {};
  public lineChart3Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#00782B'
    }
  ];
  public lineChart3Legend = false;
  public lineChart3Type = 'line';


  // Doughnut 1
  public doughnutChartLabels: string[] = ['Hogares', 'Empresas', 'Grandes Clientes', 'Gobierno'];
  public doughnutChartData: number[] = [];
  public doughnutChartType = 'doughnut';
  public doughnutChartColours: Array<any> = [
    {
      backgroundColor: ["#00782B", "#49F485", "#00BF43", "#00F255"]
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

  // Doughnut 3
  public doughnutChartLabels3: string[] = ['Hogares', 'Empresas', 'Grandes Clientes', 'Gobierno'];
  public doughnutChartData3: number[] = [];
  public doughnutChartType3 = 'doughnut';
  public doughnutChartColours3: Array<any> = [
    {
      backgroundColor: ["#00782B", "#49F485", "#00BF43", "#00F255"]
    }
  ];

  // backgroundColor: [ "#FF6384", "#63FF84", "#84FF63", "#8463FF", "#6384FF"] "#8463FF", "#6384FF"

  // Doughnut 43
  public doughnutChartLabels4: string[] = ['Urbano', 'Rural'];
  public doughnutChartData4 = [];
  public doughnutChartType4 = 'doughnut';
  public doughnutChartColours4: Array<any> = [
    {
      backgroundColor: ["#00782B", "#00BF43"]
    }
  ];

  //Captura de las graficas


  getPDF() {
    getPDF();
  }

  faltaDeEnergiaGraficas(id) {
    faltaDeEnergiaGraficas(id);
  }


  public regla = '';
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
}
