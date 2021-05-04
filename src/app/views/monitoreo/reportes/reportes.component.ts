import { Component, OnInit } from '@angular/core';
import { FormGroup } from '@angular/forms';
import * as moment from 'moment';
import { ServiceProvider } from '../../../config/services';
import { WEBSERVICE } from '../../../config/webservices';
import * as FileSaver from 'file-saver';
import * as XLSX from 'xlsx';

const EXCEL_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';
const EXCEL_EXTENSION = '.xlsx';
const month = moment(new Date()).format("M").toString();
@Component({
  selector: 'app-reportes',
  templateUrl: './reportes.component.html',
})
export class ReportesComponent implements OnInit {

  public criterioSeleccionado: FormGroup;
  public mes: any = '0'+ month;
  public anio: any = '2021';
  public anios = ['2020', '2019', '2021'];
  public meses = [{ mes: 'Enero', valor: '01' }, { mes: 'Febrero', valor: '02' }, { mes: 'Marzo', valor: '03' }, { mes: 'Abril', valor: '04' }, { mes: 'Mayo', valor: '05' }, { mes: 'Junio', valor: '06' }, { mes: 'Julio', valor: '07' }, { mes: 'Agosto', valor: '08' }, { mes: 'Septiembre', valor: '09' }, { mes: 'Octubre', valor: '10' }, { mes: 'Noviembre', valor: '11' }, { mes: 'Diciembre', valor: '12' }];
  public data: any = [
    {

      "CATEGORYID": 1,
      "CATEGORYNAME": "BOOKS",
      "DESCRIPTION": "It contains all types of books",
      "IMAGE": "Books",
      "STATUS": "TRUE"
    },
    {

      "CATEGORYID": 2,
      "CATEGORYNAME": "EBOOKS",
      "DESCRIPTION": "It contains all types of ebooks",
      "IMAGE": "Ebooks",
      "STATUS": "TRUE"
    },
    {

      "CATEGORYID": 3,
      "CATEGORYNAME": "Bookss",
      "DESCRIPTION": "DESCRIPTION",
      "IMAGE": "IMAGE",
      "STATUS": "TRUE"
    }
  ];
  constructor(private ServiceProvider: ServiceProvider) { }

  ngOnInit(): void {
    console.log(typeof  moment(new Date()).format("M").toString());
    this.ServiceProvider.setTituloPestana("Reportes");
    // console.log(this.mes, this.anio);
  }

  fechaSeleccionada() {
    //this.año = this.criterioSeleccionado.cri;
    //this.mes = this.criterioSeleccionado.valorCri;

  }

  async obtenerReporte(tipo) {
    //console.log("/assets/archivosDescargar/" + tipo + ".xlsx");
    await this.getDataMonitoreoReportes(this.mes, this.anio, tipo);

    //await this.descargarDocumento(tipo);
  }

  async descargarDocumento(tipo) {
    let link = document.createElement("a");
    link.download = tipo;
    //console.log("/assets/archivosDescargar/" + tipo + ".xlsx");
    link.href = "/assets/archivosDescargar/" + tipo + ".xlsx";
    link.click();
  }

  //traer datos de las consultas de monitoreo lucy
  async getDataMonitoreoReportes(mes, anio, tipo) {
    this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        mes: mes,
        anio: anio,
        tipo: tipo
      };

      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_REPORTES, parameters);
      await this.downloadXlsx(invocar, tipo);
      //await this.exportAsExcelFile(invocar.reportes.calificacionesLucy, `${tipo}`);

    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  downloadXlsx(invocar, tipo) {
    switch (tipo) {
      case 'calificacionesLucy':
        this.exportAsExcelFile(invocar.reportes.calificacionesLucy, `${tipo}`);
        break;
      case 'usuariosFrecuentesLucy':
        this.exportAsExcelFile(invocar.reportes.usuariosFrecuentesLucy, `${tipo}`);

        break;
      case 'usuariosSegmentoLucy':
        this.exportAsExcelFile(invocar.reportes.usuariosSegmentoLucy, `${tipo}`);

        break;
      case 'usuariosTtotalesLucy':
        this.exportAsExcelFile(invocar.reportes.usuariosTtotalesLucy, `${tipo}`);

        break;
      case 'usuariosInscritosDinp':
        this.exportAsExcelFile(invocar.reportes.usuariosInscritosDinp, `${tipo}`);

        break;
      case 'usuariosRecibidosMsmDinp':
        this.exportAsExcelFile(invocar.reportes.usuariosRecibidosMsmDinp, `${tipo}`);

        break;
      case 'usuariosRecibidosMsmDinpSegmento':
        console.log(invocar.reportes.usuariosRecibidosMsmDinpSegmento);
        this.exportAsExcelFile(invocar.reportes.usuariosRecibidosMsmDinpSegmento, `${tipo}`);

        break;
      case 'usuariosRecibidosMsmDinpPromocion':
        this.exportAsExcelFile(invocar.reportes.usuariosRecibidosMsmDinpPromocion, `${tipo}`);
        break;
    }
  }

  /*exportAsXLSX(): void {
    this.exportAsExcelFile(this.data, 'myExcelFile');
  }*/

  public exportAsExcelFile(json: any[], excelFileName: string): void {
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(json);
    const workbook: XLSX.WorkBook = { Sheets: { 'data': worksheet }, SheetNames: ['data'] };
    const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
    this.saveAsExcelFile(excelBuffer, excelFileName);
  }
  private saveAsExcelFile(buffer: any, fileName: string): void {
    const data: Blob = new Blob([buffer], { type: EXCEL_TYPE });
    FileSaver.saveAs(data, fileName + '_export_' + new Date().getTime() + EXCEL_EXTENSION);
  }
}
