import { NgModule } from "@angular/core";

import { CuentaComponent } from "./cuenta.component";
import { CuentaRoutingModule } from "./cuenta-routing.module";
import { ReactiveFormsModule, FormsModule } from "@angular/forms";
import { CommonModule } from "@angular/common";
import { MatTableModule } from "@angular/material/table";
import { MatPaginatorModule, MatPaginatorIntl } from "@angular/material/paginator";
import { MatPaginatorIntlEsp } from "../../../matPaginatorIntl";
import { MatButtonModule } from "@angular/material/button";
import { MatCheckboxModule } from "@angular/material/checkbox";
import { NgbModule } from "@ng-bootstrap/ng-bootstrap";

@NgModule({
  imports: [
    CommonModule,
    CuentaRoutingModule,
    ReactiveFormsModule,
    FormsModule,
    MatTableModule,
    MatPaginatorModule,
    MatButtonModule,
    MatCheckboxModule,
    NgbModule
  ],
  providers: [
    { provide: MatPaginatorIntl, useClass: MatPaginatorIntlEsp },
  ],
  declarations: [CuentaComponent]
})
export class CuentaModule { }
