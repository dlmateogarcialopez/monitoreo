import { Injectable } from "@angular/core";
import { Router, CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, CanActivateChild } from "@angular/router";
import { AuthenticationService } from "./authentication.service";
import { map, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { Users } from './interfaces';


@Injectable({ providedIn: "root" })
export class AuthGuard implements CanActivate, CanActivateChild {
  constructor(
    private router: Router,
    private authenticationService: AuthenticationService,
  ) { }

  canActivate(ruta: ActivatedRouteSnapshot, estado: RouterStateSnapshot) {
    return this.authenticationService.getDatosUsuario()
      .pipe(
        map((datos: Users) => {
          if (datos.permisos) {
            const permisosUsuario = Object.entries(datos.permisos);
            let isUsuarioAutorizado: boolean = false;

            /* Si la `ruta` requiere roles, se recorren los permisos del usuario */
            if (ruta.data.roles) {
              for (const [clavePermisoBack, valorPermisoBack] of permisosUsuario) {
                /* Si el `valorPermisoBack` es `true` y la `clavePermisoBack` se encuentra en el parámetro `roles` de la `ruta` (*.routing.ts), se permitirá el acceso a la misma */
                if (valorPermisoBack && ruta.data.roles.indexOf(clavePermisoBack) !== -1) {
                  isUsuarioAutorizado = true;
                  break;
                }
              }
            } else {
              /* Si la `ruta` no requiere roles, se autoriza el acceso */
              isUsuarioAutorizado = true;
            }
            return isUsuarioAutorizado;
          }
          /* Cuando el usuario no ha iniciado sesión, redirigir a login */
          localStorage.clear();
          this.router.navigate(["/login"], { queryParams: { returnUrl: estado.url } });
          return false;
        }),
        catchError(error => of(false))
      );
  }

  canActivateChild(ruta: ActivatedRouteSnapshot, estado: RouterStateSnapshot) {
    return this.canActivate(ruta, estado);
  }
}