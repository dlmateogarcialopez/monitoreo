import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";
import { AuthGuard } from '../../config/auth.guard';
import { Roles } from '../../config/Roles';
import { ListadoReglasDifusionComponent } from './reglasDifusion/listadoReglasDifusion.component';

const routes: Routes = [
  {
    path: "",
    data: {
      title: "Difusión interrupciones no programadas"
    },
    children: [
      {
        path: "",
        redirectTo: "dinp"
      },
      {
        path: "dinp",
        component: ListadoReglasDifusionComponent,
        data: {
          title: "Reglas difusión"
        }
      },
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DinpRoutingModule { }
