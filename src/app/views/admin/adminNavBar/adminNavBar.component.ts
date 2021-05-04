import { Component } from "@angular/core";


@Component({
  selector: "adminNavBar",
  templateUrl: "adminNavBar.component.html"
})

export class AdminNavBarComponent {
  rutas: string[] = ["usuarios", "bolsa"];
}
