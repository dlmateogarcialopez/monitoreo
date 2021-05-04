import { Component, OnInit, Input, ViewChild, ChangeDetectorRef } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray, FormControl, AbstractControl } from "@angular/forms";
import { HttpClient, HttpEvent, HttpEventType } from "@angular/common/http";
import { ServiceProvider } from "../../../config/services";
import { MESSAGES } from "../../../config/messages";
import { WEBSERVICE } from "../../../config/webservices";
import { Router } from "@angular/router";
import { AuthenticationService } from "../../../config/authentication.service";
import { DomSanitizer } from "@angular/platform-browser";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import { MatTableDataSource } from "@angular/material/table";
import { ModalDirective } from 'ngx-bootstrap/modal';
import { SelectionModel } from '@angular/cdk/collections';
import { Observable } from 'rxjs';
import { startWith, map } from 'rxjs/operators';
import { InterfazAgregarCampos, Mensaje, DatosMensajeDod, CuotaMensajesUsuario, Users, BolsaMensajes, InfoMensajes } from '../../../config/interfaces';
import { MatCheckboxChange } from '@angular/material/checkbox';
import { MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';
import { MatRadioChange } from '@angular/material/radio';
import { animate, state, style, transition, trigger } from '@angular/animations';
import { JwtHelperService } from '@auth0/angular-jwt';
import { ModalMensajesEnviadosComponent } from '../modalMensajesEnviados/modalMensajesEnviados.component';

const DATA_DESTINATARIOS: InterfazAgregarCampos[] = [
  { posicion: 0, nombreCampo: "Segmento" },
  { posicion: 1, nombreCampo: "Subsegmento" },
  { posicion: 2, nombreCampo: "Clase de servicio" },
  { posicion: 3, nombreCampo: "Ubicación" },
  { posicion: 4, nombreCampo: "Circuito" },
  { posicion: 5, nombreCampo: "Nodo" },
  { posicion: 6, nombreCampo: "Municipio" },
  { posicion: 7, nombreCampo: "Estrato" },
];

@Component({
  selector: "envios-desde-bd",
  templateUrl: "enviarDesdeBd.component.html",
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({ height: '0px', minHeight: '0' })),
      state('expanded', style({ height: '*' })),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ],
})

export class EnviarDesdeBdComponent implements OnInit {
  usuarioActual: any = {};
  isCollapsed: boolean = true;
  expandedElement: any;
  filteredSegmento: Observable<string[]>;
  filteredSubsegmento: Observable<string[]>;
  filteredClaseServicio: Observable<string[]>;
  filteredUbicacion: Observable<string[]>;
  filteredCircuito: Observable<string[]>;
  filteredNodo: Observable<string[]>;
  filteredMunicipio: Observable<string[]>;
  filteredEstrato: Observable<string[]>;
  arraySegmento: string[] = [];
  arraySubsegmento: string[] = [];
  arrayClaseServicio: string[] = [];
  arrayUbicacion: string[] = [];
  arrayCircuito: string[] = [];
  arrayNodo: string[] = [];
  arrayMunicipio: string[] = [];
  arrayEstrato: string[] = [];
  arrayAyudaMarcadores: any[] = [
    { nombre: "Celular", marcador: "celular", categoria: "usuario", icono: "mobile-alt" },
    { nombre: "Correo", marcador: "correo", categoria: "usuario", icono: "envelope" },
    { nombre: "Documento", marcador: "documento", categoria: "usuario", icono: "id-card" },
    { nombre: "Nombre", marcador: "nombre", categoria: "usuario", icono: "user" },
    { nombre: "Teléfono", marcador: "telefono", categoria: "usuario", icono: "phone-alt" },
    { nombre: "Circuito", marcador: "circuito", categoria: "ubicacion", icono: "circle-notch" },
    { nombre: "Dirección", marcador: "direccion", categoria: "ubicacion", icono: "map-marker-alt" },
    { nombre: "Municipio", marcador: "municipio", categoria: "ubicacion", icono: "globe-americas" },
    { nombre: "Nodo", marcador: "nodo", categoria: "ubicacion", icono: "box" },
    { nombre: "Ubicación", marcador: "ubicacion", categoria: "ubicacion", icono: "search-location" },
    { nombre: "Clase de servicio", marcador: "clase_servicio", categoria: "otros", icono: "server" },
    { nombre: "Cuenta", marcador: "cuenta", categoria: "otros", icono: "address-book" },
    { nombre: "Estrato", marcador: "estrato", categoria: "otros", icono: "list-ol" },
    { nombre: "Segmento", marcador: "segmento", categoria: "otros", icono: "project-diagram" },
    { nombre: "Subsegmento", marcador: "subsegmento", categoria: "otros", icono: "cube" },
  ];

  arrayMarcadores: string[] = this.arrayAyudaMarcadores.map(marcador => marcador.marcador);

  marcadoresConfig: object = {
    mentionSelect: this.insertSpanText,
    insertHTML: true,
    dropUp: true
  };

  mensajeVistaPrevia: string = "";
  objVistaPrevia: object = {
    celular: "3148757685",
    correo: "lucytorres@chec.com.co",
    documento: "75653490",
    nombre: "Lucy Torres",
    telefono: "8808574",
    circuito: "AMR23L13",
    direccion: "Cra 9 Cll 13 9 03 Las Palmas",
    municipio: "Manizales",
    nodo: "D23097",
    ubicacion: "Urbano",
    clase_servicio: "Residencial",
    cuenta: "101995035",
    estrato: "3",
    segmento: "Hogares",
    subsegmento: "Ideal"
  };

