import { Component } from '@angular/core';
import {Toast, ToastService} from './toast.service';
import {AsyncPipe} from '@angular/common';

@Component({
  selector: 'ub-toast',
  imports: [
    AsyncPipe
  ],
  templateUrl: './toast.component.html',
  standalone: true,
  styleUrl: './toast.component.scss'
})
export class ToastComponent {

  constructor(public toastService: ToastService) {}

  // Retorna a classe CSS com base no tipo de toast
  getToastClass(toast: Toast): string {
    return `toast toast-${toast.type}`;
  }

  // Pausa o desaparecimento do Toast quando o mouse está sobre ele
  pauseToast(toast: Toast): void {
    toast.isPaused = true;
    clearTimeout(toast.timeoutId); // Cancela o timeout atual
  }

  // Retoma o desaparecimento do Toast quando o mouse sai dele
  resumeToast(toast: Toast): void {
    toast.isPaused = false;
    toast.timeoutId = setTimeout(() => {
      this.toastService.removeToast(toast);
    }, toast.duration || 3000); // Reinicia o timeout
  }

}
