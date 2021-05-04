import { Component, OnInit, Input, ViewChild, forwardRef, HostBinding, SecurityContext, HostListener } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray, FormControl, NG_VALUE_ACCESSOR, ControlValueAccessor, AbstractControl } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../../config/services";
import { MESSAGES } from "../../../config/messages";
import { WEBSERVICE } from "../../../config/webservices";
import { Router } from "@angular/router";
import { AuthenticationService } from "../../../config/authentication.service";
import { DragdropService } from "../../../config/dragdrop.service";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort, MatSortable } from "@angular/material/sort";
import { MatTableDataSource } from "@angular/material/table";
import { ModalDirective } from 'ngx-bootstrap/modal';
import { SelectionModel } from '@angular/cdk/collections';
import * as XLSX from 'xlsx';
import { InterfazAgregarCampos, Mensaje, DatosMensajeDod, Users, CuotaMensajesUsuario, InfoMensajes, BolsaMensajes } from '../../../config/interfaces';
import { MatStepper } from '@angular/material/stepper';
import { BypassHtmlPipe } from '../../../config/pipes/bypassHtml.pipe';
import { MatRadioChange } from '@angular/material/radio';
import { animate, state, style, transition, trigger } from '@angular/animations';
import { JwtHelperService } from '@auth0/angular-jwt';
import * as moment from "moment";
import "moment/locale/es";
import "moment-timezone";
import { MatDialog } from '@angular/material/dialog';
import { DialogBoxComponent } from '../dialogBox/dialogBox.component';
import { ModalMensajesEnviadosComponent } from '../modalMensajesEnviados/modalMensajesEnviados.component';

@Component({
  selector: "enviar-desde-archivo",
  templateUrl: "enviarDesdeArchivo.component.html",
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({ height: '0px', minHeight: '0' })),
      state('expanded', style({ height: '*' })),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ],

})


export class EnviarDesdeArchivoComponent implements OnInit {
  isLinear = true;
  formCargarArchivo: FormGroup;
  formTipoMensaje: FormGroup;
  formMensaje: FormGroup;
  formArrayNuevosCampos: FormArray;
  formConfirmacion: FormGroup;
  fileArr: any[] = [];
  imgArr: any[] = [];
  fileObj: any[] = [];
  msg: string;
  progress: number = 0;
  encabezadosArchivoCargado: any = [];
  tiposMensaje: string[] = ["general", "individual", "precargado"];
  /** Cantidad máxima de caracteres de los SMS = 160 */
  maxCaracteresMensaje: number = 160;
  @ViewChild('modalCelularesExcluidos') modalCelularesExcluidos: ModalDirective;
  @ViewChild('modalMensajesEnviados') modalMensajesEnviados: ModalMensajesEnviadosComponent;
  columnsStepConfirmacion: string[] = ["celular", "mensaje", "cantidadCaracteres", "cantidadMensajesPorUsuario", "accion"];
  dataSourceStepConfirmacion: MatTableDataSource<any>;
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;
  totalDefinitivoMensajes: number = 0;
  valorMensajeIndividual: number = 0;
  valorMensajesAEnviar: number = 0;
  MESSAGES: object = MESSAGES;
  datosUsuarioJWT: Users = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data;
  idUsuario: string = this.datosUsuarioJWT._id.$oid;
  /** Define si el usuario puede realizar envíos a clientes que hayan superado la cuota de mensajes recibidos por día  */
  usuarioHasEnvioPrioritario: boolean = this.datosUsuarioJWT.permisos.dodEnviarSms && this.datosUsuarioJWT.permisos.dodPrioridadEnvio;
  columnsAgregarCampos: string[] = ['posicion', 'columnaCheckbox'];
  dataSourceAgregarCampos: any = [];
  selection = new SelectionModel<InterfazAgregarCampos>(true, []);

  /* The label for the checkbox on the passed row */
  /* checkboxLabel(row?: InterfazAgregarCampos): string {
    return `${this.selection.isSelected(row) ? 'deselect' : 'select'} row ${row.posicion + 1}`;
  } */


  arrayMarcadores: string[] = ["celular"];

  marcadoresConfig: object = {
    mentionSelect: this.insertSpanText,
    dropUp: true,
  };


  constructor(
    private authenticationService: AuthenticationService,
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    public dragdropService: DragdropService,
    public jwtHelper: JwtHelperService,
    public dialog: MatDialog
  ) { }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.ServiceProvider.setTituloPestana("Envío desde archivo");
    this.setFormCargarArchivoControls();
    this.setFormTipoMensajeControls();
    this.setFormMensajeControls();
    this.setFormConfirmacionControls();

