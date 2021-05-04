import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";
import { ListadoEnviosComponent } from "./listadoEnvios.component";
import { EnviarDesdeArchivoComponent } from './enviarDesdeArchivo/enviarDesdeArchivo.component';
import { EnviarDesdeBdComponent } from './enviarDesdeBd/enviarDesdeBd.component';
import { DetalleEnvioDodComponent } from './detalleEnvio/detalleEnvioDod.component';
import { AuthGuard } from '../../config/auth.guard';
import { Roles } from '../../config/Roles';

const routes: Routes = [
  {
    path: "",
    data: {
      title: "Difusión bajo demanda"
    },
    children: [
      {
        path: "",
        redirectTo: "dod"
      },
      {
        path: "dod",
        component: ListadoEnviosComponent,
        data: {
          title: "Envíos"
        }
      },
      {
        path: "enviar-archivo",
        component: EnviarDesdeArchivoComponent,
        canActivate: [AuthGuard],
        data: {
          title: "Enviar desde archivo",
          roles: [Roles.dodEnviarSms]
        }
      },
      {
        path: "enviar-bd",
        component: EnviarDesdeBdComponent,
        canActivate: [AuthGuard],
        data: {
          title: "Enviar desde base de datos",
          roles: [Roles.dodEnviarSms]
        }
      },
      {
        path: "detalle-envio-dod/:idEnvio",
        component: DetalleEnvioDodComponent,
        canActivate: [AuthGuard],
        data: {
          title: "Detalles del envío",
          roles: [Roles.dodVerReportes]
        }
      },
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DodRoutingModule { }