  isLinear: boolean = true;
  formDestinatarios: FormGroup;
  formTipoMensaje: FormGroup;
  formMensaje: FormGroup;
  formConfirmacion: FormGroup;
  tiposMensaje: string[] = ["general", "individual"];
  maxCaracteresMensaje: number = 160; /* Cantidad máxima de caracteres de los SMS */
  @ViewChild('modalAgregarCamposDestinatarios') public modalAgregarCamposDestinatarios: ModalDirective;
  @ViewChild('modalCelularesExcluidos') modalCelularesExcluidos: ModalDirective;
  @ViewChild('modalMensajesEnviados') modalMensajesEnviados: ModalMensajesEnviadosComponent;
  dataSourceCamposDestinatarios: any = [];
  columnsStepConfirmacion: string[] = ["celular", "mensaje", "cantidadCaracteres", "cantidadMensajesPorUsuario"];
  dataSourceStepConfirmacion: MatTableDataSource<any>;
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;
  MESSAGES: object = MESSAGES;
  otrosCamposDestinatarios: FormArray;
  camposBuscar: object = {};
  isLoading: boolean = false;
  totalCelularesValidosFiltro: number = 0;
  isCheckboxSubsegmentoChecked: boolean = false;
  isCheckboxClaseServicioChecked: boolean = false;
  isCheckboxUbicacionChecked: boolean = false;
  isCheckboxCircuitoChecked: boolean = false;
  isCheckboxNodoChecked: boolean = false;
  isCheckboxMunicipioChecked: boolean = false;
  isCheckboxEstratoChecked: boolean = false;
  isCheckboxNumeroCuentaChecked: boolean = false;
  valorMensajeIndividual: number = 0/* this.ServiceProvider.valorMensajeIndividual */;
  valorMensajesAEnviar: number = 0;
  datosUsuarioJWT: Users = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data;
  idUsuario: string = this.datosUsuarioJWT._id.$oid;
  usuarioHasEnvioPrioritario: boolean = this.datosUsuarioJWT.permisos.dodEnviarSms && this.datosUsuarioJWT.permisos.dodPrioridadEnvio;

  tablaPrueba = [
    {
      "celular": "3125566546",
      "mensajeConvertido": "hola Mario tu número de cuenta 35983443872 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica. hola Mario tu número de cuenta 35983443872 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica. hola Mario tu número de cuenta 35983443872 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 180,
      "cantidadMensajesPorUsuario": 1
    },
    {
      "celular": "3218766454",
      "mensajeConvertido": "hola Susana tu número de cuenta 3112343255 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 160,
      "cantidadMensajesPorUsuario": 1
    },
    {
      "celular": "3176867787",
      "mensajeConvertido": "hola Rubí tu número de cuenta 98907344345 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 159,
      "cantidadMensajesPorUsuario": 1
    },
    {
      "celular": "3014543334",
      "mensajeConvertido": "hola Soraya tu número de cuenta 1445565667 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 160,
      "cantidadMensajesPorUsuario": 1
    },
    {
      "celular": "3108674352",
      "mensajeConvertido": "hola Alejandro Osorio tu número de cuenta 7498379753 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 170,
      "cantidadMensajesPorUsuario": 2
    },
    {
      "celular": "3214367890",
      "mensajeConvertido": "hola Johan Osorio Gomez tu número de cuenta 25664854854 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 173,
      "cantidadMensajesPorUsuario": 2
    },
    {
      "celular": "3214535678",
      "mensajeConvertido": "hola Pedro López tu número de cuenta 3598344345 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 165,
      "cantidadMensajesPorUsuario": 2
    },
    {
      "celular": "3163456554",
      "mensajeConvertido": "hola Salvador tu número de cuenta 83443458976 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 163,
      "cantidadMensajesPorUsuario": 2
    },
    {
      "celular": "3215654674",
      "mensajeConvertido": "hola Magdalena tu número de cuenta 1242566556 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 163,
      "cantidadMensajesPorUsuario": 2
    },
    {
      "celular": "3205566364",
      "mensajeConvertido": "hola Patricia tu número de cuenta 5964945865 se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica",
      "cantidadCaracteres": 162,
      "cantidadMensajesPorUsuario": 2
    }
  ];

  constructor(
    private authenticationService: AuthenticationService,
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    private sanitizer: DomSanitizer,
    private readonly cdr: ChangeDetectorRef,
    public jwtHelper: JwtHelperService,
  ) { }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.ServiceProvider.setTituloPestana("Envío desde base de datos");
    this.usuarioActual = this.authenticationService.usuarioActualValue;
    this.setDestinatariosControls();
    this.setDataSourceDestinatarios();
    this.setFormTipoMensajeControls();
    this.setFormMensajeControls();
    this.setFormConfirmacionControls();
    // this.dataSourceStepConfirmacion = new MatTableDataSource(this.tablaPrueba);
    // this.dataSourceStepConfirmacion.paginator = this.paginator;
    // this.dataSourceStepConfirmacion.sort = this.sort;

