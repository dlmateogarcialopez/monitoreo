import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../config/services";
import { MESSAGES } from '../../config/messages';
import { WEBSERVICE } from "../../config/webservices";
import { Router, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'recuperar_cuenta',
  templateUrl: 'recuperarCuenta.component.html'
})

export class RecuperarCuentaComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  formRecuperarCuenta: FormGroup;
  isEmailRegistered: boolean = true;
  isEmailSent: boolean = false;
  msgEmailNotFound: string = "";
  correoRecuperacion: string = "";
  isLinkInvalidFromNuevoPassword: boolean = false;

  constructor(
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    private route: ActivatedRoute
  ) {
    const navegacion = this.router.getCurrentNavigation();
    const estadoNavegacion = navegacion.extras.state;
    /* estadoNavegacion.isLinkInvalidFromNuevoPassword proviene de NuevoPasswordComponent y se utiliza para mostrar en el template mensaje de error cuando el enlace para resetear el password no es válido */
    if (estadoNavegacion && estadoNavegacion.isLinkInvalidFromNuevoPassword) {
      this.isLinkInvalidFromNuevoPassword = true;
    }
  }

  ngOnInit() {
    this.ServiceProvider.setTituloPestana("Recuperar cuenta");
    this.correoRecuperacion = this.route.snapshot.queryParamMap.get("correo");
    this.setRecuperarCuentaControls();
  }

  setRecuperarCuentaControls() {
    this.formRecuperarCuenta = this.fb.group({
      correo: this.fb.control(this.correoRecuperacion, [Validators.required, Validators.email]),
    });

    // this.correo.setValue(this.correoRecuperacion);
  }

  get correo() {
    return this.formRecuperarCuenta.get("correo");
  }


  async recuperarCuenta() {
    if (this.formRecuperarCuenta.valid) {
      this.ServiceProvider.preloaderOn();
      this.isLinkInvalidFromNuevoPassword = false;

      try {
        const datosCorreo: any = await this.ServiceProvider.post(WEBSERVICE.GET_CORREO_USUARIO, { correo: this.correo.value });

        if (datosCorreo instanceof Object) {
          /* El usuario se encuentra registrado. Se intenta enviar mensaje al correo */
          const datosUsuario = {
            idUsuario: datosCorreo.id.$oid,
            correo: datosCorreo.correo,
            hashId: datosCorreo.hashId
          };

          const correoEnviado: any = await this.ServiceProvider.post(WEBSERVICE.ENVIAR_CORREO_RECUPERACION_CUENTA, datosUsuario);

          /* Si el mensaje fue enviado */
          if (correoEnviado === "ok") {
            this.isEmailRegistered = true;
            this.isEmailSent = true;
          } else {
            this.ServiceProvider.openPopup("error", correoEnviado);
          }
        } else {
          /* El usuario no se encuentra registrado */
          this.isEmailRegistered = false;
          this.msgEmailNotFound = datosCorreo;
        }
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }



      // setTimeout(() => {

      // }, 1000);


      // this.router.navigate(["usuarios"])
    } else {
      this.ServiceProvider.validateAllFormFields(this.formRecuperarCuenta);
    }
  }
}
