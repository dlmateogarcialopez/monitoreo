import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { CustomTooltips } from '@coreui/coreui-plugin-chartjs-custom-tooltips';
import { WEBSERVICE } from '../../../config/webservices';
import { ServiceProvider } from '../../../config/services';
import * as moment from 'moment';
import { FormGroup, FormControl, FormBuilder } from '@angular/forms';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import { MatDatepicker } from '@angular/material/datepicker';
import { ChartDataSets, ChartOptions } from 'chart.js';

//funcion js para tomar pantallazos de las graficas
declare const general: any;


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

export const MY_FORMATS2 = {
  parse: {
    dateInput: 'MM/YYYY',
  },
  display: {
    dateInput: 'MM/YYYY',
    monthYearLabel: 'MMM YYYY',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'MMMM YYYY',
  },
};


@Component({
  selector: 'app-general',
  templateUrl: './general.component.html',
  providers: [
    {
      provide: DateAdapter,
      useClass: MomentDateAdapter,
      deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
    },

    { provide: MAT_DATE_FORMATS, useValue: MY_FORMATS },
  ],
})
export class GeneralComponent implements OnInit {
  isVistaCargada: boolean = false;
  public selected = {
    startDate: moment().subtract(29, 'days'),
    endDate: moment(),
  };
  public radioModel: string = 'dia';
  public mes = false;
  public semana = false;
  public dia = false;
  public hora = false;
  public fecha = moment();
  public flag = '';
  public calendar = false;
  public getHora = '';
  public getSemana = '';
  public getDia = '';
  public calendarSemana = false;
  public calendarDia = false;
  public getMes = '';
  public calendarMes = false;
  public dateForm = new FormGroup({
    date: new FormControl()
  });
  public habilitarCalendario = false;
  date = new FormControl(moment());
  public anioDia = '';
  public mesDia = '';
  public nombresMeses: string[] = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

  constructor(private ServiceProvider: ServiceProvider, private fb: FormBuilder) { }

  ngOnInit(): void {
    this.ServiceProvider.setTituloPestana("Monitoreo general");
    this.mainChartOptions = this.cargarGrafico(1000);
    this.tablaMes();
    this.getMes = "cantidad";
    this.getCalendarMes("cantidad");

    Promise.all([
      this.getDataMonitoreoLucyFaltaEnergiaMes(new Date())
    ]).finally(() => this.isVistaCargada = true);
  }

  //metodo para mejar las ecala a la que va a mostrr cada grafico
  calcularMedida(arr1, arr2, arr3, arr4, arr5) {

    let valoresMaximos = [];
    let valorMaximo = 0;

    valoresMaximos.push(Math.max.apply(null, arr1));
    valoresMaximos.push(Math.max.apply(null, arr2));
    valoresMaximos.push(Math.max.apply(null, arr3));
    valoresMaximos.push(Math.max.apply(null, arr4));
    valoresMaximos.push(Math.max.apply(null, arr5));

    valorMaximo = Math.max.apply(null, valoresMaximos);

    return valorMaximo;
  }

  //configuraciones para los meses
  async getDataMonitoreoLucyFaltaEnergiaMes(anio) {
    this.ServiceProvider.preloaderOn();
    try {

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_GENERAL, { anio: anio });

      if (this.getMes == 'cantidad') {
        this.mainChartLabels = this.getMeses(invocar.monitoreo_general.consultas_lucy_anio).map(mes => this.nombresMeses[mes - 1]);
        console.log("mainChartLabels", this.mainChartLabels);
        this.mainChartData[0].data = this.objetcToArray(invocar.monitoreo_general.consultas_lucy_anio);
        this.mainChartData[1].data = this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_anio);
        this.mainChartData[2].data = this.objetcToArray(invocar.monitoreo_general.llamadas_anio);
        this.mainChartData[3].data = this.objetcToArray(invocar.monitoreo_general.turnos_anio);
        this.mainChartData[4].data = this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);



