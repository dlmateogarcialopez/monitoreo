import { Component, OnInit } from '@angular/core';
import { CustomTooltips } from '@coreui/coreui-plugin-chartjs-custom-tooltips';
import { getStyle } from '@coreui/coreui/dist/js/coreui-utilities';
import { ServiceProvider } from '../../../config/services';
import * as moment from 'moment';
import { WEBSERVICE } from '../../../config/webservices';
import { FormGroup, FormControl } from '@angular/forms';
import { Sort } from '@angular/material/sort';

//tomar captura de las imagenes
declare const copiaFacturaGraficas: any;

//pantallazo de toda la pantalla
declare const getPDF: any;

//mapa
declare const initMapaGraph: any;


export interface DatosTablaCopiaFactura {
  municipio: string;
  num: number;
  porUrbano: number;
  porcRural: number;
  porcon: number;
}

@Component({
  selector: 'app-copia-factura',
  templateUrl: './copia-factura.component.html'
})
export class CopiaFacturaComponent implements OnInit {
  isVistaCargada: boolean = false;
  descargasSegmentoTieneDatos: boolean;
  descargasUbicacionTieneDatos: boolean;

  public municipios: any = ["Todos"];
  public municipio = 'Todos';
  public ubicacion = 'Todos';
  public reportesTelegram = '';
  public reportesChatWeb = '';
  public mesesChatweb: any = [];
  public mesesTelegram: any = [];
  public mesesTotales: any = [];
  public dataTable: DatosTablaCopiaFactura[] = [];
  /** Pivote para mostrar datos ordenados y no afectar `dataTable` */
  public datosOrdenadosTablaMunicipio: DatosTablaCopiaFactura[] = [];
  public selected: any = {
    startDate: moment().subtract(10, 'days'),
    endDate: moment(),
  };
  public dateForm = new FormGroup({
    date: new FormControl({ begin: new Date(this.selected.startDate), end: new Date() })
  });
  public descargasTotales = '';

  constructor(private ServiceProvider: ServiceProvider) { }

  ngOnInit(): void {
    this.ServiceProvider.setTituloPestana("Copia de factura");
    this.lineChart1Options = this.cargarOpcionesGrafico(100);
    this.lineChart2Options = this.cargarOpcionesGrafico(100);
    this.initCalendar();
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
        case "num": return this.ServiceProvider.compararDatosTabla(a.num, b.num, isAsc);
        case "porUrbano": return this.ServiceProvider.compararDatosTabla(a.porUrbano, b.porUrbano, isAsc);
        case "porcRural": return this.ServiceProvider.compararDatosTabla(a.porcRural, b.porcRural, isAsc);
        case "porcon": return this.ServiceProvider.compararDatosTabla(a.porcon, b.porcon, isAsc);
        default: return 0;
      }
    });
  }

  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoLucyCopiaFactura(startDate, endDate, municipio, ubicacion) {
    this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        startDate: startDate,
        endDate: endDate,
        municipio: municipio,
        ubicacion: ubicacion
      };

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_LUCY_COPIA_FACTURA, parameters);
      this.reportesChatWeb = invocar.copia_factura.consultasCopiaFacturaSources.chatWeb;
      this.reportesTelegram = invocar.copia_factura.consultasCopiaFacturaSources.telegram;
      this.descargasTotales = invocar.copia_factura.consultasCopiaFacturaSources.copia_factura_total;



      this.mesesChatweb = this.objetcToArrayReportes(invocar.copia_factura.consultasCopiaFacturaSources.meses_chatweb);
      this.lineChart1Data[0].data = this.mesesChatweb;
      this.lineChart1Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart1Data[0].data));


      this.mesesTelegram = this.objetcToArrayReportes(invocar.copia_factura.consultasCopiaFacturaSources.meses_telegram);
      this.lineChart2Data[0].data = this.mesesTelegram;
      this.lineChart2Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart2Data[0].data));

      this.mesesTotales = this.objetcToArrayReportes(invocar.copia_factura.consultasCopiaFacturaSources.meses_total);
      this.lineChart3Data[0].data = this.mesesTotales;
      this.lineChart3Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart3Data[0].data));

      //console.log('valqerty',invocar.copia_factura.consultasCopiaFacturaSources);

      this.dataTable = invocar.copia_factura.segmentos.dataTable;
      this.datosOrdenadosTablaMunicipio = [...this.dataTable];
      this.doughnutChartData2 = [invocar.copia_factura.segmentos.ubicacion.urbano, invocar.copia_factura.segmentos.ubicacion.rural];
      this.descargasUbicacionTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData2);
      this.doughnutChartData = [
        invocar.copia_factura.segmentos.segmentos.hogares,
        invocar.copia_factura.segmentos.segmentos.empresas,
        invocar.copia_factura.segmentos.segmentos.grandesClientes,
        invocar.copia_factura.segmentos.segmentos.gobierno,
      ];
      this.descargasSegmentoTieneDatos = this.ServiceProvider.arrayTieneDatos(this.doughnutChartData);

      initMapaGraph(invocar.copia_factura.segmentos.municipio);
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  calcularMedida(arr1) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    for (let pos in arr1) {
      valoresMaximos.push(arr1[pos]);
    }

    valorMaximo = Math.max.apply(null, valoresMaximos);
    return valorMaximo;
  }


  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoLucyMunicipios(municipios) {
    //this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        municipios: municipios
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_LUCY_COPIA_FACTURA, parameters);
      this.municipios = invocar.falta_energia.municipios;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }


  //calendario

  initCalendar() {
    let finicio = this.dateForm.value.date.begin;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.dateForm.value.date.end;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    //console.log('fechascopia',startDate, endDate)

    Promise.all([
      this.getDataMonitoreoLucyMunicipios('municipios'),
      this.getDataMonitoreoLucyCopiaFactura(startDate, endDate, 'todos', 'todos')
    ]).finally(() => this.isVistaCargada = true);
  }


  change() {
    this.getDataMonitoreoLucyMunicipios('municipios');
    let finicio = this.dateForm.value.date.begin;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.dateForm.value.date.end;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    //console.log('fechascopiaflujo',startDate, endDate)
    this.getDataMonitoreoLucyCopiaFactura(startDate, endDate, this.municipio, this.ubicacion);
  }


  objetcToArrayReportes(mesesPlataforma: object) {
    let meses = [];
    for (let mes in mesesPlataforma) {
      meses.push(mesesPlataforma[mes]);
    }
    //console.log(this.mesesChatweb);
    return meses;
  }

  // lineChart1


  cargarOpcionesGrafico(valorMaximo) {
    return {
      tooltips: {
        enabled: true,
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
  public lineChart1Labels: Array<any> = ['Enero', 'FebRero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart1Options: any = {};
  public lineChart1Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#00782b'
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
  public lineChart2Labels: Array<any> = ['EneRo', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
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
  public lineChart3Labels: Array<any> = ['EneRo', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart3Options: any = {};
  public lineChart3Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#9cc129'
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



  //tomar cptras de las imagnes

  getPDF() {
    getPDF();
  }

  copiaFacturaGraficas(id) {
    copiaFacturaGraficas(id);
  }

}
