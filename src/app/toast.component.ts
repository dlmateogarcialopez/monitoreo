import { Component, TemplateRef } from "@angular/core";
import { ToastService } from "./config/toast-service";

@Component({
  selector: "app-toasts",
  template: `
    <ngb-toast
      *ngFor="let toast of toastService.toasts"
      [class]="toast.classname"
      [autohide]="true"
      [delay]="toast.delay"
      (hide)="toastService.remove(toast)"
    >
      <ng-template [ngIf]="isTemplate(toast)" [ngIfElse]="text">
      <ng-template [ngTemplateOutlet]="toast.textOrTpl"></ng-template>
      </ng-template>
      <ng-template #text>
        <span class="mr-2">
          <i class="{{ toast.icon }} fas mr-1"></i>
          {{ toast.textOrTpl }}
        </span>
        <button
          type="button"
          class="close text-white"
          style="text-shadow: none; opacity: 0.8;"
          aria-label="Close"
          (click)="toastService.remove(toast)">
          <span aria-hidden="true">&times;</span>
        </button>
      </ng-template>
    </ngb-toast>
  `,
  host: { "[class.ngb-toasts]": "true" }
})
export class ToastsContainer {
  constructor(public toastService: ToastService) { }

  isTemplate(toast: any) {
    return toast.textOrTpl instanceof TemplateRef;
  }
}
