import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";

// Import Containers
import { DefaultLayoutComponent } from "./containers";

// Import components
import { LoginComponent } from "./views/login/login.component";
import { RecuperarCuentaComponent } from "./views/recuperarCuenta/recuperarCuenta.component";
import { NuevoPasswordComponent } from "./views/nuevoPassword/nuevoPassword.component";
import { AuthGuard } from "./config/auth.guard";
import { Roles } from './config/Roles';
import { P404Component } from './views/error/404.component';

export const routes: Routes = [
  {
    path: "",
    redirectTo: "monitoreo",
    pathMatch: "full",
  },
  {
    path: "login",
    component: LoginComponent,
    data: {
      title: "Login Page"
    }
  },
  {
    path: "recuperarCuenta",
    component: RecuperarCuentaComponent,
  },
  // {
  //   path: "nuevoPassword",
  //   component: NuevoPasswordComponent,
  // },
  {
    path: "nuevoPassword/:tokenUsuario",
    component: NuevoPasswordComponent,
  },
  {
    path: "",
    component: DefaultLayoutComponent,
    // canActivate: [AuthGuard],
    data: {
      title: "Inicio"
    },
    children: [
      {
        path: "admin",
        loadChildren: () => import("./views/admin/admin.module").then(m => m.AdminModule),
        canActivate: [AuthGuard],
        data: {
          /* Roles que tienen acceso a esta ruta. Se comparan en `AuthGuard` con los permisos de cada usuario */
          roles: [Roles.administrador]
        }
      },
      {
        path: "cuenta",
        loadChildren: () => import("./views/cuenta/cuenta.module").then(m => m.CuentaModule),
        canActivate: [AuthGuard],
      },
      {
        path: "dod",
        loadChildren: () => import("./views/dod/dod.module").then(m => m.DodModule),
        canActivate: [AuthGuard],
        data: {
          roles: [Roles.administrador] // AOD Eliminar cuando se habilite DOD en producción
        }
      },
      {
        path: "dinp",
        loadChildren: () => import("./views/dinp/dinp.module").then(m => m.DinpModule),
        canActivate: [AuthGuard],
        data: {
          roles: [Roles.administrador] // AOD Eliminar cuando se habilite DOD en producción
        }
      },
      {
        path: "monitoreo",
        loadChildren: () => import("./views/monitoreo/monitoreo.module").then(m => m.MonitoreoModule),
        canActivate: [AuthGuard],
        data: {
          roles: [Roles.monitoreoVerReportes]
        }
      }
    ]
  },
  { path: "**", component: P404Component }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
