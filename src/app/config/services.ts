import { FormGroup, FormControl, FormArray, AbstractControl } from "@angular/forms";
import { Injectable } from "@angular/core";
import { HttpClient, HttpHeaders, HttpRequest, HttpErrorResponse } from "@angular/common/http";
import { URL, WEBSERVICE } from "./webservices";
import { NgbModal } from "@ng-bootstrap/ng-bootstrap";
import { ModalComponent } from '../views/modal/modal.component';
import { Observable, throwError } from 'rxjs';
import { catchError, retry, finalize } from 'rxjs/operators';
import { MESSAGES } from './messages';
import { ToastService } from '../config/toast-service';
import * as moment from "moment";
import "moment/locale/es";
import "moment-timezone";
import { MatTableDataSource } from '@angular/material/table';
import { CuotaMensajesUsuario } from './interfaces';
import { Title } from '@angular/platform-browser';

@Injectable()
export class ServiceProvider {
  private url: string = URL;
  private isSuperPreload: boolean = false;
  // public readonly valorMensajeIndividual: number = 6;
  public formatoFecha: string = "YYYY-MM-DD HH:mm:ss";

  constructor(
    private modalService: NgbModal,
    public toastService: ToastService,
    private http: HttpClient,
    private tabTitleService: Title
  ) {
    moment.tz.setDefault("America/Bogota");
  }

  /** Define el título de la pestaña del navegador */
  setTituloPestana(tituloPestana: string) {
    this.tabTitleService.setTitle(`${tituloPestana} | SGCB`);
  }

  mentionMenu: Element;
  matContainer: ElementCSSInlineStyle;
  /** Función que se activa cuando se abre el panel de marcadores en el `textAreaMensaje`. Permite mostrar adecuadamente la lista de marcadores disponibles al agregarle `overflow: visible` a la clase `mat-horizontal-content-container`. */
  public showContainerOverflow() {
    setTimeout(() => {
      this.mentionMenu = document.querySelector(".mention-menu");
      this.matContainer = this.mentionMenu.closest(".mat-horizontal-content-container") as unknown as ElementCSSInlineStyle;
      this.matContainer.style.overflow = "visible";
    });
  }

  /** Función que se activa cuando se cierra el panel de marcadores en el `textAreaMensaje`. Se utiliza para restablecer la propiedad `overflow` a `hidden` en la clase `mat-horizontal-content-container`, con el fin de que la animación al movernos en los steps se vea limpia. */
  public hideContainerOverflow() {
    if (this.matContainer) {
      this.matContainer.style.overflow = "hidden";
    }
  }

  public validateAllFormFields(formGroup: FormGroup) {
    Object.keys(formGroup.controls).forEach(field => {
      const control = formGroup.get(field);
      if (control instanceof FormControl) {
        control.markAsTouched({ onlySelf: true });
      } else if (control instanceof FormGroup) {
        this.validateAllFormFields(control);
      } else if (control instanceof FormArray) {
        /* Validar los formGroups del FormArray */
        const formArrayControls = control.controls;
        formArrayControls.forEach(formArrayControl => {
          if (formArrayControl instanceof FormGroup) {
            this.validateAllFormFields(formArrayControl);
          }
        });
      }
    });
  }

  public validateStep(formGroup: FormGroup) {
    // console.log(formGroup);
    if (formGroup.valid) {
      return true;
    }
    this.validateAllFormFields(formGroup);
    return false;
  }

  /** @removeEspaciosInicioCadena Función que valida que los campos de texto (input, textarea) no inicien con espacio.
   *
   * @param control Es el control del formGroup sobre el que se aplica este validador.
   */
  public removeEspaciosInicioCadena(control: AbstractControl) {
    if (control && control.value && !control.value.trim().length) {
      control.setValue("");
      return { required: true };
    }
    return null;
  }