        this.mainChartOptions = this.cargarGrafico(valorMaximo);
        //console.log('anioooo');
      } else if (this.getMes == 'costos') {
        this.mainChartLabels = this.getMeses(invocar.monitoreo_general.consultas_lucy_anio).map(mes => this.nombresMeses[mes - 1]);
        this.mainChartData[0].data = this.OrdenarPorClaveCostos(this.objetcToArray(invocar.monitoreo_general.consultas_lucy_anio), 800);
        this.mainChartData[1].data = this.OrdenarPorClaveCostos(this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_anio), 14);
        this.mainChartData[2].data = this.OrdenarPorClaveCostos(this.objetcToArray(invocar.monitoreo_general.llamadas_anio), 1506.7);
        this.mainChartData[3].data = this.OrdenarPorClaveCostos(this.objetcToArray(invocar.monitoreo_general.turnos_anio), 300);
        this.mainChartData[4].data = this.OrdenarPorClaveCostos(this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones), 25);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
      }



    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }


  getCalendarMes(valor: string) {
    this.getMes = valor;
    this.calendarMes = true;
    this.calendarDia = false;
    this.calendar = false;
    this.calendarSemana = false;


  }

  changeMes(event, dp) {
    let fecha = this.chosenYearHandler(event, dp);
    // console.log(fecha);

    this.getDataMonitoreoLucyFaltaEnergiaMes(fecha);
    this.dateForm = new FormGroup({
      date: new FormControl()
    });
  }

  clicked = false;
  tablaMes() {
    this.flag = 'mes';
    this.mes = true;
    this.semana = false;
    this.dia = false;
    this.hora = false;
    this.calendarDia = false;
    this.calendar = false;
    this.calendarSemana = false;

    if (this.clicked) {
      // this.date.setValue("");
    }
  }

  //configuracio para los días
  async getDataMonitoreoLucyFaltaEnergiaDia(anio2, mes) {
    this.ServiceProvider.preloaderOn();
    try {

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_GENERAL, { anio2, mes });
      this.mainChartLabels = Object.keys(invocar.monitoreo_general.mensajes_difusion_mesAno);
      //console.log(Object.keys(invocar.monitoreo_general.mensajes_difusion_mesAno));

      if (this.getDia == 'cantidad') {
        this.mainChartData[0].data = this.objetcToArray(invocar.monitoreo_general.consultas_lucy_mesAno);
        this.mainChartData[1].data = this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_mesAno);
        this.mainChartData[2].data = this.objetcToArray(invocar.monitoreo_general.llamadas_mesAno);
        this.mainChartData[3].data = this.objetcToArray(invocar.monitoreo_general.turnos_mesAno);
        this.mainChartData[4].data = this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
        //console.log(valorMaximo);
      } else if (this.getDia == 'costos') {
        this.mainChartData[0].data = this.costos(this.objetcToArray(invocar.monitoreo_general.consultas_lucy_mesAno), 800);
        this.mainChartData[1].data = this.costos(this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_mesAno), 14);
        this.mainChartData[2].data = this.costos(this.objetcToArray(invocar.monitoreo_general.llamadas_mesAno), 1506.7);
        this.mainChartData[3].data = this.costos(this.objetcToArray(invocar.monitoreo_general.turnos_mesAno), 300);
        this.mainChartData[4].data = this.costos(this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones), 25);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
      }
      //console.log(this.mainChartData[0].data);
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  getCalendarDia(valor) {
    this.getDia = valor;
    this.calendarDia = true;
    this.calendar = false;
    this.calendarSemana = false;
    this.calendarMes = false;
  }

  changeDia() {
    this.getDataMonitoreoLucyFaltaEnergiaDia(this.anioDia, this.mesDia);
    this.dateForm = new FormGroup({
      date: new FormControl()
    });
  }

  tablaDia() {
    this.flag = 'día';
    this.mes = false;
    this.semana = false;
    this.dia = true;
    this.hora = false;
    this.calendar = false;
    this.calendarSemana = false;
    this.calendarMes = false;
  }


  //configutacion para las horas
  async getDataMonitoreoLucyFaltaEnergiaHora(startDate2, endDate2) {
    this.ServiceProvider.preloaderOn();
    try {

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_GENERAL, { startDate2, endDate2 });
      this.mainChartLabels = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];

      if (this.getHora == 'cantidad') {
        this.mainChartData[0].data = this.objetcToArray(invocar.monitoreo_general.consultas_lucy_hora);
        this.mainChartData[1].data = this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_hora);
        //this.mainChartData[1].data = [3,4,3,2,1,4,3,43,443,23,2,4,8,98,7,6,5,4,34,23,32,12,2,4];
        this.mainChartData[2].data = this.objetcToArray(invocar.monitoreo_general.llamadas_hora);
        this.mainChartData[3].data = this.objetcToArray(invocar.monitoreo_general.turnos_hora);
        this.mainChartData[4].data = this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
        //console.log(this.cargarGrafico(valorMaximo));
        // console.log(this.mainChartData[4].data);
      } else if (this.getHora == 'costos') {
        this.mainChartData[0].data = this.costos(this.objetcToArray(invocar.monitoreo_general.consultas_lucy_hora), 800);
        this.mainChartData[1].data = this.costos(this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_hora), 14);
        this.mainChartData[2].data = this.costos(this.objetcToArray(invocar.monitoreo_general.llamadas_hora), 1506.7);
        this.mainChartData[3].data = this.costos(this.objetcToArray(invocar.monitoreo_general.turnos_hora), 300);
        this.mainChartData[4].data = this.costos(this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones), 25);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
        //console.log( this.mainChartData[1].data);
      }
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  getCalendarHora(valor) {
    this.getHora = valor;
    this.calendar = true;
    this.calendarSemana = false;
    this.calendarDia = false;
    this.calendarMes = false;

  }

  changeHora() {
    let finicio = this.dateForm.value.date.begin;
    let startDate = moment(finicio).format('YYYY-MM-DD 00:00');

    let ffin = this.dateForm.value.date.end;
    let endDate = moment(ffin).format('YYYY-MM-DD 23:59');

    //console.log('horasi:', startDate);
    //console.log('horasf:', endDate);

    //this.getDataMonitoreoLucyFaltaEnergiaHora(this.dateForm.value.date.begin, this.dateForm.value.date.end);
    this.getDataMonitoreoLucyFaltaEnergiaHora(startDate, endDate);

    this.dateForm = new FormGroup({
      date: new FormControl()
    });

  }

  tablaHora() {
    this.flag = 'hora';
    this.mes = false;
    this.semana = false;
    this.dia = false;
    this.hora = true;
    this.calendarSemana = false;
    this.calendarDia = false;
    this.calendarMes = false;
  }

  //confiduracion para las semanas
  async getDataMonitoreoLucyFaltaEnergiaSemana(startDate, endDate) {
    this.ServiceProvider.preloaderOn();
    try {

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_GENERAL, { startDate, endDate });
      this.mainChartLabels = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      if (this.getSemana == 'cantidad') {
        this.mainChartData[0].data = this.objetcToArray(invocar.monitoreo_general.consultas_lucy_dia);
        this.mainChartData[1].data = this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_dia);
        this.mainChartData[2].data = this.objetcToArray(invocar.monitoreo_general.llamadas_dia);
        this.mainChartData[3].data = this.objetcToArray(invocar.monitoreo_general.turnos_dia);
        this.mainChartData[4].data = this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
        //console.log(this.mainChartData[0].data);

      } else if (this.getSemana == 'costos') {
        this.mainChartData[0].data = this.costos(this.objetcToArray(invocar.monitoreo_general.consultas_lucy_dia), 800);
        this.mainChartData[1].data = this.costos(this.objetcToArray(invocar.monitoreo_general.mensajes_difusion_dia), 14);
        this.mainChartData[2].data = this.costos(this.objetcToArray(invocar.monitoreo_general.llamadas_dia), 1506.7);
        this.mainChartData[3].data = this.costos(this.objetcToArray(invocar.monitoreo_general.turnos_dia), 300);
        this.mainChartData[4].data = this.costos(this.objetcToArray(invocar.monitoreo_general.avisos_suspensiones), 25);
        let valorMaximo = this.calcularMedida(this.mainChartData[0].data, this.mainChartData[1].data, this.mainChartData[2].data, this.mainChartData[3].data, this.mainChartData[4].data);
        this.mainChartOptions = this.cargarGrafico(valorMaximo);
        //console.log(valorMaximo);
      }
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  tablaSemana() {
    this.flag = 'semana';
    this.mes = false;
    this.semana = true;
    this.dia = false;
    this.hora = false;
    this.calendar = false;
    this.calendarDia = false;
    this.calendarMes = false;
  }

  changeSemana() {
    let startDate = this.dateForm.value.date.begin;
    let prueba: any = moment(startDate);
    this.getDataMonitoreoLucyFaltaEnergiaSemana(this.dateForm.value.date.begin, this.dateForm.value.date.end);
    this.dateForm = new FormGroup({
      date: new FormControl()
    });
  }


  getCalendarSemana(valor) {
    this.getSemana = valor;
    this.calendarSemana = true;
    this.calendar = false;
    this.calendarDia = false;
    this.calendarMes = false;

  }


  //para obtener los valores del objeto
  objetcToArray(datosPlataforma: object) {
    let datos = [];
    for (let mes in datosPlataforma) {
      if (datosPlataforma[mes].cantidad != undefined) {
        datos.push(datosPlataforma[mes].cantidad);
      } else {
        datos.push(datosPlataforma[mes]);
      }
    }
    return datos;
  }

  //para obtener los indices del objeto
  objetcToArray2(datosPlataforma) {
    let dias = [];
    for (let i = 0; i <= datosPlataforma.length; i++) {
      //console.log('indices', i);
      dias.push(i.toString());
    }
    return dias;
  }

  //calcular los costos
  OrdenarPorClaveCostos(arr, precio) {
    var keys = Object.keys(arr),
      i, len = keys.length,
      k, response = new Array();

    keys.sort();

    for (i = 0; i < len; i++) {
      k = keys[i];
      response.push((arr[k] * precio).toFixed(1));
    }
    return response;
  }

  //calcular los costos
  costos(arr, precio) {
    let precios = [];
    for (let value in arr) {
      precios.push(parseInt(arr[value]) * precio);
    }

    return precios;
  }



  getMeses(datosPlataforma: object) {
    let meses = [];
    for (let mes in datosPlataforma) {
      switch (datosPlataforma[mes]._id) {
        case "01":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "02":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "03":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "04":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "05":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "06":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "07":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "08":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "09":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "10":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "11":
          meses.push(datosPlataforma[mes]._id);
          break;
        case "12":
          meses.push(datosPlataforma[mes]._id);
          break;
      }

    }

    return meses;
  }




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
      }
    };
  }

  public mainChartElements = 31;
  public mainChartData: Array<any> = [
    {
      data: [],
      label: 'Consultas Lucy'
    },
    {
      data: [],
      label: 'DINP'
    },
    {
      data: [],
      label: 'Llamadas'
    },
    {
      data: [],
      label: 'Turnos'
    },
    {
      data: [],
      label: 'Avisos interr. programadas'
    }
  ];
  /* tslint:disable:max-line-length */
  public mainChartLabels: Array<any> = [];
  /* tslint:enable:max-line-length */
  public mainChartOptions: any = {};
  public mainChartColours: Array<ChartDataSets> = [
    {
      backgroundColor: 'rgba(0, 128, 0, 0.100)',
      borderColor: 'rgba(0, 128, 0, 0.400)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(0, 0, 255, 0.100)',
      borderColor: 'rgba(0, 0, 255, 0.400)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(0, 165, 151, 0.100)',
      borderColor: 'rgba(0, 165, 151, 0.400)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(170, 0, 0, 0.100)',
      borderColor: 'rgba(170, 0, 0, 0.400)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    },
    {
      backgroundColor: 'rgba(255, 166, 0, 0.100)',
      borderColor: 'rgba(255, 166, 0, 0.400)',
      pointHoverBackgroundColor: '#fff',
      fill: false
    }
  ];
  public mainChartType = 'line';

  //descargar imagenes
  general(id) {
    general(id);
  }


  //calendario

  chosenYearHandler(normalizedYear: moment.Moment, datepicker: MatDatepicker<moment.Moment>) {
    const ctrlValue = this.date.value;
    ctrlValue.year(normalizedYear.year());
    this.date.setValue(ctrlValue);
    //console.log('año seleccionado',ctrlValue._d);
    datepicker.close();

    return ctrlValue._d;
  }


  chosenYearHandler2(normalizedYear: moment.Moment) {
    const ctrlValue = this.date.value;
    ctrlValue.year(normalizedYear.year());
    this.date.setValue(ctrlValue);
    this.anioDia = ctrlValue._d;

  }

  //k
  chosenMonthHandler(normalizedMonth: moment.Moment, datepicker: MatDatepicker<moment.Moment>) {
    const ctrlValue = this.date.value;
    ctrlValue.month(normalizedMonth.month());
    this.date.setValue(ctrlValue);
    this.mesDia = ctrlValue._d;
    datepicker.close();
    this.changeDia();
  }

}
