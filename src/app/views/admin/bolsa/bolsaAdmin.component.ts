import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../../config/services";
import { MESSAGES } from '../../../config/messages';
import { WEBSERVICE } from "../../../config/webservices";
import { BolsaMensajesComponent } from "../../bolsaMensajes/bolsaMensajes.component";
import { JwtHelperService } from '@auth0/angular-jwt';
import { AuthenticationService } from '../../../config/authentication.service';
import { Users, BolsaMensajes } from '../../../config/interfaces';


@Component({
  selector: "bolsa-admin",
  templateUrl: "bolsaAdmin.component.html"
})

export class BolsaAdminComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  bolsaDineroMensajes: number = 0;
  formBolsas: FormGroup;
  formValorMensajeUnitario: FormGroup;
  formAdicionarSaldo: FormGroup;
  datosUsuarioJWT: Users = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data;
  bolsasMensaje: string[] = [];
  cantidadMensajesUnidireccionales: number = 0;
  cantidadMensajesBidireccionales: number = 0;

  constructor(
    private authenticationService: AuthenticationService,
    private ServiceProvider: ServiceProvider,
    private fb: FormBuilder,
    public jwtHelper: JwtHelperService,
  ) { }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.ServiceProvider.setTituloPestana("Administración - Bolsa de mensajes");
    this.setBolsasControls();
    this.setValorMensajeUnitarioControls();
    this.setAdicionarSaldoControls();

    this.getDatosBolsasMensajes().then(() => {
      this.formBolsasFields.nombreBolsa.setValue(this.bolsasMensaje[0]);
      this.getBolsaMensajesIndividual(this.formBolsasFields.nombreBolsa.value);
    }).finally(() => this.ServiceProvider.preloaderOff());
  }

  setBolsasControls() {
    this.formBolsas = this.fb.group({
      nombreBolsa: ["", Validators.required],
    });
  }

  setValorMensajeUnitarioControls() {
    this.formValorMensajeUnitario = this.fb.group({
      valorMensajeUnidireccional: ["", [Validators.required, Validators.min(1)]],
      valorMensajeBidireccional: ["", [Validators.required, Validators.min(1)]],
    });
  }

  setAdicionarSaldoControls() {
    this.formAdicionarSaldo = this.fb.group({
      valorSaldoAdicionar: ["", [Validators.required, Validators.min(1)]],
    });
  }

  get formBolsasFields() {
    return this.formBolsas.controls;
  }

  get formValorMensajeUnitarioFields() {
    return this.formValorMensajeUnitario.controls;
  }

  get formAdicionarSaldoFields() {
    return this.formAdicionarSaldo.controls;
  }

  /** Obtiene los datos de las bolsas de mensajes disponibles */
  async getDatosBolsasMensajes() {
    try {
      const datosBolsas = <BolsaMensajes[]>await this.ServiceProvider.get(WEBSERVICE.GET_DATOS_BOLSAS_MENSAJES);

      this.bolsasMensaje = datosBolsas.map(bolsa => bolsa.nombre);
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    }
  }

  async getBolsaMensajesIndividual(nombreBolsa: string) {
    this.ServiceProvider.preloaderOn();
    try {
      const bolsaMensajes: BolsaMensajes = await this.ServiceProvider.post(WEBSERVICE.GET_BOLSA_MENSAJES_USUARIO, { nombreBolsa });
      this.bolsaDineroMensajes = bolsaMensajes.valor_actual;
      const valorMensajeUnidireccional = bolsaMensajes.valor_mensaje_unidireccional;
      const valorMensajeBidireccional = bolsaMensajes.valor_mensaje_bidireccional;
      this.cantidadMensajesUnidireccionales = this.bolsaDineroMensajes / valorMensajeUnidireccional;
      this.cantidadMensajesBidireccionales = this.bolsaDineroMensajes / valorMensajeBidireccional;
      this.formValorMensajeUnitarioFields.valorMensajeUnidireccional.setValue(valorMensajeUnidireccional);
      this.formValorMensajeUnitarioFields.valorMensajeBidireccional.setValue(valorMensajeBidireccional);
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  async updateValorMensajeUnitarioBolsa(nombreBolsa: string) {
    if (this.formValorMensajeUnitario.valid) {
      this.ServiceProvider.preloaderOn();
      try {
        const datosValorMensajeUnitario = {
          idUsuario: this.datosUsuarioJWT._id.$oid,
          nombreBolsa,
          valorMensajeUnidireccional: this.formValorMensajeUnitario.value.valorMensajeUnidireccional,
          valorMensajeBidireccional: this.formValorMensajeUnitario.value.valorMensajeBidireccional
        };
        await this.ServiceProvider.post(WEBSERVICE.UPDATE_VALOR_MENSAJE_UNITARIO_BOLSA, datosValorMensajeUnitario);

        this.getBolsaMensajesIndividual(nombreBolsa);
        this.ServiceProvider.openToast("success", MESSAGES.actualizadoExito);
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      }
      // finally {
      //   this.ServiceProvider.preloaderOff();
      // }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formValorMensajeUnitario);
    }
  }

  async adicionarSaldoBolsa(nombreBolsa: string) {
    if (this.formAdicionarSaldo.valid) {
      this.ServiceProvider.preloaderOn();
      try {
        const datosAdicionarSaldo = {
          idUsuario: this.datosUsuarioJWT._id.$oid,
          nombreBolsa,
          valorSaldoAdicionar: this.formAdicionarSaldo.value.valorSaldoAdicionar
        };

        const saldoBolsaAdicionar = await this.ServiceProvider.post(WEBSERVICE.ADICIONAR_SALDO_BOLSA, datosAdicionarSaldo);

        this.getBolsaMensajesIndividual(nombreBolsa);
        this.ServiceProvider.openToast("success", MESSAGES.saldoAdicionadoExito);
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      }
      // finally {
      //   this.ServiceProvider.preloaderOff();
      // }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formAdicionarSaldo);
    }
  }
}
