import { Component, OnInit, ViewChild } from '@angular/core';
import { getStyle } from '@coreui/coreui/dist/js/coreui-utilities';
import { CustomTooltips } from '@coreui/coreui-plugin-chartjs-custom-tooltips';
// import { DaterangepickerDirective } from 'ngx-daterangepicker-material';
import * as moment from 'moment';
import { WEBSERVICE } from '../../../config/webservices';
import { ServiceProvider } from '../../../config/services';
import { Chart, ChartOptions, ChartDataSets, ChartTooltipItem, ChartConfiguration } from 'chart.js';
import { FormGroup, FormControl } from '@angular/forms';
import { Sort } from '@angular/material/sort';



//funcion para tomar captura a la pantalla
declare const getPDF: any;
declare const tarjetaPagWeb: any;
declare const tarjetaTelegram: any;
declare const calificaciones: any;
declare const accesoMenu: any;
declare const accesoMenu2: any;
declare const fallbacks: any;
declare const submenu: any;
declare const indicadoresInteraccion: any;
declare const topConsultas: any;
//gththtr

export interface DatosTablaDinp {
  NOMBRE: string;
  NIU: number;
  NUMCONSULTAS: number;
  MUNICIPIO: number;
  TYPE: number;
}


@Component({
  selector: 'app-lucy',
  templateUrl: './lucy.component.html'
})
export class LucyComponent implements OnInit {
  isVistaCargada: boolean = false;
  @ViewChild('elementChartCalificaciones') private elementChartCalificaciones: any;
  public isLoading: boolean = false;
  public chatweb = 0;
  public totalAccesoMenu = 0;
  public telegram = 0;
  public mesesChatweb: any = [];
  public mesesTelegram: any = [];
  public mesesTotales: any = [];
  public acceso_menu: any = 0;
  public acceso_menu_total: any = 0;
  public graficaCalificaciones: Chart;
  public widthFaltaEnergia = '0%';
  public widthCopia = '0%';
  public widthVacantes = '0%';
  public widthPqr = '0%';
  public fecha: Date = new Date();
  public maxNumAccesosMesLucy: any = [];
  public width: any = {};
  selected: any = {
    startDate: moment().subtract(29, 'days'),
    endDate: moment(),
  };
  public dateForm = new FormGroup({
    date: new FormControl({ begin: new Date(this.selected.startDate), end: new Date() })
  });
  public conversaciones: any = [];
  public dataAtribute = { toggle: "", target: "" };
  public isLoadingFallbacks: boolean = true;
  public fallback: number = 0;
  public totalSubmenu: number = 0;
  public tasaAbandono: number = 0;
  public totalCalificacion = 0;
  public totalComentarios = 0;
  //public dataTable2 = [];
  arrayItemsAccesoMenu: any[];
  calificacionLucyTieneDatos: boolean;
  public isLoadingTop:boolean;

  constructor(private ServiceProvider: ServiceProvider,) {
    this.dataAtribute = { toggle: "modal", target: "#exampleModal" };
  }