    Promise.all([
      this.ServiceProvider.manageCuotaMensajesUsuario(this.idUsuario),
      this.ServiceProvider.manageCuotaMensajesClientes(this.idUsuario),
      this.getSegmentos()
    ])
      .then(() => this.ServiceProvider.preloaderOff());
  }

  /** Detecta cambios en el valor del control `totalUsuarios` de `formDestinatarios` verificando que este total sea superior a cero y el usuario pueda continuar al Step 2. */
  ngAfterContentChecked() {
    this.cdr.detectChanges();
  }

  insertSpanText(nombreCampo: any) {
    return `@${nombreCampo.label} `;
  }

  showVistaPrevia($event: any) {
    let mensajeTextArea: string = $event.target.value;
    const arrayMarcadoresVistaPrevia: string[] = this.getMarcadoresRealesTextArea(mensajeTextArea);

    // console.log(arrayMarcadoresVistaPrevia);
    arrayMarcadoresVistaPrevia.forEach((marcador: string) => {
      // marcador = this.ServiceProvider.removeCaracteresEspeciales(marcador);
      // console.log("despues", marcador);
      const marcadorArroba = new RegExp(`@${marcador}`, "g");
      /* Nos aseguramos de que `marcador` exista en `this.objVistaPrevia` para que `@marcador` no retorne `undefined` */
      if (this.objVistaPrevia[marcador]) {
        mensajeTextArea = mensajeTextArea.replace(marcadorArroba, this.objVistaPrevia[marcador]);
      }
    });
    this.mensajeVistaPrevia = mensajeTextArea;
  }

  addControlesCamposDestinatarios(element: any) {
    let checkboxValue: boolean = false;

    /* Poner valor `true` al checkbox de segmento al cargar la página para que se muestre el filtro. También deshabilitar este checkbox ya que este campo siempre se va a mostrar */
    if (element.nombreCampo === "Segmento") {
      checkboxValue = true;
    }

    return this.fb.group({
      checkbox: [{ value: checkboxValue, disabled: checkboxValue }],
      nombre: [element]
    });
  }

  setDataSourceDestinatarios() {
    /** @dataSourceCamposDestinatarios Carga los campos del archivo cargado en el modal Agregar campos */
    this.dataSourceCamposDestinatarios = DATA_DESTINATARIOS;
    /* Se limipia `this.otrosCamposDestinatarios` para volver a llenarlo con los controles para los checkboxes de Agregar Otros Campos (Todos los controles, excepto el de Segmento, tendrán el checkbox sin seleccionar => Ver `this.addControlesCamposDestinatarios(element)`) */
    this.otrosCamposDestinatarios?.clear();
    this.otrosCamposDestinatarios = this.formDestinatariosFields.otrosCamposDestinatarios as FormArray;

    this.dataSourceCamposDestinatarios.forEach((element: any) =>
      this.otrosCamposDestinatarios.push(this.addControlesCamposDestinatarios(element))
    );
  }

  setDestinatariosControls() {
    this.formDestinatarios = this.fb.group({
      otrosCamposDestinatarios: this.fb.array([]),
      radioFiltro: ["otrosCampos", Validators.required],
      formGroupFiltros: this.fb.group({
        selectSegmento: ["", Validators.required],
        selectSubsegmento: [""],
        selectClaseServicio: [""],
        selectUbicacion: [""],
        selectCircuito: [""],
        selectNodo: [""],
        selectMunicipio: [""],
        selectEstrato: [""],
      }),
      textAreaNumeroCuenta: [""],
      porcentajeUsuarios: [100, [Validators.required, Validators.min(1), Validators.max(100)]],
      totalUsuarios: [0, [Validators.min(1)]]
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
      textAreaMensaje: ["", [Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]],
    });
  }

  setFormConfirmacionControls() {
    this.formConfirmacion = this.fb.group({
      radioMensajesSuperiores: ["", Validators.required],
    });
  }

  get formDestinatariosFields() {
    return this.formDestinatarios.controls;
  }

  get formGroupFiltrosFields() {
    return (this.formDestinatarios.controls.formGroupFiltros as FormGroup).controls;
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

  setFormControlValidators(formGroup: FormGroup) {
    Object.keys(formGroup.controls).forEach((field: string) => {
      const control = formGroup.get(field);
      control.setValidators(Validators.required);
      control.updateValueAndValidity();
    });
  }

  clearFormControlValidators(formGroup: FormGroup) {
    Object.keys(formGroup.controls).forEach((field: string) => {
      const control = formGroup.get(field);
      control.clearValidators();
      control.updateValueAndValidity();
    });
  }

  setFiltrosValidators($event: MatRadioChange) {
    const filtroSeleccionado: string = $event.value;
    /* Si selecciona el radio `Número de cuenta` */
    if (filtroSeleccionado === "numeroCuenta") {
      this.formDestinatariosFields.textAreaNumeroCuenta.setValidators([Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]);
      this.formGroupFiltrosFields.selectSegmento.setValue("");
      this.formDestinatariosFields.porcentajeUsuarios.clearValidators();

      /* Se limpian todas las validaciones de los controles de los filtros para que `formDestinatarios` sea válido */
      this.clearFormControlValidators(this.formDestinatarios.controls.formGroupFiltros as FormGroup);
    } else {
      /* Si selecciona el radio `Otros campos` */
      /* Se llenan de nuevo los controles para los checkboxes de campos */
      this.setDataSourceDestinatarios();
      this.formDestinatariosFields.textAreaNumeroCuenta.setValue("");
      this.formDestinatariosFields.textAreaNumeroCuenta.clearValidators();
      this.formDestinatariosFields.porcentajeUsuarios.setValidators([Validators.required, Validators.min(1), Validators.max(100)]);
      this.formGroupFiltrosFields.selectSegmento.setValidators(Validators.required);
      /* Se ponen todas las condicionales en falso para ocultar los campos del filtro en el template y solo mostrar el filtro de Segmento */
      this.isCheckboxSubsegmentoChecked = false;
      this.isCheckboxClaseServicioChecked = false;
      this.isCheckboxUbicacionChecked = false;
      this.isCheckboxCircuitoChecked = false;
      this.isCheckboxNodoChecked = false;
      this.isCheckboxMunicipioChecked = false;
      this.isCheckboxEstratoChecked = false;
    }
    this.totalCelularesValidosFiltro = 0;
    this.formDestinatariosFields.textAreaNumeroCuenta.updateValueAndValidity();
    this.formDestinatariosFields.porcentajeUsuarios.updateValueAndValidity();
    this.formGroupFiltrosFields.selectSegmento.updateValueAndValidity();
  }

  /** Filtra los datos de los destinatarios a medida que el usuario escribe un valor en el campo de texto */
  private filtrarDatosDestinatarios(valueInput: string, arrayDatosDestinatario: any[]): string[] {
    const valorFiltro = valueInput.toLowerCase();
    return arrayDatosDestinatario.filter(option => option.toLowerCase().includes(valorFiltro));
  }

  getTotalUsuarios() {
    let total: number = 0;
    /* Si los filtros están presentes (otrosCampos), se realiza el cálculo de cantidad de usuarios con respecto al `porcentajeUsuarios` seleccionado por el usuario */
    if (this.formDestinatariosFields.radioFiltro.value === "otrosCampos") {
      total = Math.floor(this.totalCelularesValidosFiltro * this.formDestinatariosFields.porcentajeUsuarios.value / 100);
    } else {
      /* Si no, se retorna el total de número de cuentas del `textAreaNumeroCuenta` */
      total = this.totalCelularesValidosFiltro;
    }
    this.formDestinatariosFields.totalUsuarios.setValue(total);
    return total;
  }

  /** Realiza la petición a la base de datos para recuperar la cantidad de usuarios por los filtros aplicados  */
  async updateTotalUsuariosFiltro() {
    this.isLoading = true;

    if (this.camposBuscar.hasOwnProperty("UBICACION")) {
      /* Si se realiza filtro por UBICACION, tomar solo la primera letra: "U" para URBANO o "R" para RURAL */
      this.camposBuscar["UBICACION"] = this.camposBuscar["UBICACION"][0];
    }
    // console.log(this.camposBuscar);

    try {
      const infoTotales: any = await this.ServiceProvider.post(WEBSERVICE.GET_TOTAL_USUARIOS_FILTRO_BD_CHEC, { camposBuscar: this.camposBuscar });
      // console.log(infoTotales);

      this.totalCelularesValidosFiltro = infoTotales.total_usuarios_cel_validos;

    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.isLoading = false;
    }
  }

  /** Cuenta los usuarios existentes por los filtros aplicados
   * @param $event - Evento que contiene la opción del filtro seleccionada por el usuario
   * @param inputDestinatario - El nombre del filtro que se utiliza para agrega al objeto `this.camposBuscar`
  */
  countUsuariosFiltro($event: MatAutocompleteSelectedEvent, inputDestinatario: string) {
    /* Solo se llamará `this.updateTotalUsuariosFiltro()` si los filtros son válidos */
    // if (this.formDestinatarios.controls.formGroupFiltros.valid) {
    const opcionSeleccionada = $event.option.value;
    this.camposBuscar["SEGMENTO"] = this.formGroupFiltrosFields.selectSegmento.value;

    switch (inputDestinatario) {
      case "selectSegmento":
        this.camposBuscar["SEGMENTO"] = opcionSeleccionada;
        break;
      case "selectSubsegmento":
        let nombreSubsegmento: string = opcionSeleccionada;
        if (nombreSubsegmento === "Aliado Estratégico") {
          nombreSubsegmento = "Aliado Estrat�gico";
        }
        else if (nombreSubsegmento === "Rubí") {
          nombreSubsegmento = "Rub�";
        }
        this.camposBuscar["SUBSEGMENTO"] = nombreSubsegmento;
        break;
      case "selectClaseServicio":
        this.camposBuscar["CLASE_SERVICIO"] = opcionSeleccionada;
        break;
      case "selectUbicacion":
        this.camposBuscar["UBICACION"] = opcionSeleccionada;
        break;
      case "selectCircuito":
        this.camposBuscar["CIRCUITO"] = opcionSeleccionada;
        break;
      case "selectNodo":
        this.camposBuscar["NODO"] = opcionSeleccionada;
        break;
      case "selectMunicipio":
        this.camposBuscar["MUNICIPIO"] = opcionSeleccionada;
        break;
      case "selectEstrato":
        this.camposBuscar["ESTRATO"] = opcionSeleccionada;
        break;
    }

    /* Si el campo porcentajeUsuarios no es válido, ponerle un valor de 100 para realizar la petición `this.updateTotalUsuariosFiltro()` */
    if (this.formDestinatariosFields.porcentajeUsuarios.invalid) {
      this.formDestinatariosFields.porcentajeUsuarios.setValue(100);
    }
    this.updateTotalUsuariosFiltro();
    // console.log(this.camposBuscar);


    // }
    // else {
    //   // console.log("mal");
    //   this.ServiceProvider.validateAllFormFields(this.formDestinatarios);
    // }
  }





  checkCamposDestinatarios2($event: MatCheckboxChange, campoDestinatario: InterfazAgregarCampos) {
    /** @inputHasValue Revisa si en el momento de quitar un filtro, este tiene algún valor seleccionado. En tal caso, se reiniciará el valor del filtro a una cadena vacía. */
    let inputHasValue: boolean = false;

    switch (campoDestinatario.nombreCampo) {
      // case "Segmento":
      //   this.isCheckboxSegmentoChecked = $event.checked;

      //   if (this.isCheckboxSegmentoChecked) {
      //     this.formGroupFiltrosFields.selectSegmento.setValidators([Validators.required]);

      //     /* Permite que solo se haga la petición al backend si `arraySegmento` está vacío */
      //     // if (!this.arraySegmento.length) {
      //     //   this.getSegmentos();
      //     // }
      //   } else {
      //     this.formGroupFiltrosFields.selectSegmento.clearValidators();
      //     delete this.camposBuscar["SEGMENTO"];

      //     if (this.formGroupFiltrosFields.selectSegmento.value) {
      //       inputHasValue = true;
      //       this.formGroupFiltrosFields.selectSegmento.setValue("");
      //     }
      //   }
      //   this.formGroupFiltrosFields.selectSegmento.updateValueAndValidity();
      //   break;
      case "Subsegmento":
        this.isCheckboxSubsegmentoChecked = $event.checked;

        if (this.isCheckboxSubsegmentoChecked) {
          this.formGroupFiltrosFields.selectSubsegmento.setValidators([Validators.required]);
          // this.camposBuscar["SUBSEGMENTO"] = "";

          /* Permite que solo se haga la petición al backend si `arraySubsegmento` está vacío */
          if (!this.arraySubsegmento.length) {
            this.getSubsegmentos();
          }
        } else {
          this.formGroupFiltrosFields.selectSubsegmento.clearValidators();
          delete this.camposBuscar["SUBSEGMENTO"];

          if (this.formGroupFiltrosFields.selectSubsegmento.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectSubsegmento.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectSubsegmento.updateValueAndValidity();
        break;

      case "Clase de servicio":
        this.isCheckboxClaseServicioChecked = $event.checked;

        if (this.isCheckboxClaseServicioChecked) {
          this.formGroupFiltrosFields.selectClaseServicio.setValidators([Validators.required]);
          // this.camposBuscar["CLASE_SERVICIO"] = "";

          /* Permite que solo se haga la petición al backend si `arrayClaseServicio` está vacío */
          if (!this.arrayClaseServicio.length) {
            this.getClaseServicio();
          }
        } else {
          this.formGroupFiltrosFields.selectClaseServicio.clearValidators();
          delete this.camposBuscar["CLASE_SERVICIO"];

          if (this.formGroupFiltrosFields.selectClaseServicio.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectClaseServicio.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectClaseServicio.updateValueAndValidity();
        break;

      case "Ubicación":
        this.isCheckboxUbicacionChecked = $event.checked;

        if (this.isCheckboxUbicacionChecked) {
          this.formGroupFiltrosFields.selectUbicacion.setValidators([Validators.required]);
          // this.camposBuscar["UBICACION"] = "";

          /* Permite que solo se haga la petición al backend si `arrayUbicacion` está vacío */
          if (!this.arrayUbicacion.length) {
            this.getUbicacion();
          }
        } else {
          this.formGroupFiltrosFields.selectUbicacion.clearValidators();
          delete this.camposBuscar["UBICACION"];

          if (this.formGroupFiltrosFields.selectUbicacion.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectUbicacion.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectUbicacion.updateValueAndValidity();
        break;

      case "Circuito":
        this.isCheckboxCircuitoChecked = $event.checked;

        if (this.isCheckboxCircuitoChecked) {
          this.formGroupFiltrosFields.selectCircuito.setValidators([Validators.required]);
          // this.camposBuscar["CIRCUITO"] = "";

          /* Permite que solo se haga la petición al backend si `arrayCircuito` está vacío */
          if (!this.arrayCircuito.length) {
            this.getCircuito();
          }
        } else {
          this.formGroupFiltrosFields.selectCircuito.clearValidators();
          delete this.camposBuscar["CIRCUITO"];

          if (this.formGroupFiltrosFields.selectCircuito.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectCircuito.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectCircuito.updateValueAndValidity();
        break;

      case "Nodo":
        this.isCheckboxNodoChecked = $event.checked;

        if (this.isCheckboxNodoChecked) {
          this.formGroupFiltrosFields.selectNodo.setValidators([Validators.required]);
          // this.camposBuscar["NODO"] = "";

          /* Permite que solo se haga la petición al backend si `arrayNodo` está vacío */
          if (!this.arrayNodo.length) {
            this.getNodo();
          }
        } else {
          this.formGroupFiltrosFields.selectNodo.clearValidators();
          delete this.camposBuscar["NODO"];

          if (this.formGroupFiltrosFields.selectNodo.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectNodo.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectNodo.updateValueAndValidity();
        break;

      case "Municipio":
        this.isCheckboxMunicipioChecked = $event.checked;

        if (this.isCheckboxMunicipioChecked) {
          this.formGroupFiltrosFields.selectMunicipio.setValidators([Validators.required]);
          // this.camposBuscar["MUNICIPIO"] = "";

          /* Permite que solo se haga la petición al backend si `arrayMunicipio` está vacío */
          if (!this.arrayMunicipio.length) {
            this.getMunicipio();
          }
        } else {
          this.formGroupFiltrosFields.selectMunicipio.clearValidators();
          delete this.camposBuscar["MUNICIPIO"];

          if (this.formGroupFiltrosFields.selectMunicipio.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectMunicipio.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectMunicipio.updateValueAndValidity();
        break;

      case "Estrato":
        this.isCheckboxEstratoChecked = $event.checked;

        if (this.isCheckboxEstratoChecked) {
          this.formGroupFiltrosFields.selectEstrato.setValidators([Validators.required]);
          // this.camposBuscar["ESTRATO"] = "";

          /* Permite que solo se haga la petición al backend si `arrayEstrato` está vacío */
          if (!this.arrayEstrato.length) {
            this.getEstrato();
          }
        } else {
          this.formGroupFiltrosFields.selectEstrato.clearValidators();
          delete this.camposBuscar["ESTRATO"];

          if (this.formGroupFiltrosFields.selectEstrato.value) {
            inputHasValue = true;
            this.formGroupFiltrosFields.selectEstrato.setValue("");
          }
        }
        this.formGroupFiltrosFields.selectEstrato.updateValueAndValidity();
        break;

      // case "Número de cuenta":
      //   this.isCheckboxNumeroCuentaChecked = $event.checked;


      //   console.log(this.formDestinatarios.controls);

      //   if (this.isCheckboxNumeroCuentaChecked) {
      //     this.clearFormControlValidators(this.formDestinatarios.controls.formGroupFiltros as FormGroup);
      //     this.formGroupFiltrosFields.textAreaNumeroCuenta.setValidators([Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]);
      //   } else {
      //     this.formGroupFiltrosFields.textAreaNumeroCuenta.clearValidators();

      //     // if (this.formGroupFiltrosFields.textAreaNumeroCuenta.value) {
      //     //   inputHasValue = true;
      //     this.formGroupFiltrosFields.textAreaNumeroCuenta.setValue("");
      //     // }
      //   }
      //   this.formGroupFiltrosFields.textAreaNumeroCuenta.updateValueAndValidity();
      //   break;
    }

    if (inputHasValue) {
      /* LLAMAR A LA FUNCIÓN PARA ACTUALIZAR TOTAL DE USUARIOS */
      this.updateTotalUsuariosFiltro();
      // console.log("actualiar total");
    } else {
      // console.log("NO actualiar total");

    }

    // console.log(this.camposBuscar);
  }

  cuentasFiltradas: string[] = [];
  /** Cuenta la cantidad de cuentas agregadas por el usuario en el `textAreaNumeroCuenta` */
  countCantidadCuentas($event: any) {
    const textAreaValue: string = $event.target.value;
    /** @cuentas Toma los valores separados por coma y los agrega como un array */
    const cuentas: string[] = textAreaValue.split(",");
    /** @cuentasFiltradas Quita espacios a ambos lados de cada cuenta y filtra aquellas que tengan un valor booleano verdadero */
    this.cuentasFiltradas = cuentas.map(cuenta => cuenta.trim()).filter(Boolean);
    /** @totalCelularesValidosFiltro Muestra la cantidad de cuentas agregadas */
    this.totalCelularesValidosFiltro = this.cuentasFiltradas.length;
    // console.log(this.cuentasFiltradas)
  }

  validateStep(formGroup: FormGroup) {
    return this.ServiceProvider.validateStep(formGroup);
  }

  /** Lista los segmentos disponibles en la base de datos  */
  async getSegmentos() {
    const datosCampoABuscar = {
      camposBuscar: "SEGMENTO"
    };

    try {
      const segmentos = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arraySegmento = segmentos;
      this.filteredSegmento = this.formGroupFiltrosFields.selectSegmento.valueChanges
        .pipe(
          startWith(""),
          map(valorInput => this.filtrarDatosDestinatarios(valorInput, this.arraySegmento))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    }
  }

  /** Lista los subsegmentos disponibles en la base de datos  */
  async getSubsegmentos() {
    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "SUBSEGMENTO"
    };

    try {
      const subsegmentos = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      // this.arraySubsegmento = subsegmentos;
      this.arraySubsegmento = subsegmentos.map(subsegmento => {
        if (subsegmento.toLowerCase().startsWith("aliado estra")) {
          subsegmento = "Aliado Estratégico";
        } else if (subsegmento.toLowerCase().startsWith("rub")) {
          subsegmento = "Rubí";
        }
        return subsegmento;
      });

      this.filteredSubsegmento = this.formGroupFiltrosFields.selectSubsegmento.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arraySubsegmento))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Lista las clases de servicio disponibles en la base de datos  */
  async getClaseServicio() {
    // [...new Set(this.arrayClaseServicio.map(v => v.trim()))]

    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "CLASE_SERVICIO"
    };

    try {
      const claseServicio = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arrayClaseServicio = claseServicio;

      this.filteredClaseServicio = this.formGroupFiltrosFields.selectClaseServicio.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arrayClaseServicio))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Lista las ubicaciones disponibles en la base de datos  */
  async getUbicacion() {
    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "UBICACION"
    };

    try {
      const ubicaciones = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arrayUbicacion = ubicaciones.map(ubicacion => ubicacion === "R" ? "Rural" : "Urbano"
      );

      this.filteredUbicacion = this.formGroupFiltrosFields.selectUbicacion.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arrayUbicacion))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Lista los circuitos disponibles en la base de datos  */
  async getCircuito() {
    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "CIRCUITO"
    };

    try {
      const circuitos = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arrayCircuito = circuitos;

      this.filteredCircuito = this.formGroupFiltrosFields.selectCircuito.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arrayCircuito))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Lista los nodos disponibles en la base de datos  */
  async getNodo() {
    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "NODO"
    };

    try {
      const nodos = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arrayNodo = nodos;

      this.filteredNodo = this.formGroupFiltrosFields.selectNodo.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arrayNodo))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Lista los municipios disponibles en la base de datos  */
  async getMunicipio() {
    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "MUNICIPIO"
    };

    try {
      const municipios = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arrayMunicipio = municipios;

      this.filteredMunicipio = this.formGroupFiltrosFields.selectMunicipio.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arrayMunicipio))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Lista los estratos disponibles en la base de datos  */
  async getEstrato() {
    this.ServiceProvider.preloaderOn();
    const datosCampoABuscar = {
      camposBuscar: "ESTRATO"
    };

    try {
      const estratos = <string[]>await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_BD_CHEC, datosCampoABuscar);
      this.arrayEstrato = estratos;

      this.filteredEstrato = this.formGroupFiltrosFields.selectEstrato.valueChanges
        .pipe(
          startWith(""),
          map(value => this.filtrarDatosDestinatarios(value, this.arrayEstrato))
        );
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  /** Obtiene un array con los marcadores utilizados en el campo de texto de Mensaje
   * @param mensajeTextArea Texto escrito en el textAreaMensaje.
   * @returns Array de strings con los marcadores que representan un dato en la BD
   */
  getMarcadoresRealesTextArea(mensajeTextArea: string): string[] {
    const marcadoresUtilizados: string[] = mensajeTextArea.split(" ")
      .filter((word: string) => word.includes("@"))
      .map((marcador: string) => {
        marcador = marcador.slice(marcador.indexOf("@"));
        return this.ServiceProvider.removeCaracteresEspeciales(marcador);
      })
      .filter((marcador: string) => this.arrayMarcadores.includes(marcador));

    return Array.from(new Set(marcadoresUtilizados));
  }

  /** Celulares que se excluirán porque superan la cuota de mensajes de envío a clientes */
  arrayCelularesExcluidos: object[] = [];
  arrayMensajesConvertidos: object[] = [];
  arrayMensajesSuperioresMaxCaracteres: object[] = [];
  isSuperiorMaxCaracteres: boolean = false;
  /** @countMensajesSuperioresMaxCaracteres Cuenta los mensajes que superan los 160 caracteres */
  countMensajesSuperioresMaxCaracteres: number = 0;
  totalMensajesAEnviar: number = 0;
  totalCelularesValidos: number = 0;
  /** Total de teléfonos válidos de los mensajes que superan 160 caracteres */
  totalCelularesSuperioresValidos: number = 0;
  totalMensajesMax: number = 0;
  totalDefinitivoMensajes: number = 0;
  totalDefinitivoCelulares: number = 0;
  arrayTodosMensajes: object[];
  textAreaMensajeValue: string = "";

  async handleFormMensaje(formMensaje: FormGroup) {
    if (this.validateStep(formMensaje)) {
      this.ServiceProvider.preloaderOn();
      /* Limpiar el botón de radio para que funcione el evento (change) */
      this.formConfirmacionFields.radioMensajesSuperiores.setValue("");
      const tipoMensajeSeleccionado: string = this.formTipoMensaje.value.radioTipoMensaje;
      this.arrayTodosMensajes = [];
      this.arrayCelularesExcluidos = [];
      this.totalMensajesAEnviar = 0;
      this.totalCelularesValidos = 0;
      this.totalCelularesSuperioresValidos = 0;
      this.totalMensajesMax = 0;
      this.isSuperiorMaxCaracteres = false;
      this.countMensajesSuperioresMaxCaracteres = 0;
      this.totalDefinitivoMensajes = 0;
      /** `this.textAreaMensajeValue` Toma el mensaje escrito por el usuario, reemplazando posibles saltos de línea por espacios. */
      this.textAreaMensajeValue = String(formMensaje.value.textAreaMensaje).replace(/(\r\n|\n|\r)/gm, " ").trim();
      let mensajeConvertido: string = this.textAreaMensajeValue;

      /** @marcadoresUtilizados Recupera los marcadores escritos en el `textAreaMensaje` y los devuelve sin el caracter `@` */
      // const marcadoresUtilizados = this.getMarcadoresRealesTextArea(mensajeConvertido);

      /** @arrayMarcadoresTextArea Devuelve un array quitando los marcadores duplicados, si los hubiere */
      let arrayMarcadoresTextArea: string[] = [];
      /* Procesar @marcadores si se selecciona mensaje individual */
      if (this.formTipoMensajeFields.radioTipoMensaje.value === this.tiposMensaje[1]) {
        arrayMarcadoresTextArea = this.getMarcadoresRealesTextArea(mensajeConvertido);
      }

      /** Guarda un array con los teléfonos de los clientes que hayan superado la cuota de mensajes recibidos en un día */
      let celularesExcluir: any = [];

      try {
        const bolsaMensajes: BolsaMensajes = await this.ServiceProvider.post(WEBSERVICE.GET_BOLSA_MENSAJES_USUARIO, { nombreBolsa: this.datosUsuarioJWT.permisos.selectBolsa });
        this.valorMensajeIndividual = bolsaMensajes.valor_mensaje_unidireccional;
        let datosUsuariosPorFiltro: any;
        const datosCampoABuscar = {
          camposBuscar: this.camposBuscar,
          cantidad_usuarios: this.formDestinatariosFields.totalUsuarios.value
        };

        if (this.formDestinatariosFields.radioFiltro.value === "otrosCampos") {
          datosUsuariosPorFiltro = await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_USUARIOS_POR_FILTRO, datosCampoABuscar);
          // console.log("por filtro");
        } else {
          // console.log("por cuenta");
          datosUsuariosPorFiltro = await this.ServiceProvider.post(WEBSERVICE.GET_DATOS_USUARIOS_POR_CUENTA, { buscarCuentas: this.cuentasFiltradas });
        }


        /* Si el envío no es prioritario, se buscan los celulares de los clientes que hayan superado la cuota de mensajes recibidos para exluirlos del archivo cargado */
        if (!this.formTipoMensajeFields.envioPrioritario.value) {
          // console.log("no es prioritario");

          const cuotaMensajesCliente: any = await this.ServiceProvider.get(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE);
          const arrayCelulares: string[] = Array.from(new Set(datosUsuariosPorFiltro.map((datos: object) => datos["CELULAR"])));

          /** Construye el objeto para revisar en la colección `log_difusion_dod` a qué celulares se les ha enviado mensajes entre el periodo de inicio y fin definido por la cuota de mensajes para todos los clientes */
          const datosCelularesDod = {
            celulares: arrayCelulares,
            fechaInicioMensajesCliente: cuotaMensajesCliente.fechaInicioMensajesCliente,
            fechaFinMensajesCliente: cuotaMensajesCliente.fechaFinMensajesCliente,
          };

          celularesExcluir = await this.ServiceProvider.post(WEBSERVICE.GET_LOG_CELULARES_DOD_EXCLUIR, { datosCelularesDod });
        }

        /* Se filtran los datos del archivo cargado para eliminar posibles celulares duplicados y evitar el envío de dos o más mensajes a un mismo cliente. También, se exluyen de `dataArchivo`, los celulares que hayan superado la cuota de clientes para este día */
        datosUsuariosPorFiltro = Array.from(datosUsuariosPorFiltro)
          .filter((datosFila, indexFila, arrayDataArchivo) => {
            const celularBaseDatos = datosFila["CELULAR"];
            /** @isCelularUnico Retorna `true` si el celular no está repetido en los datos retornados por la base de datos; `false`, en caso contrario */
            const isCelularUnico: boolean = arrayDataArchivo.findIndex(dataFila => dataFila["CELULAR"] === celularBaseDatos) === indexFila;

            if (isCelularUnico) {
              if (!celularesExcluir.includes(String(celularBaseDatos))) {
                return true;
              }
              /* Si un celular del archivo está en el array `celularesExcluir`, este se descarta de `dataArchivo` y se guarda en `arrayCelularesExcluidos` */
              this.arrayCelularesExcluidos.push(celularBaseDatos);
            }
            return false;
          });



        // console.log("despes", arrayMarcadoresTextArea);
        Array.from(datosUsuariosPorFiltro).forEach((datosUsuario: any) => {
          // console.log(datosUsuario);
          arrayMarcadoresTextArea.forEach((marcador: string) => {
            /** @nombreCampoBD Obtiene el nombre del campo de la BD convirtiendo el marcador a mayúscula */
            let nombreCampoBD: string = marcador.toUpperCase();
            const marcadorConArroba = new RegExp(`@${marcador}`, "g");

            if (marcador === "cuenta") {
              nombreCampoBD = "NIU";
            } else if (marcador === "correo") {
              nombreCampoBD = "EMAIL";
            }
            /** @valorCampoBD Valor del campo en la BD en la clave `nombreCampoBD` */
            let valorCampoBD: string = datosUsuario[nombreCampoBD];

            if (marcador === "ubicacion") {
              if (valorCampoBD === "U") {
                valorCampoBD = "Urbano";
              } else if (valorCampoBD === "R") {
                valorCampoBD = "Rural";
              }
            }
            /* Se reemplaza el marcador `marcadorConArroba` con los datos del usuario */
            mensajeConvertido = mensajeConvertido.replace(marcadorConArroba, valorCampoBD);

          }); // Fin forEach arrayMarcadores
          // console.log(mensajeConvertido);

          let caracteresMensaje: number = mensajeConvertido?.length;
          let cantidadMensajesPorUsuario: number = 1;
          const celular: string = String(datosUsuario["CELULAR"]).trim();

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
            //   cuenta: datosUsuario["NIU"],
            //   nombre: datosUsuario["NOMBRE"],
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
            //   cuenta: datosUsuario["NIU"],
            //   nombre: datosUsuario["NOMBRE"],
            //   celular,
            //   mensaje: mensajeConvertido,
            //   cantidadCaracteres: mensajeConvertido?.length,
            //   cantidadMensajes: cantidadMensajesPorUsuario,
            // });
          }
          this.arrayTodosMensajes.push({
            cuenta: datosUsuario["NIU"],
            nombre: datosUsuario["NOMBRE"],
            celular,
            mensaje: mensajeConvertido,
            cantidadCaracteres: mensajeConvertido?.length,
            cantidadMensajes: cantidadMensajesPorUsuario,
          });
          /* Se reinicia `mensajeConvertido` para retomar los @marcadores en la siguiente iteración */
          mensajeConvertido = this.textAreaMensajeValue;
          this.totalDefinitivoMensajes = this.totalMensajesAEnviar;
        }); /* Fin `forEach()` */


        /** Retorna tanto los mensajes válidos como los que superan los 160 caracteres */
        // this.arrayTodosMensajes = [...this.arrayMensajesConvertidos, ...this.arrayMensajesSuperioresMaxCaracteres];

        // const cuotaMensajesCliente: any = await this.ServiceProvider.get(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE);

        // const arrayCelulares: string[] = this.arrayTodosMensajes.map(mensaje => mensaje["celular"]);

        /** Construye el objeto para revisar en la colección `log_difusion_dod` a qué celulares se les ha enviado mensajes entre el periodo de inicio y fin definido por la cuota de mensajes para todos los clientes */
        // const datosCelularesDod = {
        //   celulares: arrayCelulares,
        //   fechaInicioMensajesCliente: cuotaMensajesCliente.fechaInicioMensajesCliente,
        //   fechaFinMensajesCliente: cuotaMensajesCliente.fechaFinMensajesCliente,
        // };

        // const celularesExcluir: any = await this.ServiceProvider.post(WEBSERVICE.GET_LOG_CELULARES_DOD_EXCLUIR, { datosCelularesDod });

        // celularesExcluir.forEach((celularAExcluir: string) => {
        //   this.arrayTodosMensajes = this.arrayTodosMensajes.filter(mensaje => {
        //     if (mensaje["celular"] !== celularAExcluir) {
        //       return true;
        //     }
        //     this.arrayCelularesExcluidos.push(mensaje);

        //     if (mensaje["cantidadMensajes"] > 1) {
        //       this.totalMensajesMax -= mensaje["cantidadMensajes"];
        //       this.totalMensajesAEnviar -= mensaje["cantidadMensajes"];
        //       this.totalDefinitivoMensajes -= mensaje["cantidadMensajes"];
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

        this.dataSourceStepConfirmacion = new MatTableDataSource(this.arrayTodosMensajes);
        this.dataSourceStepConfirmacion.paginator = this.paginator;
        this.dataSourceStepConfirmacion.sort = this.sort;

        // console.log(this.arrayTodosMensajes);
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    }
  }

  // setMensajesAEnviar($event: MatRadioChange) {
  //   if ($event.value === "true") {
  //     this.totalDefinitivoMensajes = this.totalMensajesAEnviar;
  //     this.totalDefinitivoCelulares = this.totalCelularesSuperioresValidos + this.totalCelularesValidos;
  //   } else {
  //     this.totalDefinitivoMensajes -= this.totalMensajesMax;
  //     this.totalDefinitivoCelulares -= this.totalCelularesSuperioresValidos;
  //   }
  //   this.valorMensajesAEnviar = this.totalDefinitivoMensajes * this.valorMensajeIndividual;
  // }

  showAdvertenciaSendMensajes(formConfirmacion: FormGroup) {
    if (this.isSuperiorMaxCaracteres) {
      this.formConfirmacionFields.radioMensajesSuperiores.setValidators([Validators.required]);
    } else {
      this.formConfirmacionFields.radioMensajesSuperiores.clearValidators();
    }
    this.formConfirmacionFields.radioMensajesSuperiores.updateValueAndValidity();

    // console.log(formConfirmacion.valid);

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
        metodoEnvio: "Desde base de datos",
        tipoMensaje: this.formTipoMensaje.value.radioTipoMensaje,
        rawMensaje: String(this.formMensaje.value.textAreaMensaje).trim(),
        mensajes: {},
        valorMensajeIndividual: 0
      }
    };

    /* Si el mensaje a enviar es general */
    if (this.formTipoMensaje.value.radioTipoMensaje === this.tiposMensaje[0]) {
      // let cantidadMensajes: number = 1;
      // let caracteresMensaje: number = this.textAreaMensajeValue.length;

      // while (caracteresMensaje > this.maxCaracteresMensaje) {
      //   caracteresMensaje -= this.maxCaracteresMensaje;
      //   cantidadMensajes++;
      // }

      const filtro: string[] = ["cuenta", "nombre", "celular"];
      /** Filtra los mensajes a enviar y retorna un array de objetos con solo las llaves `cuenta`, `nombre` y `celular` */
      const datosUsuario: object[] = this.ServiceProvider.filtrarArray(mensajesAEnviar, filtro);

      const dataMensajeGeneral: object = {
        mensaje: this.textAreaMensajeValue,
        cantidadCaracteres: this.textAreaMensajeValue.length,
        /** Se toma la cantidad de mensajes del primer datos de `mensajesAEnviar` ya que al ser un mensaje general, es igual para todos los mensajes */
        cantidadMensajes: mensajesAEnviar[0]["cantidadMensajes"],
        datosUsuario
      };

      datosMensaje.mensajes.mensajes = dataMensajeGeneral;
    } else {
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
          /* Si el total de mensajes a enviar es menor o igual a la cantidad de mensajes disponible que tiene el usuario, enviar el mensaje */
          if (this.totalDefinitivoMensajes <= cuotaMensajesUsuario.cantidadMensajesUsuario) {
            datosMensaje.mensajes.valorMensajeIndividual = bolsaMensajes.valor_mensaje_unidireccional;
            datosMensaje.nombreBolsa = bolsaMensajes.nombre;

            /* Se envían los mensajes */
            await this.ServiceProvider.post(WEBSERVICE.SEND_MENSAJES_DOD, datosMensaje);

            const datosCuotaMensajes = {
              idCuota: cuotaMensajesUsuario._id.$oid,
              cantidadMensajesEnviados: this.totalDefinitivoMensajes
            };
            /* Se actualiza la cuota de mensajes de usuario */
            await this.ServiceProvider.post(WEBSERVICE.UPDATE_CUOTA_MENSAJES_USUARIO_ENVIO_SMS, datosCuotaMensajes);

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
