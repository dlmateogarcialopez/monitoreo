import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ListadoUsuariosComponent } from './usuarios/listadoUsuarios.component';
import { BolsaAdminComponent } from './bolsa/bolsaAdmin.component';
import { AddUsuarioComponent } from './usuarios/addUsuario.component';

const routes: Routes = [
  {
    path: "",
    data: {
      title: "Administración"
    },
    children: [
      {
        path: "",
        redirectTo: "usuarios",
        pathMatch: "full"
      },
      {
        path: "usuarios",
        component: ListadoUsuariosComponent,
        data: {
          title: "Usuarios"
        },
      },
      {
        path: "add-usuario",
        component: AddUsuarioComponent,
        data: {
          title: "Agregar usuario"
        }
      },
      {
        path: "add-usuario/:idUsuario",
        component: AddUsuarioComponent,
        data: {
          title: "Editar usuario"
        }
      },
      {
        path: "bolsa",
        component: BolsaAdminComponent,
        data: {
          title: "Bolsa de mensajes"
        }
      },
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class AdminRoutingModule { }