  public removeCaracteresEspeciales(palabra: string) {
    /* |\_ - Se sacó para probar clase_servicio */
    return palabra?.replace(/\~|\°|\¬|\`|\´|\!|\¡|\¿|\@|\#|\$|\%|\^|\&|\*|\(|\)|\{|\}|\[|\]|\;|\:|\"|\'|\<|\,|\.|\>|\?|\/|\\|\||\-|\-|\+|\=/g, "");
  }

  public removeAcentos(palabra: string) {
    return palabra?.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  public openModal(content: any, tamano?: string) {
    if (!tamano) {
      tamano = "md";
    }
    return this.modalService.open(content, {
      windowClass: "modal_opening_animation",
      size: tamano,
      centered: true,
      backdrop: "static",
      keyboard: false
    });
  }

  public openPopup(titulo: string, cuerpo: string, funcion?: any, scope?: any, param?: any) {
    const modalRef = this.modalService.open(ModalComponent, {
      backdrop: "static"
    });

    modalRef.componentInstance.component = scope;
    modalRef.componentInstance.funcion = funcion;
    modalRef.componentInstance.param = param;

    if (titulo.toLowerCase() == "exito" || titulo.toLowerCase() == "éxito") {
      modalRef.componentInstance.estilo = "success";
      modalRef.componentInstance.titulo = "Éxito";
    } else if (titulo.toLowerCase() == "error") {
      modalRef.componentInstance.estilo = "danger";
      modalRef.componentInstance.titulo = "Error";
    } else {
      modalRef.componentInstance.estilo = "warning";
      modalRef.componentInstance.titulo = "Advertencia";
    }
    modalRef.componentInstance.cuerpo = cuerpo;
    modalRef.componentInstance.funcion = funcion;
    modalRef.componentInstance.param = param;
  }

  public openToast(tipo: string, mensaje: string, duracion?: number) {
    let icon = "";
    let className = "";

    if (tipo.toLowerCase() === "success") {
      icon = "fa-check-circle";
      className = "bg-success";
    } else if (tipo.toLowerCase() === "error") {
      icon = "fa-exclamation-circle";
      className = "bg-danger";
    }

    duracion = duracion ? duracion : 10000;

    this.toastService.show(mensaje, {
      classname: className,
      delay: duracion,
      icon
    });
  }

  public preloaderOn(isSuperPreload?: boolean) {
    if (isSuperPreload) {
      this.isSuperPreload = isSuperPreload;
    }

    if (document.querySelector("#preloader")) {
      document.querySelector("#preloader")!.classList.remove("d-none");
      document.querySelector("#preloader")!.classList.add("d-block");
    } else {
      const d1 = document.querySelector("body");
      d1!.insertAdjacentHTML("beforeend", `
        <div id="preloader" class="position-fixed" style="top:0px; z-index:2000;">
          <div class="position-fixed backdrop_preload w-100 h_100vh d-flex justify-content-center align-items-center">
            <div  class="avatar sombra pulse">
              <img class="logo_circulo" src="./assets/img/ch_logo_pequeno.png">
            </div>
            <div class="text-white texto_cargando">Cargando...</div>
          </div>
        </div>`
      );
    }
  }

  public preloaderOff(isSuperPreload?: boolean) {
    if (isSuperPreload) {
      this.isSuperPreload = false;
    }
    if (!this.isSuperPreload) {
      if (document.querySelector("#preloader")) {
        document.querySelector("#preloader")!.classList.remove("d-block");
        document.querySelector("#preloader")!.classList.add("d-none");
      }
    }
  }

  /** Muestra errores procedentes del cliente o del servidor */
  public handleError(error: HttpErrorResponse) {
    if (error.error instanceof ErrorEvent) {
      /* Se muestran errores del lado del cliente o errores de red. */
      console.error("Ocurrió un error:", error.error.message);
    } else {
      /* Desde el backend se retorna código y cuerpo del error */
      console.error("Código retornado por el backend:", error.status);
      console.error("Cuerpo del error:", error.error);
    }
    /* Retorna un observable con un error genérico para mostrar */
    return throwError(MESSAGES.errorGenerico);
  };

  public get(inUrl: string, params?: any) {
    const httpOptions = {
      params,
      headers: new HttpHeaders({
        "Content-Type": "application/json",
      }),
      withCredentials: true,
    };

    return this.http.get(this.url + inUrl, httpOptions)
      .pipe(
        catchError(this.handleError)
      ).toPromise();
  }

  public getPrueba(inUrl: string, params?: any): Promise<any> {
    //const formedUrl = this.filterAccents(inUrl.split(' ').join('-'));
    return new Promise((resolve, reject) => {
      const headers = new HttpHeaders().append('Content-Type', 'application/json');
      const options = {
        headers: headers,
        params: params
      };
      this.http.get(/* this.url + inUrl */ inUrl, options).subscribe(
        response => {
          try {
            // response = response.json();
            resolve(response);
          } catch (error) {
            // console.log("[api-274]", response);
            reject(response);
          }
        },
        fail => {
          try {
            fail = fail.json();
          } catch (error) {
            // console.log("[api-162]", fail);
          }
          reject(fail);
        }
      );
    });
  }

  //is_file determina si el formulario envia un archivo
  public post(inUrl: string, params?: object, is_file?: boolean, token?: string) {
    const httpOptions = {
      headers: new HttpHeaders({})
    };

    if (!is_file) {
      httpOptions.headers.append("Content-Type", "application/json");
    }

    if (token) {
      httpOptions.headers.append("token", token);
    }

    return this.http.post(this.url + inUrl, params, httpOptions)
      .pipe(
        catchError(this.handleError)
      ).toPromise();
  }

  //is_file determina si el formulario envia un archivo
  public postPrueba(
    inUrl: string,
    query?: any,
    is_file?: boolean,
    token?: any
  ): Promise<any> {
    return new Promise((resolve, reject) => {
      let headers: any;
      if (!is_file) {
        headers = new HttpHeaders().append('Content-Type', 'application/json');
      } else {
        headers = new HttpHeaders({});
      }

      if (token) {
        headers.append('token', token);
      }

      this.http.post(this.url + inUrl, query, { headers }).subscribe(
        response => {
          try {
            // response = response.json();
            resolve(response);
          } catch (error) {
            console.log("[api-274]", response);
            reject(response);
          }
        },
        fail => {
          try {
            fail = fail.json();
          } catch (error) {
            console.log("[api-108]", fail);
          }
          reject(fail);
        }
      );
    });
  }

  public comparePasswords(formGroup: FormGroup) {
    const password = formGroup.controls["password"].value;
    const confirmarPassword = formGroup.controls["confirmarPassword"].value;

    // Retornar false si los campos Password o Confirmar Password están vacíos, o si esos dos campos con iguales
    if ((!password || !confirmarPassword) || (password === confirmarPassword)) {
      return false;
    }
    // Retornar objeto noCoinciden para mostrar mensaje en el template
    return { noCoinciden: true };
  }

  /** Inserta una nueva cuota de mensajes de usuario, si la fecha en que se va a realizar un envío de mensajes (`fechaHoy`), es posterior a la fecha en que termina la cuota de mensajes de usuario (`cuotaMensajesUsuario.fechaFinMensajesUsuario`)
   * @param idUsuario Identificador del usuario que actualizará la cuota de usuarios
   */
  public async manageCuotaMensajesUsuario(idUsuario: string) {
    try {
      const cuotaMensajesUsuario = <CuotaMensajesUsuario>await this.post(WEBSERVICE.GET_CUOTA_MENSAJES_USUARIO, { idUsuario });

      const fechaHoy = moment().format(this.formatoFecha);
      if (moment(fechaHoy).isAfter(cuotaMensajesUsuario.fechaFinMensajesUsuario)) {
        const periodoMensajesUsuario = cuotaMensajesUsuario.selectPeriodoMensajesUsuario;

        const datosCuotaMensajes = {
          idUsuario,
          nombreUsuario: cuotaMensajesUsuario.nombreUsuario,
          cuotaMensajes: {
            selectBolsa: cuotaMensajesUsuario.selectBolsa,
            selectPeriodoMensajesUsuario: periodoMensajesUsuario,
            cantidadMensajesUsuario: cuotaMensajesUsuario.totalInicialMensajes,
            fechaInicioMensajesUsuario: fechaHoy,
            fechaFinMensajesUsuario: moment().endOf(periodoMensajesUsuario as moment.unitOfTime.StartOf).format(this.formatoFecha),
          }
        };
        await this.post(WEBSERVICE.INSERT_CUOTA_MENSAJES_USUARIO, datosCuotaMensajes);
      }
    } catch (error) {
      this.openPopup("error", error);
    }
  }

  /** Inserta una nueva cuota de mensajes de clientes, si la fecha en que se va a realizar un envío de mensajes (`fechaHoy`), es posterior a la fecha en que termina la cuota de mensajes de clientes (`cuotaMensajesClientes.fechaFinMensajesCliente`)
   * @param idUsuario Identificador del usuario que actualizará la cuota de clientes
   */
  public async manageCuotaMensajesClientes(idUsuario: string) {
    try {
      const cuotaMensajesClientes: any = await this.post(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE, { idUsuario });

      const fechaHoy = moment().format(this.formatoFecha);
      if (moment(fechaHoy).isAfter(cuotaMensajesClientes.fechaFinMensajesCliente)) {
        const periodoMensajesCliente = cuotaMensajesClientes.periodoMensajesCliente;

        const datosCuotaMensajesClientes = {
          idUsuario,
          nombreUsuario: cuotaMensajesClientes.nombreUsuario,
          fechaRegistro: fechaHoy,
          cuotaMensajes: {
            periodoMensajesCliente,
            cantidadMensajesCliente: cuotaMensajesClientes.cantidadMensajesCliente,
            fechaInicioMensajesCliente: moment().startOf(periodoMensajesCliente).format(this.formatoFecha),
            fechaFinMensajesCliente: moment().endOf(periodoMensajesCliente).format(this.formatoFecha)
          }
        };
        await this.post(WEBSERVICE.INSERT_CUOTA_MENSAJES_CLIENTE, datosCuotaMensajesClientes);
      }
    } catch (error) {
      this.openPopup("error", error);
    }
  }

  /** Filtra un array de objetos, retornándolo solo con las `llavesFiltro`
   * @param arrayFiltrar Array a filtrar
   * @param llavesFiltro Array de strings con las llaves a retornar
   */
  public filtrarArray(arrayFiltrar: object[], llavesFiltro: string[]) {
    return arrayFiltrar.map(datoArray => {
      return Object.fromEntries(
        Object.entries(datoArray).filter(([llave]) => llavesFiltro.includes(llave))
      );
    });
  }

  public aplicarFiltroTabla($event: Event, dataSource: MatTableDataSource<any>) {
    const valorFiltro = ($event.target as HTMLInputElement).value;
    dataSource.filter = valorFiltro.trim().toLowerCase();
  }

  /** Convierte un número, agregándole puntos de mil: 1430507 => 1.430.507 */
  public formatNumero(numero: number) {
    return new Intl.NumberFormat("es-CO").format(numero);
  }

  public arrayTieneDatos(arrayValores: number[]) {
    return arrayValores.some(Boolean);
  }

  /** Compara los datos de una tabla para mostralos ordenados de forma ascendente o descendente */
  public compararDatosTabla(a: number | string, b: number | string, isAsc: boolean) {
    return (a < b ? -1 : 1) * (isAsc ? 1 : -1);
  }
}
