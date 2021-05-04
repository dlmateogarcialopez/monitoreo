import { Component, OnInit } from '@angular/core';
import { CustomTooltips } from '@coreui/coreui-plugin-chartjs-custom-tooltips';
import { getStyle } from '@coreui/coreui/dist/js/coreui-utilities';
import { WEBSERVICE } from '../../../config/webservices';
import { ServiceProvider } from '../../../config/services';
import * as moment from 'moment';
import { MatDatepicker } from '@angular/material/datepicker';
import { FormGroup, FormControl } from '@angular/forms';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import { ChartOptions, ChartDataSets } from 'chart.js';


//pantallaxo de los graficos
declare const contact: any;
//general pdf de la pantalla
declare const getPDF: any;

export const MY_FORMATS = {
  parse: {
    dateInput: 'YYYY',
  },
  display: {
    dateInput: 'YYYY',
    monthYearLabel: 'YYYY',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'YYYY',
  },
};

@Component({
  selector: 'app-contact',
  templateUrl: './contact.component.html',
  providers: [
    {
      provide: DateAdapter,
      useClass: MomentDateAdapter,
      deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
    },

    { provide: MAT_DATE_FORMATS, useValue: MY_FORMATS },
  ],
})
export class ContactComponent implements OnInit {
  isVistaCargada: boolean = false;
  public flag = '';
  public municipio = 'Todos';
  public municipios: any = ["Todos"];
  public ubicacion = 'Todos';
  public llamadasCuentasValidas: any = '';
  public llamadasTelefonosValidas: any = '';
  public llamadasTelefonosCuentasValidas: any = '';
  public nuevasCuentas: any = '';
  public modificaciones: any = '';
  public confirmaciones: any = '';
  public por_eficacia: any = '';
  public por_abandono: any = '';
  public por_nivel_servicio: any = '';
  public getSemana = '';
  public calendar = false;
  public habilitarCalendario = false;
  public semana = false;
  public mes = false;
  public dia = false;
  public fecha = moment();
  date = new FormControl(moment());
  public anioDia = '';
  public mesDia = '';
  public hora = false;
  public calendarDia = false;
  public isLoading: boolean = false;
  public isLoadingConfirmaciones: boolean = false;
  public isLoadingModificaciones: boolean = false;
  public selected: any = {
    startDate: moment().subtract(10, 'days'),
    endDate: moment(),
  };
  public dateForm = new FormGroup({ //calendario para la tabla
    date: new FormControl()
  });
  public dateForm2 = new FormGroup({ //calendario para los kpis
    date: new FormControl({ begin: new Date(this.selected.startDate), end: new Date() })
  });
  isLoadingFuentesContact: boolean;

  constructor(private ServiceProvider: ServiceProvider) { }

  ngOnInit(): void {
    this.ServiceProvider.setTituloPestana("Contact center");
    this.mainChartOptions = this.cargarGrafico(1000);
    this.getDataMonitoreoLucyMunicipios();
    this.initCalendar();
    this.tablaMes();
    this.getDataMonitoreoLucyContactMes(new Date());
  }


