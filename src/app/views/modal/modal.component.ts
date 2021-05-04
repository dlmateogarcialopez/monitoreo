import { Component, Input, OnInit } from '@angular/core';
import { NgbModal, NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';


@Component({
  selector: 'ngbd-modal-content',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.css']
})
export class ModalComponent implements OnInit {
  @Input() titulo: string;
  @Input() cuerpo: string;
  @Input() funcion: any;
  @Input() param: any;
  @Input() estilo: any;
  @Input() component: any;


  constructor(
    public activeModal: NgbActiveModal
  ) { }


  ngOnInit() {
  }

  funcion_ok() {
    this.component[this.funcion](this.param);
  }

}