    Promise.all([
      this.ServiceProvider.manageCuotaMensajesUsuario(this.idUsuario),
      this.ServiceProvider.manageCuotaMensajesClientes(this.idUsuario),
    ])
      .then(() => this.ServiceProvider.preloaderOff());
  }

  setFormCargarArchivoControls() {
    this.formCargarArchivo = this.fb.group({
      radioEncabezados: ["", Validators.required],
      archivo: ["", Validators.required],
    });
  }

  setFormTipoMensajeControls() {
    this.formTipoMensaje = this.fb.group({
      radioTipoMensaje: ["", Validators.required],
      textAreaMotivo: ["", [Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]],
      envioPrioritario: [this.usuarioHasEnvioPrioritario]
    });
  }

  setFormMensajeControls() {
    this.formMensaje = this.fb.group({
      selectCelular: ["", Validators.required],
      textAreaMensaje: ["", [Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]],
      selectMensajePrecargado: ["", Validators.required],
      formArrayNuevosCampos: this.fb.array([])
    });
  }

  setFormConfirmacionControls() {
    this.formConfirmacion = this.fb.group({
      radioMensajesSuperiores: ["", Validators.required],
    });
  }

  get formCargarArchivoFields() {
    return this.formCargarArchivo.controls;
  }

  get formTipoMensajeFields() {
    return this.formTipoMensaje.controls;
  }

  get formMensajeFields() {
    return this.formMensaje.controls;
  }

  get formConfirmacionFields() {
    return this.formConfirmacion.controls;
  }

  /**
   *
   * @param nombreCampo
   */
  insertSpanText(nombreCampo: any) {
    return `@${nombreCampo.label} `;
  }

  /** @addCampoArrayMarcadores Función que agrega en `this.arrayMarcadores` los nombres de los campos agregados dinámicamente por el usuario, permitiéndole referenciarlos en el textarea de Mensaje individual escribiendo el caracter `@`
   *
   * @param nombreCampo Nombre del campo que se va a agregar
   * @param index Posición del `this.arrayMarcadores` en el que se agregará el nombre del campo. Este parámetro se toma del [formGroupName]="i" del template.
   * `nombreCampo` se agrega en la posición `index + 1`, ya que `this.arrayMarcadores` tiene un elemento inicial que es `"celular"`.
   *
   * Luego de agregar el campo a `this.arrayMarcadores`, este debe reiniciarse para que los nuevos campos puedan mostrarse al escribir `@`.
   */
  addCampoArrayMarcadores(nombreCampo: string, index: number) {
    this.arrayMarcadores[index + 1] = nombreCampo;
    this.arrayMarcadores = [...this.arrayMarcadores];
    // // console.log(this.arrayMarcadores)
  }

  /** @removeCampoArrayMarcadores Función que elimina de `this.arrayMarcadores` los nombres de los campos dinámicos eliminados por el usuario.
  *
  * @param index Posición del `this.arrayMarcadores` en el que se eliminará el nombre del campo. Este parámetro se toma del [formGroupName]="i" del template.
  * El nombre del campo se elimina en la posición `index + 1`, ya que `this.arrayMarcadores` tiene un elemento inicial que es `"celular"`.
  *
  * Luego de eliminar el campo de `this.arrayMarcadores`, este debe reiniciarse para que los campos restantes puedan mostrarse al escribir `@`.
  */
  removeCampoArrayMarcadores(index: number) {
    this.arrayMarcadores.splice(index + 1, 1);
    this.arrayMarcadores = [...this.arrayMarcadores];
  }

  addNuevoCampoFormGroup() {
    return this.fb.group({
      inputCampoAgregado: ["", [Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]],
      selectCampoAgregado: ["", Validators.required],
      inputCampoAgregadoMarcador: [""],
    });
  }

  showInputFile() {
    this.progress = 0;
    // this.formCargarArchivoFields.archivo.setValue("");
    // this.formCargarArchivo.setValue({ archivo: "", radioEncabezados: "" });
    this.formCargarArchivo.reset();
    this.formTipoMensaje.reset();
    this.formMensaje.reset();
    this.formConfirmacion.reset();
  }

  resetInputFile() {
    this.progress = 0;
    this.formCargarArchivoFields.archivo.setValue("");
    this.formMensaje.reset();
  }

  validateStep(formGroup: FormGroup) {
    return this.ServiceProvider.validateStep(formGroup);
  }





  /** @validateControlsStepMensaje Realiza validación del formulario del paso "Mensaje" para actualizar los campos requeridos dependiendo del tipo de mensaje seleccionado en el paso "Tipo de mensaje".
   *
   * @param tipoMensajeSeleccionado Es el tipo de mensaje seleccionado en los botones de radio del paso "Tipo de mensaje".
   *
   * Los valores son "general", "individual" y "precargado".
   */
  validateControlsStepMensaje(tipoMensajeSeleccionado: string) {
    // // console.log(tipoMensajeSeleccionado)
    // // console.log(this.formArrayNuevosCampos)
    /* Si se selecciona mensaje precargado */
    if (tipoMensajeSeleccionado === this.tiposMensaje[2]) {
      this.formMensajeFields.textAreaMensaje.clearValidators();
      this.formMensajeFields.selectMensajePrecargado.setValidators([Validators.required]);

      /* Se quitan las validaciones de los campos dinámicos de mensaje individual */
      this.formArrayNuevosCampos?.controls.forEach(campo => {
        const formGroupMensajeIndividual = campo as FormGroup;
        formGroupMensajeIndividual.controls.inputCampoAgregado.clearValidators();
        formGroupMensajeIndividual.controls.inputCampoAgregado.updateValueAndValidity();
        formGroupMensajeIndividual.controls.selectCampoAgregado.clearValidators();
        formGroupMensajeIndividual.controls.selectCampoAgregado.updateValueAndValidity();
      });
    } else {
      this.formMensajeFields.textAreaMensaje.setValidators([Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]);
      this.formMensajeFields.selectMensajePrecargado.clearValidators();

      /* Si se selecciona mensaje individual, se establecen las validaciones de campos requeridos para los campos dinámicos agregados por el usuario  */
      if (tipoMensajeSeleccionado === this.tiposMensaje[1]) {
        this.formArrayNuevosCampos?.controls.forEach(campo => {
          const formGroupMensajeIndividual = campo as FormGroup;
          formGroupMensajeIndividual.controls.inputCampoAgregado.setValidators([Validators.required]);
          formGroupMensajeIndividual.controls.inputCampoAgregado.updateValueAndValidity();
          formGroupMensajeIndividual.controls.selectCampoAgregado.setValidators([Validators.required]);
          formGroupMensajeIndividual.controls.selectCampoAgregado.updateValueAndValidity();
        });
      }
    }
    this.formMensajeFields.textAreaMensaje.updateValueAndValidity();
    this.formMensajeFields.selectMensajePrecargado.updateValueAndValidity();
  }

  handleFileError(error: string) {
    this.ServiceProvider.openToast("error", error);
    this.ServiceProvider.preloaderOff();
  }

  replaceWhitespace(palabra: string) {
    palabra = this.ServiceProvider.removeCaracteresEspeciales(palabra);
    palabra = this.ServiceProvider.removeAcentos(palabra);
    return palabra?.trim().toLowerCase().replace(/\s+/g, "_");
  }

  acentos: number[] = [8, 32, 46, 192];

  acceptLettersOnly(event: any) {
    // // console.log(event.keyCode)
    return event.keyCode >= 65 && event.keyCode <= 90 || this.acentos.includes(event.keyCode);
  }

  filtro: string = "";

  agregarCampo() {
    this.formArrayNuevosCampos = this.formMensajeFields.formArrayNuevosCampos as FormArray;
    this.formArrayNuevosCampos.push(this.addNuevoCampoFormGroup());

    this.formArrayNuevosCampos.controls.forEach(control => {
      const formGroupMensajeIndividual = control as FormGroup;
      const controlInputCampoAgregado = formGroupMensajeIndividual.controls.inputCampoAgregado;
      const controlInputCampoAgregadoMarcador = formGroupMensajeIndividual.controls.inputCampoAgregadoMarcador;

      controlInputCampoAgregado.valueChanges.subscribe(value =>
        controlInputCampoAgregadoMarcador.setValue(this.replaceWhitespace(value))
      );
    });
    // this.formArrayNuevosCampos.valueChanges.subscribe(value =>
    //   value.forEach((val: any) => document.querySelector(".lab").remove())
    // );
  }

  /** @eliminarCampo Elimina la fila padre (.row) que contiene el botón clickeado.
   *
   * También elimina el formGroup de @formArrayNuevosCampos en la posición @index
   *
   * @element Elemento del DOM que disparó el evento. Se obtiene a través de @param event.target
  */
  eliminarCampo(event: Event, index: number) {
    // const element = event.target as Element;
    // element.closest(".row").remove();
    this.formArrayNuevosCampos.removeAt(index);
  }

  datosArchivoCargado: any[] = [];
  dataArchivo: any[] = [];
  nombreArchivo: string = "";
  isNextButtonEnabled: boolean = false;

  upload(event: any) {
    if (this.formCargarArchivoFields.radioEncabezados.value) {
      event.stopPropagation();
      event.preventDefault();
      this.ServiceProvider.preloaderOn();
      const target: DataTransfer = <DataTransfer>(event.target);
      const files = event.dataTransfer?.files || target.files;
      const file = files[0];
      const reader = new FileReader();

      if (files.length !== 1) {
        this.handleFileError(MESSAGES.errorMultiplesArchivos);
        throw new Error(MESSAGES.errorMultiplesArchivos);
      }

      reader.onload = (e: any) => {
        try {
          const archivoCargado = new Uint8Array(e.target.result);

          /** @firstFourBytes Inspecciona el tipo de archivo cargado a partir de los primeros cuatro bytes de su cabecera */
          const firstFourBytes = archivoCargado.subarray(0, 4);
          let uploadedFileHeaders = "";

          /* Se convierten los bytes a hexadecimal */
          for (let i = 0; i < firstFourBytes.length; i++) {
            uploadedFileHeaders += firstFourBytes[i].toString(16);
          }

          /** (@param excelHeaders): Son los valores hexadecimales de los primeros cuatro bytes de la cabeceras de archivos tipo .xls y .xlsx */

          const excelHeaders = ["20456c20", "504b34", "d0cf11e0", "3c623e4f"];
          /** Si el valor de la cabecera del archivo cargado no se encuentra en (@param excelHeaders), el archivo no es una hoja de cálculo de Excel. */
          if (!excelHeaders.includes(uploadedFileHeaders)) {
            throw new Error(MESSAGES.errorTipoArchivo);
          }

          const libro = XLSX.read(archivoCargado, { type: "array" });
          const nombreHoja: string = libro.SheetNames[0];
          const hoja: XLSX.WorkSheet = libro.Sheets[nombreHoja];

          /** Este condicional determina la cabecera del archivo a partir del botón de radio seleccionado. Si el archivo a cargar contiene encabezados ("true"), se tomarán las posiciones del array devuelto (@tipoEncabezadoArchivo = 1) para llenar los <select> del siguiente paso. Si se selecciona No ("false"), se tomarán las llaves devueltas por el objeto (@tipoEncabezadoArchivo = "A"), las cuales contienen el nombre de las columnas del archivo.

          @tipoEncabezadoArchivo maneja las opciones 1 y "A" de la llave "header", que son opciones de la librería SheetJS */

          let tipoEncabezadoArchivo: any = 1;
          if (this.formCargarArchivoFields.radioEncabezados.value === "false") {
            tipoEncabezadoArchivo = "A";
          }

          this.datosArchivoCargado = XLSX.utils.sheet_to_json(hoja, { header: tipoEncabezadoArchivo });
          this.encabezadosArchivoCargado = this.datosArchivoCargado[0];




          if (tipoEncabezadoArchivo === "A") {
            this.encabezadosArchivoCargado = Object
              .keys(this.datosArchivoCargado[0])
              .map((nombreColumna: string) => `Columna ${nombreColumna}`);

            // // console.log(this.datosArchivoCargado);
            /** Se agrega array vacío para que el `for()` de @handleFormMensaje inicie en 1
             *
             * Se utiliza Object.values(array) para convertir el objeto de cada fila en un array y poder ser manejado por el `for()` de @handleFormMensaje
              */
            this.datosArchivoCargado = [[], ...this.datosArchivoCargado.map(array => Object.values(array))];



            // this.encabezadosArchivoCargado = this.datosArchivoCargado[0];

          }

          // console.log(this.datosArchivoCargado);
          /** Se filtra @encabezadosArchivoCargado para eliminar elementos vacíos y quitar espacios en blanco. Se utiliza para cargar los <select> en el step Mensaje */
          this.encabezadosArchivoCargado = this.encabezadosArchivoCargado
            .filter(Boolean)
            .map((nombreColumna: string) => String(nombreColumna).trim());

          // // console.log(this.encabezadosArchivoCargado);
          // const camposDisponiblesParaAgregar: InterfazAgregarCampos[] = [];

          // this.encabezadosArchivoCargado.forEach((nombreColumna: string, index: number) =>
          //   camposDisponiblesParaAgregar.push({ posicion: index, nombreCampo: nombreColumna })
          // );
          // // console.log(camposDisponiblesParaAgregar)

          /** @dataSourceAgregarCampos Carga los campos del archivo cargado en el modal Agregar campos */
          // this.dataSourceAgregarCampos = camposDisponiblesParaAgregar;
          // this.formArrayNuevosCampos = this.formMensajeFields.formArrayNuevosCampos as FormArray;

          // this.dataSourceAgregarCampos.forEach((element: any) =>
          //   this.formArrayNuevosCampos.push(this.addNuevoCampoFormGroup(element))
          // );

          /** `this.formCargarArchivoFields.archivo.setErrors(null)` - Quita los errores del input file para cargar archivo y permite cambiar el estado de `this.formCargarArchivo`  a válido, lo que permite proceder al siguiente paso del stepper */
          this.formCargarArchivoFields.archivo.setErrors(null);


          // this.isNextButtonEnabled = true;
          this.nombreArchivo = file.name;
          // console.table(this.data[0])
          // // console.log(file)


        }
        catch (error) {
          console.error(error);
          this.handleFileError(MESSAGES.errorCargarArchivo);
          this.showInputFile();
        }
      };

      reader.onprogress = (event: any) => {
        if (event.lengthComputable) {
          this.progress = Math.round(event.loaded / event.total * 100);
        }
      };

      reader.onerror = (event: any) => {
        this.handleFileError(MESSAGES.errorCargarArchivo);
      };

      reader.onloadend = (event: any) => {
        this.ServiceProvider.preloaderOff();
      };

      reader.readAsArrayBuffer(file);
    } else {
      this.formCargarArchivoFields.radioEncabezados.markAsTouched();
    }
  }

  // setMensajesAEnviar($event: MatRadioChange) {
  //   this.totalDefinitivoMensajes = 0;
  //   this.totalDefinitivoCelulares = 0;

  //   if ($event.value === "true") {
  //     this.arrayTodosMensajes.forEach((datosMensaje: Mensaje) => {
  //       this.totalDefinitivoMensajes += datosMensaje.cantidadMensajes;
  //       this.totalDefinitivoCelulares++;
  //     });
  //     // this.totalDefinitivoMensajes = this.totalMensajesAEnviar;
  //     // this.totalDefinitivoCelulares = this.totalCelularesSuperioresValidos + this.totalCelularesValidos;
  //   } else {
  //     this.arrayTodosMensajes.forEach((datosMensaje: Mensaje) => {
  //       if (datosMensaje.cantidadCaracteres <= this.maxCaracteresMensaje) {
  //         this.totalDefinitivoMensajes += datosMensaje.cantidadMensajes;
  //         this.totalDefinitivoCelulares++;
  //       }
  //     });
  //     // this.totalDefinitivoMensajes -= this.totalMensajesMax;
  //     // this.totalDefinitivoCelulares -= this.totalCelularesSuperioresValidos;
  //   }
  //   this.valorMensajesAEnviar = this.totalDefinitivoMensajes * this.valorMensajeIndividual;
  // }

  arrayMensajesConvertidos: object[] = [];
  arrayMensajesSuperioresMaxCaracteres: object[] = [];
  /** Celulares que se excluirán porque superan la cuota de mensajes de envío a clientes */
  arrayCelularesExcluidos: string[] = [];
  isSuperiorMaxCaracteres: boolean = false;
  /** Cuenta los mensajes que superan los 160 caracteres */
  countMensajesSuperioresMaxCaracteres: number = 0;
  totalMensajesAEnviar: number = 0;
  totalCelularesValidos: number = 0;
  /** Total de celulares válidos de los mensajes que superan 160 caracteres */
  totalCelularesSuperioresValidos: number = 0;
  totalMensajesMax: number = 0;

  totalDefinitivoCelulares: number = 0;
  arrayTodosMensajes: object[];
  textAreaMensajeValue: string = "";

  async handleFormMensaje(formMensaje: FormGroup) {
    if (this.validateStep(formMensaje)) {
      this.ServiceProvider.preloaderOn();
      /* `dataArchivo` clona `datosArchivoCargado`. Se utiliza `map` en conjunto con `slice` para mantener un clon por valor en lugar de un clon por referencia para evitar que una modificación de un array afecte al otro. */
      this.dataArchivo = this.datosArchivoCargado.map(columnas => columnas.slice());

      /* Limpiar el botón de radio para que funcione el evento (change) */
      this.formConfirmacionFields.radioMensajesSuperiores.setValue("");
      const tipoMensajeSeleccionado: string = this.formTipoMensaje.value.radioTipoMensaje;
      // this.arrayMensajesConvertidos = [];
      // this.arrayMensajesSuperioresMaxCaracteres = [];
      this.arrayTodosMensajes = [];
      this.arrayCelularesExcluidos = [];
      this.totalMensajesAEnviar = 0;
      this.totalCelularesValidos = 0;
      this.totalCelularesSuperioresValidos = 0;
      this.totalMensajesMax = 0;
      this.isSuperiorMaxCaracteres = false;
      this.countMensajesSuperioresMaxCaracteres = 0;
      const selectCelular: string = formMensaje.value.selectCelular;
      const selectMensajePrecargado: string = formMensaje.value.selectMensajePrecargado;
      /** `this.textAreaMensajeValue` Toma el mensaje escrito por el usuario, reemplazando posibles saltos de línea por espacios. */
      this.textAreaMensajeValue = String(formMensaje.value.textAreaMensaje).replace(/(\r\n|\n|\r)/gm, " ").trim();
      let mensajeConvertido: string = this.textAreaMensajeValue;
      const arrayCamposAgregados: any[] = [];
      const indexSelectCelular: number = this.encabezadosArchivoCargado.indexOf(selectCelular);
      const indexSelectMensajePrecargado: number = this.encabezadosArchivoCargado.indexOf(selectMensajePrecargado);
      /** Guarda un array con los teléfonos de los clientes que hayan superado la cuota de mensajes recibidos en un día */
      let celularesExcluir: any = [];

      try {
        const bolsaMensajes: BolsaMensajes = await this.ServiceProvider.post(WEBSERVICE.GET_BOLSA_MENSAJES_USUARIO, { nombreBolsa: this.datosUsuarioJWT.permisos.selectBolsa });
        this.valorMensajeIndividual = bolsaMensajes.valor_mensaje_unidireccional;
        /* Si el envío no es prioritario, se buscan los celulares de los clientes que hayan superado la cuota de mensajes recibidos para exluirlos del archivo cargado */
        if (!this.formTipoMensajeFields.envioPrioritario.value) {
          // console.log("no es prioritario");

          const cuotaMensajesCliente: any = await this.ServiceProvider.get(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE);
          const archivoSinEncabezado = this.dataArchivo.slice(1);
          const arrayCelulares: string[] = Array.from(new Set(archivoSinEncabezado.map(datos => datos[indexSelectCelular])));

          /** Construye el objeto para revisar en la colección `log_difusion_dod` a qué celulares se les ha enviado mensajes entre el periodo de inicio y fin definido por la cuota de mensajes para todos los clientes */
          const datosCelularesDod = {
            celulares: arrayCelulares,
            fechaInicioMensajesCliente: cuotaMensajesCliente.fechaInicioMensajesCliente,
            fechaFinMensajesCliente: cuotaMensajesCliente.fechaFinMensajesCliente,
          };

          celularesExcluir = await this.ServiceProvider.post(WEBSERVICE.GET_LOG_CELULARES_DOD_EXCLUIR, { datosCelularesDod });
        }

        /* Se filtran los datos del archivo cargado para eliminar posibles celulares duplicados y evitar el envío de dos o más mensajes a un mismo cliente. También, se exluyen de `dataArchivo`, los celulares que hayan superado la cuota de clientes para este día */
        this.dataArchivo = this.dataArchivo
          .filter((datosFila, indexFila, arrayDataArchivo) => {
            const celularArchivo = datosFila[indexSelectCelular];
            /** Retorna `true` si el celular no está repetido en el archivo cargado; `false`, en caso contrario */
            const isCelularUnico: boolean = arrayDataArchivo.findIndex(dataFila => dataFila[indexSelectCelular] === celularArchivo) === indexFila;

            if (isCelularUnico) {
              if (!celularesExcluir.includes(String(celularArchivo))) {
                return true;
              }
              /* Si un celular del archivo está en el array `celularesExcluir`, este se descarta de `dataArchivo` y se guarda en `arrayCelularesExcluidos` */
              this.arrayCelularesExcluidos.push(celularArchivo);
            }
            return false;
          });

        /* Si el mensaje es individual, se agregan a `arrayCamposAgregados` los campos adicionados por el usuario */
        if (tipoMensajeSeleccionado === this.tiposMensaje[1]) {
          const camposAgregadosUsuario = formMensaje.value.formArrayNuevosCampos;

          camposAgregadosUsuario.forEach((campoAgregado: any) => {
            this.encabezadosArchivoCargado.forEach((nombreEncabezado: string, encabezadoIndex: number) => {
              if (campoAgregado.selectCampoAgregado === nombreEncabezado) {
                arrayCamposAgregados.push({
                  encabezadoIndex,
                  inputCampoAgregadoMarcador: campoAgregado.inputCampoAgregadoMarcador
                });
              }
            });
          });
        }

        /* Se inicia en 1 para no tomar los encabezados del archivo */
        for (let i = 1; i < this.dataArchivo.length; i++) {
          const celular: string = String(this.dataArchivo[i][indexSelectCelular]).trim();
          let cantidadMensajesPorUsuario: number = 1;

          /* individual */
          if (tipoMensajeSeleccionado === this.tiposMensaje[1]) {
            arrayCamposAgregados.forEach(camp => {
              const marcadorCampoAgregado = new RegExp(`@${camp.inputCampoAgregadoMarcador}`, "g");
              mensajeConvertido = mensajeConvertido.replace(marcadorCampoAgregado, String(this.dataArchivo[i][camp.encabezadoIndex]).trim());
            });

            mensajeConvertido = mensajeConvertido.replace(/@celular/g, celular);
          } else if (tipoMensajeSeleccionado === this.tiposMensaje[2]) {
            /* Si el mensaje es precargado, `mensajeConvertido` toma el valor de cada celda de la columna seleccionada en el <select> de mensaje precargado */
            mensajeConvertido = String(this.dataArchivo[i][indexSelectMensajePrecargado]).trim();
          }

          let caracteresMensaje: number = mensajeConvertido?.length;

          while (caracteresMensaje > this.maxCaracteresMensaje) {
            caracteresMensaje -= this.maxCaracteresMensaje;
            cantidadMensajesPorUsuario++;
            this.totalMensajesMax += cantidadMensajesPorUsuario;
          }

          this.totalMensajesAEnviar += cantidadMensajesPorUsuario;

          if (mensajeConvertido?.length > this.maxCaracteresMensaje) {
            this.isSuperiorMaxCaracteres = true;
            this.countMensajesSuperioresMaxCaracteres++;

            if (celular && celular.length === 10 && celular.startsWith("3")) {
              this.totalCelularesSuperioresValidos++;
            }

            // this.arrayMensajesSuperioresMaxCaracteres.push({
            //   cuenta: "",
            //   nombre: "",
            //   celular,
            //   mensaje: mensajeConvertido,
            //   cantidadCaracteres: mensajeConvertido?.length,
            //   cantidadMensajes: cantidadMensajesPorUsuario,
            // });
          } else {
            if (celular && celular.length === 10 && celular.startsWith("3")) {
              this.totalCelularesValidos++;
            }

            // this.arrayMensajesConvertidos.push({
            //   cuenta: "",
            //   nombre: "",
            //   celular,
            //   mensaje: mensajeConvertido,
            //   cantidadCaracteres: mensajeConvertido?.length,
            //   cantidadMensajes: cantidadMensajesPorUsuario,
            // });
          }

          this.arrayTodosMensajes.push({
            cuenta: "",
            nombre: "",
            celular,
            mensaje: mensajeConvertido,
            cantidadCaracteres: mensajeConvertido?.length,
            cantidadMensajes: cantidadMensajesPorUsuario,
          });

          /* Se reinicia `mensajeConvertido` para retomar los @marcadores en la siguiente iteración */
          mensajeConvertido = this.textAreaMensajeValue;
          this.totalDefinitivoMensajes = this.totalMensajesAEnviar;
        } /* Fin del `for` */

        /* Retorna tanto los mensajes válidos como los que superan los 160 caracteres */
        // this.arrayTodosMensajes = [...this.arrayMensajesConvertidos, ...this.arrayMensajesSuperioresMaxCaracteres];

        // try {
        // const cuotaMensajesCliente: any = (await this.ServiceProvider.get(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE))[0];

        // const arrayCelulares: string[] = this.arrayTodosMensajes.map((mensaje: Mensaje) => mensaje.celular);

        // /** Construye el objeto para revisar en la colección `log_difusion_dod` a qué celulares se les ha enviado mensajes entre el periodo de inicio y fin definido por la cuota de mensajes para todos los clientes */
        // const datosCelularesDod = {
        //   celulares: arrayCelulares,
        //   fechaInicioMensajesCliente: cuotaMensajesCliente.fechaInicioMensajesCliente,
        //   fechaFinMensajesCliente: cuotaMensajesCliente.fechaFinMensajesCliente,
        // };

        // const celularesExcluir: any = await this.ServiceProvider.post(WEBSERVICE.GET_LOG_CELULARES_DOD_EXCLUIR, { datosCelularesDod });

        // celularesExcluir.forEach((celularAExcluir: string) => {
        //   this.arrayTodosMensajes = this.arrayTodosMensajes.filter((mensaje: Mensaje) => {
        //     if (mensaje.celular !== celularAExcluir) {
        //       return true;
        //     }
        //     this.arrayCelularesExcluidos.push(mensaje);

        //     if (mensaje.cantidadCaracteres > this.maxCaracteresMensaje) {
        //       this.totalMensajesMax -= mensaje.cantidadMensajes;
        //       this.totalMensajesAEnviar -= mensaje.cantidadMensajes;
        //       this.totalDefinitivoMensajes -= mensaje.cantidadMensajes;
        //       this.totalCelularesSuperioresValidos--;
        //       this.countMensajesSuperioresMaxCaracteres--;
        //     } else {
        //       this.totalMensajesAEnviar--;
        //       this.totalDefinitivoMensajes--;
        //       this.totalCelularesValidos--;
        //     }
        //     return false;
        //   });
        // });

        this.totalDefinitivoCelulares = this.totalCelularesSuperioresValidos + this.totalCelularesValidos;
        this.valorMensajesAEnviar = this.totalDefinitivoMensajes * this.valorMensajeIndividual;

        // console.log(celularesExcluir);
        // console.log("arrayCelularesExcluidos", this.arrayCelularesExcluidos);
        // console.log("todos", this.arrayTodosMensajes);


        this.dataSourceStepConfirmacion = new MatTableDataSource(this.arrayTodosMensajes);
        this.dataSourceStepConfirmacion.paginator = this.paginator;
        this.dataSourceStepConfirmacion.sort = this.sort;
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    }
  }


  showAdvertenciaSendMensajes(formConfirmacion: FormGroup) {
    if (this.isSuperiorMaxCaracteres) {
      this.formConfirmacionFields.radioMensajesSuperiores.setValidators([Validators.required]);
    } else {
      this.formConfirmacionFields.radioMensajesSuperiores.clearValidators();
    }
    this.formConfirmacionFields.radioMensajesSuperiores.updateValueAndValidity();

    if (this.validateStep(formConfirmacion)) {
      this.ServiceProvider.openPopup("Advertencia", MESSAGES.advertenciaEnviarMensajes, "sendMensajes", this);
    }
  }

  async sendMensajes() {
    this.ServiceProvider.preloaderOn();
    /* En un principio, se enviarán todos los mensajes */
    let mensajesAEnviar: object[] = this.arrayTodosMensajes;

    /* Verifica que cuando existan mensajes superiores a 160 caracteres y el usuario no quiera enviarlos, los `mensajesAEnviar` sean solo los mensajes inferiores o iguales a 160 caracteres */
    if (this.isSuperiorMaxCaracteres && this.formConfirmacionFields.radioMensajesSuperiores.value === "false") {
      mensajesAEnviar = this.arrayTodosMensajes.filter((mensaje: Mensaje) => mensaje.cantidadCaracteres <= this.maxCaracteresMensaje);
    }
    // console.log(mensajesAEnviar);

    const datosMensaje: DatosMensajeDod = {
      idUsuario: this.idUsuario,
      nombreUsuario: `${this.datosUsuarioJWT.nombres} ${this.datosUsuarioJWT.apellidos}`,
      motivoEnvio: String(this.formTipoMensaje.value.textAreaMotivo).trim(),
      nombreBolsa: "",
      mensajes: {
        metodoEnvio: "Desde archivo",
        tipoMensaje: this.formTipoMensaje.value.radioTipoMensaje,
        rawMensaje: String(this.formMensaje.value.textAreaMensaje).trim(),
        mensajes: {},
        valorMensajeIndividual: 0
      }
    };

    /* Si el mensaje a enviar es general */
    if (this.formTipoMensaje.value.radioTipoMensaje === this.tiposMensaje[0]) {
      const filtro: string[] = ["cuenta", "nombre", "celular"];
      /** Filtra los mensajes a enviar y retorna un array de objetos con solo las llaves `cuenta`, `nombre` y `celular` */
      const datosUsuario: object[] = this.ServiceProvider.filtrarArray(mensajesAEnviar, filtro);
      // const datosUsuario: object[] = mensajesAEnviar.map(usuario => {
      //   return Object.fromEntries(
      //     Object.entries(usuario).filter(([key]) => filtro.includes(key))
      //   );
      // });

      const dataMensajeGeneral: object = {
        mensaje: this.textAreaMensajeValue,
        cantidadCaracteres: this.textAreaMensajeValue.length,
        /* Se toma la cantidad de mensajes del primer dato de `mensajesAEnviar` ya que al ser un mensaje general, es igual para todos los mensajes */
        cantidadMensajes: mensajesAEnviar[0]["cantidadMensajes"],
        datosUsuario
      };

      datosMensaje.mensajes.mensajes = dataMensajeGeneral;
    } else {
      /* Si el mensaje es precargado, poner una cadena vacía como `rawMensaje` */
      if (this.formTipoMensaje.value.radioTipoMensaje === this.tiposMensaje[2]) {
        datosMensaje.mensajes.rawMensaje = "";
      }
      datosMensaje.mensajes.mensajes = mensajesAEnviar;
    }

    // console.log(datosMensaje);

    try {
      const permisosUsuario: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_USUARIO, { idUsuario: this.idUsuario });

      /* Verifica que antes de enviar los mensajes, el usuario tenga activado el permiso */
      if (permisosUsuario.permisos.dodEnviarSms) {
        const bolsaMensajes: BolsaMensajes = await this.ServiceProvider.post(WEBSERVICE.GET_BOLSA_MENSAJES_USUARIO, { nombreBolsa: this.datosUsuarioJWT.permisos.selectBolsa });
        /* Se verifica que antes de enviar los mensajes, el precio de estos no supere el saldo de la bolsa */
        this.valorMensajesAEnviar = this.totalDefinitivoMensajes * bolsaMensajes.valor_mensaje_unidireccional;

        /* Revisa que el valor de los mensajes a enviar no supere el valor actual de la bolsa de mensajes, o que la bolsa de mensajes sea CHEC, ya que esta no cuenta con saldo disponible del que se pueda restar el precio de los SMS */
        if (this.valorMensajesAEnviar <= bolsaMensajes.valor_actual || bolsaMensajes.nombre === "CHEC") {
          const cuotaMensajesUsuario: CuotaMensajesUsuario = await this.ServiceProvider.post(WEBSERVICE.GET_CUOTA_MENSAJES_USUARIO, { idUsuario: this.idUsuario });
          /* Se enviarán los SMS si el total de mensajes a enviar es menor o igual a la cantidad de mensajes disponibles que tiene el usuario. */
          if (this.totalDefinitivoMensajes <= cuotaMensajesUsuario.cantidadMensajesUsuario) {
            datosMensaje.mensajes.valorMensajeIndividual = bolsaMensajes.valor_mensaje_unidireccional;
            datosMensaje.nombreBolsa = bolsaMensajes.nombre;

            /* Se envían los mensajes */
            await this.ServiceProvider.post(WEBSERVICE.SEND_MENSAJES_DOD, datosMensaje);

            // console.log(this.totalDefinitivoMensajes);
            // console.log(bolsaMensajes);
            // console.log(mensajesAEnviar);


            const datosCuotaMensajesUsuario = {
              idCuota: cuotaMensajesUsuario._id.$oid,
              cantidadMensajesEnviados: this.totalDefinitivoMensajes
            };
            // console.log(datosCuotaMensajesUsuario);
            /* Se actualiza la cuota de mensajes del usuario que envía */
            await this.ServiceProvider.post(WEBSERVICE.UPDATE_CUOTA_MENSAJES_USUARIO_ENVIO_SMS, datosCuotaMensajesUsuario);

            this.modalMensajesEnviados.showModal();
          } else {
            this.ServiceProvider.openPopup("error", MESSAGES.errorCuotaMensajesUsuario);
          }
        } else {
          this.ServiceProvider.openPopup("error", MESSAGES.errorBolsaMensajes);
        }
      } else {
        this.ServiceProvider.openPopup("error", MESSAGES.errorPermisoEnvioMensajes);
      }
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  };

  // openDialog(accion: string, datosMensaje: Mensaje) {
  //   datosMensaje.accion = accion;
  //   datosMensaje.maxCaracteresMensaje = this.maxCaracteresMensaje;
  //   const dialogRef = this.dialog.open(DialogBoxComponent, {
  //     width: "500px",
  //     data: datosMensaje,
  //     backdropClass: "backdrop_material_dialog",
  //   });

  //   dialogRef.afterClosed().subscribe(result => {
  //     if (result && result.event === "Editar") {
  //       this.updateDatosFila(result.data);
  //     }
  //   });
  // }

  // updateDatosFila(datosFila: Mensaje) {
  //   this.isSuperiorMaxCaracteres = false;
  //   this.countMensajesSuperioresMaxCaracteres = 0;
  //   this.totalDefinitivoMensajes = 0;
  //   this.totalDefinitivoCelulares = 0;

  //   this.arrayTodosMensajes.forEach((datosMensaje: Mensaje) => {
  //     let cantidadMensajes = 1;

  //     if (datosMensaje.celular === datosFila.celular) {
  //       datosMensaje.mensaje = datosFila.mensaje.trim();
  //       datosMensaje.cantidadCaracteres = datosMensaje.mensaje.length;
  //       let cantidadCaracteres = datosMensaje.cantidadCaracteres;

  //       while (cantidadCaracteres > this.maxCaracteresMensaje) {
  //         cantidadCaracteres -= this.maxCaracteresMensaje;
  //         cantidadMensajes++;
  //       }
  //       datosMensaje.cantidadMensajes = cantidadMensajes;
  //     }

  //     if (datosMensaje.cantidadCaracteres > this.maxCaracteresMensaje) {
  //       this.isSuperiorMaxCaracteres = true;
  //       this.countMensajesSuperioresMaxCaracteres++;
  //       this.formConfirmacionFields.radioMensajesSuperiores.setValue("");
  //     }

  //     this.totalDefinitivoMensajes += datosMensaje.cantidadMensajes;
  //     this.totalDefinitivoCelulares++;
  //   }); /* Fin forEach */
  //   this.valorMensajesAEnviar = this.totalDefinitivoMensajes * this.valorMensajeIndividual;
  //   this.dataSourceStepConfirmacion = new MatTableDataSource(this.arrayTodosMensajes);
  //   this.dataSourceStepConfirmacion.paginator = this.paginator;
  //   this.dataSourceStepConfirmacion.sort = this.sort;
  // }

  /** Actualiza todos los datos del step Confirmación (Cartas y tabla de mensajes) */
  updateInfoMensajes(infoMensajes: InfoMensajes) {
    this.totalDefinitivoMensajes = infoMensajes.totalMensajes;
    this.valorMensajesAEnviar = infoMensajes.valorMensajes;
    this.totalDefinitivoCelulares = infoMensajes.totalCelulares;
    this.isSuperiorMaxCaracteres = infoMensajes.isSuperiorMaxCaracteres;
    this.countMensajesSuperioresMaxCaracteres = infoMensajes.countMensajesSuperioresMaxCaracteres;
    this.dataSourceStepConfirmacion = infoMensajes.dataSourceStepConfirmacion;
  }

  /** Actualiza las cartas que contienen los datos numéricos de los mensajes a enviar */
  updateTotalesCards(totales: InfoMensajes) {
    this.totalDefinitivoMensajes = totales.totalMensajes;
    this.valorMensajesAEnviar = totales.valorMensajes;
    this.totalDefinitivoCelulares = totales.totalCelulares;
  }

}
