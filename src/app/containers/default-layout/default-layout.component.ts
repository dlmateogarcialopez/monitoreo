import { Component, OnInit } from '@angular/core';
import { navItems } from '../../_nav';
import { AuthenticationService } from '../../config/authentication.service';
import { ServiceProvider } from "../../config/services";
import { WEBSERVICE } from '../../config/webservices';
import { JwtHelperService } from '@auth0/angular-jwt';
import { Subject } from 'rxjs';
import { PermisosUsuario } from '../../config/interfaces';

@Component({
  selector: 'app-dashboard',
  templateUrl: './default-layout.component.html'
})
export class DefaultLayoutComponent implements OnInit {
  public sidebarMinimized = true;
  public navItems = navItems;
  currentYear: number = new Date().getFullYear();
  usuarioActual: any = {};
  permisosUsuario: PermisosUsuario;
  subjectMensajesEnviados: Subject<void> = new Subject<void>();
  isPorcentajeMensajesValido: boolean;

  constructor(
    private authenticationService: AuthenticationService,
    private ServiceProvider: ServiceProvider,
    public jwtHelper: JwtHelperService
  ) { }

  checkPorcentajeEnviosUsuario(porcentajeMensajesEnviados: number) {
    this.isPorcentajeMensajesValido = Number.isInteger(porcentajeMensajesEnviados);
  }

  ngOnInit() {
    this.usuarioActual = this.authenticationService.usuarioActualValue;
    this.permisosUsuario = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken()).data.permisos;
    this.hideElementosNavegacion(["Reportes", "Monitoreo"], this.permisosUsuario.monitoreoVerReportes);
    this.hideElementosNavegacion(["Difusión", "Bajo demanda"], false); // AOD Eliminar cuando se habilite DOD en producción
    this.getFechaActual();

  }

  toggleMinimize(e) {
    this.sidebarMinimized = e;
  }

  updateVistaMensajesEnviados() {
    this.subjectMensajesEnviados.next();
  }

  /** Oculta elementos del panel de navegación de la izquierda si el nombre del elemento está presente en `nombresElementosNav` y el permiso del usuario (`permisoUsuario`) es `false`
   * @param nombresElementosNav Array con los nombres de los elementos de navegación a ocultar
   * @param permisoUsuario Valor del permiso que tiene el usuario
  */
  hideElementosNavegacion(nombresElementosNav: string[], permisoUsuario: boolean) {
    navItems.forEach(item => {
      if (nombresElementosNav.includes(item.name) && !permisoUsuario) {
        item.attributes = { hidden: true };
      }
    });
  }

  /** Obtiene el año actual desde el servidor para evitar mostrar un año erróneo cuando el usuario modifica el año de su dispositivo */
  async getFechaActual() {
    try {
      this.currentYear = <number>await this.ServiceProvider.get(WEBSERVICE.GET_FECHA_ACTUAL);
    } catch (error) {
      console.error(error);
    }
  }

  async logout() {
    this.ServiceProvider.preloaderOn();
    try {
      await this.authenticationService.logout();
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }
}