  ngOnInit(): void {
    this.ServiceProvider.setTituloPestana("Monitoreo Lucy");
    //this.getDataMonitoreoLucy(moment().subtract(29, 'days'), moment());
    this.initCalendar();
    this.lineChart1Options = this.cargarOpcionesGrafico(100);
    this.lineChart2Options = this.cargarOpcionesGrafico(100);
  }

  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoLucy(startDate, endDate) {
    this.ServiceProvider.preloaderOn();
    this.isLoadingTop = true;
    try {
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_LUCY, { startDate: startDate, endDate: endDate });
      //this.chatweb = invocar.monitoreo_lucy.invocar.chatWeb;
      this.dataTable2 = [...invocar.monitoreo_lucy.topConsultas];
      this.datosOrdenadosTop = [...this.dataTable2];
      this.totalComentarios = invocar.monitoreo_lucy.comentarios;
      this.totalAccesoMenu = invocar.monitoreo_lucy.invocar;
      this.fallback = invocar.monitoreo_lucy.fallbacks;
      this.totalSubmenu = invocar.monitoreo_lucy.submenu;
      if (!this.totalAccesoMenu || this.totalSubmenu > this.totalAccesoMenu) {
        this.tasaAbandono = 0;
      } else {
        this.tasaAbandono = (this.totalAccesoMenu - this.totalSubmenu) * 100 / this.totalAccesoMenu;
      }
      //this.tasaAbandono = ((this.totalAccesoMenu - this.totalSubmenu) / this.totalAccesoMenu * 100) || 0;
      this.totalCalificacion = invocar.monitoreo_lucy.calificacion.totalCalificacion;
      //this.telegram = invocar.monitoreo_lucy.invocar.telegram;
      this.acceso_menu = invocar.monitoreo_lucy.menus;
      this.acceso_menu_total = invocar.monitoreo_lucy.menus_mes['12'];
      this.widthFaltaEnergia = this.acceso_menu.porcentajeFaltaEnergia + '%';
      this.widthCopia = this.acceso_menu.porcentajeCopiaFactura + '%';
      this.widthVacantes = this.acceso_menu.porcentajeVacantes + '%';
      this.widthPqr = this.acceso_menu.porcentajePqr + '%';

      //this.mesesChatweb = this.objetcToArrayChatweb(invocar.monitoreo_lucy.invocarMesesChatWeb);
      this.mesesTotales = this.objetcToArrayTotales(invocar.monitoreo_lucy.invocarMesesTotales);
      this.lineChart2Data[0].data = this.mesesTotales;
      this.lineChart2Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart2Data[0].data));


      this.mesesTelegram = this.objetcToArrayTelegram(invocar.monitoreo_lucy.invocarMesesTelegram);
      this.lineChart1Data[0].data = this.mesesTelegram;
      this.lineChart1Options = this.cargarOpcionesGrafico(this.calcularMedida(this.lineChart1Data[0].data));


      let meses = this.getAccesoMenuMneusal(invocar.monitoreo_lucy.menus_mes);
      this.mainChartData[0].data = meses['mainChartData1'];
      this.mainChartData[1].data = meses['mainChartData2'];
      this.mainChartData[2].data = meses['mainChartData3'];
      this.mainChartData[3].data = meses['mainChartData4'];
      this.mainChartData[4].data = meses['mainChartData5'];
      this.mainChartData[5].data = meses['mainChartData6'];
      this.mainChartData[6].data = meses['mainChartData7'];
      this.mainChartData[7].data = meses['mainChartData8'];

      this.maxNumAccesosMesLucy = this.maxConsultasAccesos(invocar.monitoreo_lucy.menus_mes);
      this.mainChartOptions = this.cargarOpcionesMainChart(this.calcularMedidaMainChart(this.maxNumAccesosMesLucy));

      // console.log('this.mainChartData[0]', this.mainChartData[0].data = meses['mainChartData1']);
      //console.log('this.mainChartData1', this.mainChartData1);

      //console.log('acceso_menu_total', this.totalAccesoMenu);
      //console.log('monitoreo_lucy', invocar.monitoreo_lucy.menus_mes);


      this.width = {
        widthFaltaEnergia: this.acceso_menu_total['porcen_Falta_Energia'] + '%',
        widthAsesor: this.acceso_menu_total['porcen_Asesor_remoto'] + '%',
        widthCopia: this.acceso_menu_total['porcen_Copia_factura'] + '%',
        widthPagaFactura: this.acceso_menu_total['porcen_Pago_factura'] + '%',
        widthPqr: this.acceso_menu_total['porcen_Pqr'] + '%',
        widthVacantes: this.acceso_menu_total['porcen_Vacantes'] + '%',
        widthPuntosAtencion: this.acceso_menu_total['porcen_Puntos_Atencion'] + '%',
        widthFraudes: this.acceso_menu_total['porcen_Fraudes'] + '%',
      };
      let options: ChartOptions = {
        legend: {
          position: 'top',
        },
        tooltips: {
          mode: 'index',
          // axis: 'y',
          // callbacks: {
          //   title: (tooltipItems, data) => {
          //     console.log(data.labels[tooltipItems[0].index]);
          //     return data.labels[tooltipItems[0].index]
          //   }
          // }
        },
        responsive: true,
        scales: {
          yAxes: [
            {
              id: 'emocion',
              stacked: true,
              type: 'linear',
              position: 'left',
            },
          ],
          xAxes: [
            {
              stacked: true,
              // barPercentage: 0.7,
              ticks: {
                fontSize: 12
              }
            }
          ]
        }
      };

      this.calificacionLucyTieneDatos = this.ServiceProvider.arrayTieneDatos(Object.values(invocar.monitoreo_lucy.calificacion));

      /* Se destruye la instancia de la gráfica de calificaciones para evitar bug en el que se muestran datos de consultas anteriores cuando se hace hover en la gráfica */
      if (this.graficaCalificaciones) {
        this.graficaCalificaciones.destroy();
      }

      this.graficaCalificaciones = new Chart(this.elementChartCalificaciones.nativeElement,
        {
          type: 'bar',
          data: {
            labels: [""],
            datasets: [
              {
                label: "Bueno",
                data: [invocar.monitoreo_lucy.calificacion.bueno],
                backgroundColor: "#4dbd747a"
              },
              {
                label: "Regular",
                data: [invocar.monitoreo_lucy.calificacion.regular],
                backgroundColor: "#ffc1077a"
              },
              {
                label: "Malo",
                data: [invocar.monitoreo_lucy.calificacion.malo],
                backgroundColor: "#f86c6b7a"
              }
            ]
          },
          options: options
        });



      this.arrayItemsAccesoMenu = [
        {
          nombreItem: "Falta de energía",
          cantidadAccesos: this.maxNumAccesosMesLucy.Falta_Energia,
          porcentajeAccesos: this.acceso_menu_total.porcen_Falta_Energia,
          colorFondo: this.mainChartColours[0].borderColor
        },
        {
          nombreItem: "Asesor remoto",
          cantidadAccesos: this.maxNumAccesosMesLucy.Asesor_remoto,
          porcentajeAccesos: this.acceso_menu_total.porcen_Asesor_remoto,
          colorFondo: this.mainChartColours[1].borderColor
        },
        {
          nombreItem: "Copia factura",
          cantidadAccesos: this.maxNumAccesosMesLucy.Copia_factura,
          porcentajeAccesos: this.acceso_menu_total.porcen_Copia_factura,
          colorFondo: this.mainChartColours[2].borderColor
        },
        {
          nombreItem: "Pago factura",
          cantidadAccesos: this.maxNumAccesosMesLucy.Pago_factura,
          porcentajeAccesos: this.acceso_menu_total.porcen_Pago_factura,
          colorFondo: this.mainChartColours[3].borderColor
        },
        {
          nombreItem: "PQR",
          cantidadAccesos: this.maxNumAccesosMesLucy.Pqr,
          porcentajeAccesos: this.acceso_menu_total.porcen_Pqr,
          colorFondo: this.mainChartColours[4].borderColor
        },
        {
          nombreItem: "Vacantes",
          cantidadAccesos: this.maxNumAccesosMesLucy.Vacantes,
          porcentajeAccesos: this.acceso_menu_total.porcen_Vacantes,
          colorFondo: this.mainChartColours[5].borderColor
        },
        {
          nombreItem: "Ptos. de atención",
          cantidadAccesos: this.maxNumAccesosMesLucy.Puntos_Atencion,
          porcentajeAccesos: this.acceso_menu_total.porcen_Puntos_Atencion,
          colorFondo: this.mainChartColours[6].borderColor
        },
        {
          nombreItem: "Fraude",
          cantidadAccesos: this.maxNumAccesosMesLucy.Fraudes,
          porcentajeAccesos: this.acceso_menu_total.porcen_Fraudes,
          colorFondo: this.mainChartColours[7].borderColor
        },
      ];

      /* Se ordenan ítems de mayor a menor cantidad de accesos  */
      this.arrayItemsAccesoMenu.sort((itemA, itemB) => itemB.cantidadAccesos - itemA.cantidadAccesos);
      //console.log(invocar);
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
      this.isLoadingTop = false;
    }
  }

  //extraer la fecha del objeto entrante
  getDates(arr) {
    let mensajes = [];
    let cont = 0;
    for (let conversaciones in arr) {
      arr[conversaciones]['fecha'] = arr[conversaciones][0].mensaje[0].fecha;
      mensajes[conversaciones] = arr[conversaciones];
    }
    return mensajes;
  }

  //traer el historial de fallbacks
  async getDataMonitoreoLucyHistorialFallback(startDate, endDate) {
    //this.ServiceProvider.preloaderOn();
    try {
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_LUCY, { startDate2: startDate, endDate2: endDate });
      this.conversaciones = this.getDates(invocar.monitoreo_lucy.info_preliminar_fallbacks.conversaciones);
      //console.log(invocar);
      this.isLoadingFallbacks = false;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  objetcToArrayChatweb(mesesPlataforma: object) {
    let meses = [];
    for (let mes in mesesPlataforma) {
      meses.push(mesesPlataforma[mes]);
    }
    //console.log(this.mesesChatweb);
    return meses;
  }

  objetcToArrayTelegram(mesesPlataforma: object) {
    let meses = [];
    for (let mes in mesesPlataforma) {
      meses.push(mesesPlataforma[mes]);
    }
    return meses;
  }

  objetcToArrayTotales(mesesPlataforma: object) {
    let meses = [];
    for (let mes in mesesPlataforma) {
      meses.push(mesesPlataforma[mes]);
    }
    return meses;
  }

  //metodo para mejar las ecala a la que va a mostrr cada grafico
  calcularMedida(arr1) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    valorMaximo = Math.max.apply(null, arr1);
    //console.log('segundo maximo', valorMaximo);
    return valorMaximo;
  }

  calcularMedidaMainChart(arr1) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    for (let pos in arr1) {
      valoresMaximos.push(arr1[pos]);
    }

    valorMaximo = Math.max.apply(null, valoresMaximos);
    return valorMaximo;
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

  initCalendar() {
    let finicio = this.selected.startDate._d;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let ffin = this.selected.endDate._d;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    //console.log('fehca', startDate, endDate);

    this.getDataMonitoreoLucyHistorialFallback(startDate, endDate);
    Promise.all([
      this.getDataMonitoreoLucy(startDate, endDate),
    ]).finally(() => {
      this.isVistaCargada = true;
    });
  }

  change() {
    //this.initDataGraph();
    //let finicio = this.selected.startDate._d;
    let startDate = moment(this.dateForm.value.date.begin).format('YYYY-MM-DD 00:00');
    //let ffin = this.selected.endDate._d;
    let endDate = moment(this.dateForm.value.date.end).format('YYYY-MM-DD 23:59');
    this.getDataMonitoreoLucy(startDate, endDate);
    this.getDataMonitoreoLucyHistorialFallback(startDate, endDate);

  }


  cargarOpcionesGrafico(valorMaximo): ChartOptions {
    return {
      tooltips: {
        enabled: true,
        // custom: CustomTooltips,
        // position: 'nearest'
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
            // max: valorMaximo + 1000,
            callback: (value: number, index, values) => this.ServiceProvider.formatNumero(value)
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
      },
    };
  }

  // lineChart chatweb
  public lineChart2Data: Array<any> = [
    {
      data: [],
      label: 'Consultas'
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
  public lineChart2Legend = true;
  public lineChart2Type = 'line';



  // lineChart telegram
  public lineChart1Data: Array<any> = [
    {
      data: [],
      label: 'Consultas'
    }
  ];
  public lineChart1Labels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public lineChart1Options: any = {};
  public lineChart1Colours: Array<any> = [
    { // grey
      backgroundColor: getStyle('--white'),
      borderColor: '#9cc129'
    }
  ];
  public lineChart1Legend = true;
  public lineChart1Type = 'line';


  // mainChart - Acceso a menú


  cargarOpcionesMainChart(valorMaximo: number) {
    return {
      tooltips: {
        enabled: false,
        custom: CustomTooltips,
        // backgroundColor: 'rgba(0, 0, 0, 0.4)',
        intersect: true,
        mode: 'index',
        position: 'nearest',
        callbacks: {
          labelColor: (tooltipItem: ChartTooltipItem, chart: ChartConfiguration) => {
            return {
              backgroundColor: chart.data.datasets[tooltipItem.datasetIndex].borderColor
            };
          },
          // label: (tooltipItem: ChartTooltipItem, data: ChartData) => {
          //   const label = data.datasets[tooltipItem.datasetIndex].label || "";
          //   const value = new Intl.NumberFormat("es-CO").format(Number(tooltipItem.yLabel));
          //   return `${label} ${value}`;
          // },
          // labelTextColor: (tooltipItem: ChartTooltipItem, data: ChartData) => {
          //   return "#fff";
          // }
        },
      },
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        xAxes: [{
          gridLines: {
            drawOnChartArea: false,
          },
          ticks: {
            /* Retorna primeras tres letras del mes */
            callback: (label: string) => label.substr(0, 3)
          }
        }],
        yAxes: [{
          ticks: {
            beginAtZero: true,
            maxTicksLimit: 12,
            stepSize: Math.ceil(valorMaximo / 12),
            // max: valorMaximo
          }
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
        labels: {
          // fontColor: 'rgb(255, 99, 132)'
        }
      }
    };
  }

  radioModel: string = 'Month';

  initDataGraph() {
    this.mainChartData1 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData2 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData3 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData4 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData5 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData6 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData7 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.mainChartData8 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  }

  public mainChartElements = 27;
  public mainChartData1: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData2: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData3: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData4: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData5: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData6: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData7: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  public mainChartData8: Array<number> = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

  public mainChartData: Array<any> = [
    {
      data: [],
      label: 'Falta de energía'
    },
    {
      data: [],
      label: 'Asesor remoto'
    },
    {
      data: [],
      label: 'Copia factura'
    },
    {
      data: [],
      label: 'Pago factura'
    },
    {
      data: [],
      label: 'PQR'
    },
    {
      data: [],
      label: 'Vacantes'
    },
    {
      data: [],
      label: 'Puntos de atención'
    },
    {
      data: [],
      label: 'Fraudes'
    }
  ];
  /* tslint:disable:max-line-length */
  public mainChartLabels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  /* tslint:enable:max-line-length */
  public mainChartOptions: any = {};
  public mainChartColours: Array<ChartDataSets> = [
    {
      backgroundColor: 'rgba(0, 128, 0, 0.1)',
      borderColor: 'rgba(0, 128, 0, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(0, 0, 255, 0.1)',
      borderColor: 'rgba(0, 0, 255, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(255, 166, 0, 0.1)',
      borderColor: 'rgba(255, 166, 0, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(170, 0, 0, 0.1)',
      borderColor: 'rgba(170, 0, 0, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(255, 0, 242, 0.1)',
      borderColor: 'rgba(255, 0, 242, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(255, 0, 0, 0.1)',
      borderColor: 'rgba(255, 0, 0, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(0, 165, 151, 0.1)',
      borderColor: 'rgba(0, 165, 151, 0.4)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(169, 0, 255, 0.26)',
      borderColor: 'rgba(169, 0, 255, 0.6)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
  ];
  public mainChartLegend = false;
  public mainChartType = 'line';

  public random(min: number, max: number) {
    return Math.floor(Math.random() * (max - min + 1) + min);
  }

  getAccesoMenuMneusal(accesosMneusalesLucy) {

    var mainChartData1 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData2 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData3 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData4 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData5 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData6 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData7 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var mainChartData8 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];


    for (let accesoMes in accesosMneusalesLucy) {
      switch (accesoMes) {
        case '0': {
          mainChartData1[parseInt(accesoMes)] = 5;
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '1': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '2': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '3': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '4': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '5': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '6': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '7': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '8': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '9': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '10': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
        case '11': {
          mainChartData1[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Falta de Energia'];
          mainChartData2[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Asesor remoto'];
          mainChartData3[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Copia factura'];
          mainChartData4[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pago factura'];
          mainChartData5[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Pqr'];
          mainChartData6[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Vacantes'];
          mainChartData7[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Puntos de Atencion'];
          mainChartData8[parseInt(accesoMes)] = accesosMneusalesLucy[accesoMes]['Fraudes'];
          break;
        }
      }

    }
    //console.log('mainChartData1', mainChartData1);
    return { 'mainChartData1': mainChartData1, 'mainChartData2': mainChartData2, 'mainChartData3': mainChartData3, 'mainChartData4': mainChartData4, 'mainChartData5': mainChartData5, 'mainChartData6': mainChartData6, 'mainChartData7': mainChartData7, 'mainChartData8': mainChartData8 };
    //return mainChartData1;
  }

  maxConsultasAccesos(accesosLucy): Array<number> {
    let numConsultas: Array<number> = [];
    numConsultas['Falta_Energia'] = 0;
    numConsultas['Asesor_remoto'] = 0;
    numConsultas['Copia_factura'] = 0;
    numConsultas['Pago_factura'] = 0;
    numConsultas['Pqr'] = 0;
    numConsultas['Vacantes'] = 0;
    numConsultas['Puntos_Atencion'] = 0;
    numConsultas['Fraudes'] = 0;

    for (let numAcceso in accesosLucy) {
      if (numAcceso != '12') {

        numConsultas['Falta_Energia'] += accesosLucy[parseInt(numAcceso)]['Falta de Energia'];
        numConsultas['Asesor_remoto'] += accesosLucy[parseInt(numAcceso)]['Asesor remoto'];
        numConsultas['Copia_factura'] += accesosLucy[parseInt(numAcceso)]['Copia factura'];
        numConsultas['Pago_factura'] += accesosLucy[parseInt(numAcceso)]['Pago factura'];
        numConsultas['Pqr'] += accesosLucy[parseInt(numAcceso)]['Pqr'];
        numConsultas['Vacantes'] += accesosLucy[parseInt(numAcceso)]['Vacantes'];
        numConsultas['Puntos_Atencion'] += accesosLucy[parseInt(numAcceso)]['Puntos de Atencion'];
        numConsultas['Fraudes'] += accesosLucy[parseInt(numAcceso)]['Fraudes'];
      }


      //let numeroMaximo = Math.max.apply(null, numConsultas);
      //this.mainChartOptions['scales']['yAxes'][0]['ticks']['max'] = numeroMaximo;
    }
    return numConsultas;
  }


  //descargar imagenes
  /*
  declare const tarjetaPagWeb:any;
declare const tarjetaTelegram:any;
declare const calificaciones:any;
declare const accesoMenu:any;
declare const accesoMenu2:any;
  */

  getPDF() {
    getPDF();
  }

  tarjetaPagWeb() {
    tarjetaPagWeb();
  }

  tarjetaTelegram() {
    tarjetaTelegram();
  }

  calificaciones() {
    calificaciones();
  }

  accesoMenu() {
    accesoMenu();
  }

  accesoMenu2() {
    accesoMenu2();
  }

  fallbacks() {
    fallbacks();
  }

  submenu() {
    submenu();
  }

  indicadoresInteraccion() {
    indicadoresInteraccion();
  }

  topConsultas(){
    topConsultas();
  }

  public prueba = 0;

  //Modal

  public color = '';
  public dateFormFallbaks = new FormGroup({
    date: new FormControl()
  });
  public messages: any = [];
  public conversacionDetalle = [];
  public mostrarchat = false;



  changeColor() {
    this.color = '';
  }

  changeFecha() {
    //this.tablaConversaciones = true;
    let startDate = moment(this.dateForm.value.date.begin).format('YYYY-MM-DD 00:00');
    //let ffin = this.selected.endDate._d;
    let endDate = moment(this.dateForm.value.date.end).format('YYYY-MM-DD 23:59');
    this.getDataMonitoreoLucyHistorialFallback(startDate, endDate);
  }

  //conversaciones
  selectConversation(conversacion) {
    this.messages = this.getDetailChat(conversacion);
    this.conversacionDetalle = this.getDetailChat(conversacion);
    this.mostrarchat = true;
    this.color = '#3c35355c';
    //console.log('aqui cambia ')
    //this.sendBy = this.conversacionDetalle.sendBy;
  }

  getDetailChat(arr) {
    let mensajes = [];
    let send = [];
    for (let conversaciones in arr) {
      //arr[conversaciones] = arr[conversaciones][0].mensaje[0].sendBy;
      mensajes[conversaciones] = arr[conversaciones].mensaje;

    }
    for (let sendBy in mensajes) {
      if (mensajes[sendBy] != undefined) {
        if (mensajes[sendBy][0].sentBy == 'Lucy') {
          mensajes[sendBy][0]['bot'] = true;
        } else if (mensajes[sendBy][0].sentBy == 'human') {
          mensajes[sendBy][0]['bot'] = false;
        }
        send[sendBy] = mensajes[sendBy][0];
      }
    }
    return send;
  }


  /** Pivote para mostrar datos ordenados y no afectar `dataTable` */
  public dataTable2: DatosTablaDinp[] = [];
  public datosOrdenadosTop: DatosTablaDinp[] = [];


  /** Ordena los datos de la tabla Municipios dependiendo de la columna seleccionada por el usuario */
  sortTablaTop(sort: Sort) {
    const data = [...this.dataTable2];
    if (!sort.active || sort.direction === "") {
      this.datosOrdenadosTop = data;
      return;
    }
    this.datosOrdenadosTop = data.sort((a, b) => {
      const isAsc = sort.direction === "asc";
      switch (sort.active) {
        case "NOMBRE": return this.ServiceProvider.compararDatosTabla(a.NOMBRE, b.NOMBRE, isAsc);
        case "NIU": return this.ServiceProvider.compararDatosTabla(a.NIU, b.NIU, isAsc);
        case "NUMCONSULTAS": return this.ServiceProvider.compararDatosTabla(a.NUMCONSULTAS, b.NUMCONSULTAS, isAsc);
        case "MUNICIPIO": return this.ServiceProvider.compararDatosTabla(a.MUNICIPIO, b.MUNICIPIO, isAsc);
        case "TYPE": return this.ServiceProvider.compararDatosTabla(a.TYPE, b.TYPE, isAsc);
        default: return 0;
      }
    });
  }

  dinp() {

  }

}