  //traer municipios
  async getDataMonitoreoLucyMunicipios() {
    //this.ServiceProvider.preloaderOn();
    try {
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, { municipios: 'municipios' });
      this.municipios = invocar.contact.municipios;
      //console.log('muni', invocar);
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  //calendario principal
  initCalendar() {
    let startDate2 = moment(this.dateForm2.value.date.begin).format('YYYY-MM-DD');
    let endDate2 = moment(this.dateForm2.value.date.end).format('YYYY-MM-DD');
    this.getDataMonitoreoContactTipificion(startDate2, endDate2, 'todos', 'todos');
    this.getDataMonitoreoContactTipificionModificaciones(startDate2, endDate2, 'todos', 'todos');
    this.getDataMonitoreoContactTipificionConfirmaciones(startDate2, endDate2, 'todos', 'todos');

    Promise.all([
      this.getDataMonitoreoContactGestionDiaria(startDate2, endDate2)
    ]).finally(() => this.isVistaCargada = true);
  }

  change() {
    //this.getDataMonitoreoLucyMunicipios('municipios');
    let finicio = this.selected.startDate._d;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');
    let startDate2 = moment(this.dateForm2.value.date.begin).format('YYYY-MM-DD');
    let ffin = this.selected.endDate._d;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');
    let endDate2 = moment(this.dateForm2.value.date.end).format('YYYY-MM-DD');
    // console.log(startDate2);
    // console.log(endDate2);
    this.getDataMonitoreoContactGestionDiaria(startDate2, endDate2);
    this.getDataMonitoreoContactTipificion(startDate2, endDate2, this.municipio, this.ubicacion);
    this.getDataMonitoreoContactTipificionModificaciones(startDate2, endDate2, this.municipio, this.ubicacion);
    this.getDataMonitoreoContactTipificionConfirmaciones(startDate2, endDate2, this.municipio, this.ubicacion);

  }

  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoContactTipificion(startDate, endDate, municipio, ubicacion) {
    //this.ServiceProvider.preloaderOn();
    this.isLoading = true;
    try {
      var parameters = {
        startDate: startDate,
        endDate: endDate,
        municipio: municipio,
        ubicacion: ubicacion
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, parameters);

      //console.log('innvocarr', invocar);
      this.llamadasCuentasValidas = invocar.contact.llamadas_cuentas_validas;
      this.llamadasTelefonosValidas = invocar.contact.llamadas_telefono_validos;
      this.llamadasTelefonosCuentasValidas = invocar.contact.llamadas_telefonoCuentas_validas;
      this.nuevasCuentas = invocar.contact.nuevas_cuentas;
      //this.modificaciones = invocar.contact.modificaciones;
      //this.confirmaciones = invocar.contact.confirmaciones;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoading = false;
    }
  }

  async getDataMonitoreoContactTipificionModificaciones(startDate, endDate, municipio, ubicacion) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingModificaciones = true;
    try {
      var parameters = {
        startDate3: startDate,
        endDate3: endDate,
        municipio: municipio,
        ubicacion: ubicacion
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, parameters);

      this.modificaciones = invocar.contact.modificaciones;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingModificaciones = false;
    }
  }

  async getDataMonitoreoContactTipificionConfirmaciones(startDate, endDate, municipio, ubicacion) {
    //this.ServiceProvider.preloaderOn();
    this.isLoadingConfirmaciones = true;
    try {
      var parameters = {
        startDate4: startDate,
        endDate4: endDate,
        municipio: municipio,
        ubicacion: ubicacion
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, parameters);

      this.confirmaciones = invocar.contact.confirmaciones;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
      this.isLoadingConfirmaciones = false;
    }
  }

