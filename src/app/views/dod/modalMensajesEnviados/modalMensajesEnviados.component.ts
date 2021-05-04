import { Component, OnInit, ViewChild } from '@angular/core';
import { ModalDirective } from 'ngx-bootstrap';
import { Router } from '@angular/router';

@Component({
  selector: 'modal-mensajes-enviados',
  templateUrl: './modalMensajesEnviados.component.html',
})
export class ModalMensajesEnviadosComponent implements OnInit {
  @ViewChild("modalMensajesEnviados") modalMensajesEnviados: ModalDirective;

  constructor(private router: Router) { }

  ngOnInit() { }

  // onHide() {
  //   this.modalMensajesEnviados.onHide.subscribe(() => this.router.navigate(["dod"]));
  // }

  showModal() {
    this.modalMensajesEnviados.show();
    setTimeout(() => {
      /* Si después de 5 segundos el modal sigue presente, ocultarlo */
      if (this.modalMensajesEnviados.isShown) {
        this.hideModal();
      }
    }, 5000);
  }

  hideModal() {
    this.modalMensajesEnviados.hide();
    this.router.navigate(["dod"]);
  }

}
