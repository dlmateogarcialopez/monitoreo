import * as XLSX from "xlsx";

export class ExcelUtil {
  /** Exporta los datos a una hoja de Excel
   * @param datos Array de objetos que contiene los datos
   * @param nombre Nombre del archivo a exportar
   */
  static exportToExcel(datos: object[], nombre: string) {
    const fecha = new Date().toLocaleString();
    const nombreArchivo = `${nombre}-${fecha}`;
    const libro = XLSX.utils.book_new();
    const hoja = XLSX.utils.json_to_sheet(datos);
    /* Los nombres de las hojas de Excel pueden tener una longitud máxima de 31 caracteres */
    XLSX.utils.book_append_sheet(libro, hoja, nombre.substr(0, 31));
    XLSX.writeFile(libro, `${nombreArchivo}.xlsx`);
  }
}