  //informacion para los kpi de gestion diaria
  async getDataMonitoreoContactGestionDiaria(startDate, endDate) {
    this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        startDate: startDate,
        endDate: endDate
      };

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, parameters);

      this.por_eficacia = invocar.contact.gestion_diaria.por_eficacia;
      this.por_abandono = invocar.contact.gestion_diaria.por_abandono;
      this.por_nivel_servicio = invocar.contact.gestion_diaria.por_nivel_servicio;
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  //funciones
  contact(id) {
    contact(id);
  }

  //descargar imagenes
  getPDF() {
    getPDF();
  }

  //calendario grafica
  chosenYearHandler(normalizedYear: moment.Moment, datepicker: MatDatepicker<moment.Moment>) {
    const ctrlValue = this.date.value;
    ctrlValue.year(normalizedYear.year());
    this.date.setValue(ctrlValue);
    datepicker.close();
    return ctrlValue._d;
  }

  chosenYearHandler2(normalizedYear: moment.Moment) {
    const ctrlValue = this.date.value;
    ctrlValue.year(normalizedYear.year());
    this.date.setValue(ctrlValue);
    this.anioDia = ctrlValue._d;

  }

  chosenMonthHandler(normalizedMonth: moment.Moment, datepicker: MatDatepicker<moment.Moment>) {
    const ctrlValue = this.date.value;
    ctrlValue.month(normalizedMonth.month());
    this.date.setValue(ctrlValue);
    this.mesDia = ctrlValue._d;
    datepicker.close();
    this.changeDia();
  }

  //para obtener los valores de las llamadas entrantes
  getLlamadasEntrantes(datosPlataforma) {
    let datos = [];
    for (let mes in datosPlataforma) {
      datos.push(datosPlataforma[mes].e);
    }
    return datos;
  }

  //para obtener los valores de las llamadas costestadas
  getLlamadasContestadas(datosPlataforma) {
    let datos = [];
    for (let mes in datosPlataforma) {
      datos.push(datosPlataforma[mes].c);
    }
    return datos;
  }

  //metodo para mejar las ecala a la que va a mostrr cada grafico
  calcularMedida(arr1, arr2) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    valoresMaximos.push(Math.max.apply(null, arr1));
    valoresMaximos.push(Math.max.apply(null, arr2));
    valorMaximo = Math.max.apply(null, valoresMaximos);

    return valorMaximo;
  }

  //mes
  getMeses(datosPlataforma) {
    let meses = [];
    for (let mes in datosPlataforma) {
      switch (mes) {
        case "1":
          meses.push('Enero');
          break;
        case "2":
          meses.push('Febrero');
          break;
        case "3":
          meses.push('Marzo');
          break;
        case "4":
          meses.push('Abril');
          break;
        case "5":
          meses.push('Mayo');
          break;
        case "6":
          meses.push('Junio');
          break;
        case "7":
          meses.push('Julio');
          break;
        case "8":
          meses.push('Agosto');
          break;
        case "9":
          meses.push('Septiembre');
          break;
        case "10":
          meses.push('Octubre');
          break;
        case "11":
          meses.push('Noviembre');
          break;
        case "12":
          meses.push('Diciembre');
          break;
      }
    }
    return meses;
  }

  async getDataMonitoreoLucyContactMes(anio) {
    this.isLoadingFuentesContact = true;
    console.log('anio', anio);
    try {
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, { anio: anio });
      this.mainChartLabels = this.getMeses(invocar.contact.gestion_diaria_grafica);
      this.mainChartData[0].data = this.getLlamadasEntrantes(invocar.contact.gestion_diaria_grafica);
      this.mainChartData[1].data = this.getLlamadasContestadas(invocar.contact.gestion_diaria_grafica);
      let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data);
      this.mainChartOptions = this.cargarGrafico(valorMaximo);
    } catch (error) {
      console.error(error);
    } finally {
      this.isLoadingFuentesContact = false;
    }
  }


  changeMes(event, dp) {
    let fecha = this.chosenYearHandler(event, dp);
    this.getDataMonitoreoLucyContactMes(fecha);
    this.dateForm = new FormGroup({
      date: new FormControl()
    });
  }

  tablaMes() {
    this.flag = 'Meses';
    this.mes = true;
    this.semana = false;
    this.dia = false;
    //this.calendar = false;
  }


  //semana
  getDiasSemana(datosPlataforma) {
    let meses = [];
    for (let mes in datosPlataforma) {
      switch (mes) {
        case "1":
          meses.push('Lunes');
          break;
        case "2":
          meses.push('Martes');
          break;
        case "3":
          meses.push('Miercoles');
          break;
        case "4":
          meses.push('Jueves');
          break;
        case "5":
          meses.push('Viernes');
          break;
        case "6":
          meses.push('Sabado');
          break;
        case "7":
          meses.push('Domingo');
          break;
      }
    }
    return meses;
  }

  async getDataMonitoreoLucyContactSemanas(startDate2, endDate2) {
    this.isLoadingFuentesContact = true;
    try {
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, { startDate2, endDate2 });
      this.mainChartLabels = this.getDiasSemana(invocar.contact.gestion_diaria_grafica);
      this.mainChartData[0].data = this.getLlamadasEntrantes(invocar.contact.gestion_diaria_grafica);
      this.mainChartData[1].data = this.getLlamadasContestadas(invocar.contact.gestion_diaria_grafica);
      let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data);
      this.mainChartOptions = this.cargarGrafico(valorMaximo);
    } catch (error) {
      console.error(error);
    } finally {
      this.isLoadingFuentesContact = false;
    }
  }

  changeSemana() {
    //let startDate = this.dateForm.value.date.begin;
    let prueba: any = moment(this.dateForm.value.date.begin);
    //console.log('prueba semana', this.dateForm.value.date.begin);
    //console.log('prueba semana', this.dateForm.value.date.end);
    this.getDataMonitoreoLucyContactSemanas(this.dateForm.value.date.begin, this.dateForm.value.date.end);
    this.dateForm = new FormGroup({
      date: new FormControl()
    });
  }

  getCalendarSemana(valor) {
    this.getSemana = valor;
    this.calendar = false;
  }

  tablaSemana() {
    this.flag = 'Semana';
    this.semana = true;
    this.mes = false;
    this.dia = false;
    //this.calendar = false;
  }


  //dia
  getDias(datosPlataforma) {
    let dias = [];
    for (let mes in datosPlataforma) {
      dias.push(mes);
    }
    return dias;
  }

  async getDataMonitoreoLucyContactDias(anio2, mes) {
    this.isLoadingFuentesContact = true;
    try {
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_CONTACT, { anio2, mes });
      this.mainChartLabels = this.getDias(invocar.contact.gestion_diaria_grafica);
      this.mainChartData[0].data = this.getLlamadasEntrantes(invocar.contact.gestion_diaria_grafica);
      this.mainChartData[1].data = this.getLlamadasContestadas(invocar.contact.gestion_diaria_grafica);
      let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data);
      this.mainChartOptions = this.cargarGrafico(valorMaximo);
    } catch (error) {
      console.error(error);
    } finally {
      this.isLoadingFuentesContact = false;
    }
  }

  changeDia() {
    //console.log(this.mesDia);
    this.getDataMonitoreoLucyContactDias(this.anioDia, this.mesDia);
    this.dateForm = new FormGroup({
      date: new FormControl()
    });
  }

  tablaDia() {
    this.flag = 'Días';
    this.dia = true;
    this.mes = false;
    this.semana = false;
    //this.calendar = false;
  }



  //graficos
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


  // barChart3
  public barChart3Data: Array<any> = [
    {
      data: [],
      label: 'Mensajes'
    }
  ];
  public barChart3Labels: Array<any> = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  public barChart3Options: any = {
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
        display: false
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


  // mainChart

  cargarGrafico(valorMaximo: number): ChartOptions {
    return {
      tooltips: {
        enabled: false,
        custom: CustomTooltips,
        intersect: true,
        mode: 'index',
        position: 'nearest',
        callbacks: {
          labelColor: (tooltipItem, chart) => {
            return {
              backgroundColor: String(chart.data.datasets[tooltipItem.datasetIndex].borderColor),
              borderColor: String(chart.data.datasets[tooltipItem.datasetIndex].borderColor)
            };
          },
          label: (tooltipItem, data) => {
            const label = data.datasets[tooltipItem.datasetIndex].label || "";
            const value = this.ServiceProvider.formatNumero(Number(tooltipItem.yLabel));
            return `${label}: ${value}`;
          },
        }
      },
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        xAxes: [{
          gridLines: {
            drawOnChartArea: false,
          },
          ticks: {
            callback: (label: string) => label.substr(0, 3)
          }
        }],
        yAxes: [{
          ticks: {
            beginAtZero: true,
            maxTicksLimit: 5,
            stepSize: Math.ceil(valorMaximo / 5),
            // max: valorMaximo,
            callback: (value: number, index, values) => this.ServiceProvider.formatNumero(value)
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
    };
  }

  public mainChartElements = 31;
  public mainChartData: Array<any> = [
    {
      data: [],
      label: 'Llamadas entrantes'
    },
    {
      data: [],
      label: 'Llamadas contestadas'
    }
  ];
  /* tslint:disable:max-line-length */
  public mainChartLabels: Array<any> = [];
  /* tslint:enable:max-line-length */
  public mainChartOptions: any = {};
  public mainChartColours: Array<ChartDataSets> = [
    { // brandSuccess
      backgroundColor: '#9bc12933',
      borderColor: '#9bc129',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    { // brandInfo
      backgroundColor: '#00782b33',
      borderColor: '#00782b',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
  ];
  public mainChartType = 'line';





}
