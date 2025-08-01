import { Component, EventEmitter, HostBinding, Input, Output } from '@angular/core';
import { BaseInputComponent } from '../base-input.component';

export interface Action {
  label: string; // Rótulo da ação (ex: "Exportar em PDF")
  icon?: string; // Ícone opcional (ex: "bx-file")
  handler?: () => void; // Função personalizada para a ação
}

@Component({
  selector: 'ub-actionbutton',
  imports: [BaseInputComponent],
  templateUrl: './actionbutton.component.html',
  standalone: true,
  styleUrl: './actionbutton.component.scss',
})
export class ActionButtonComponent {
  @Input() severity: 'primary' | 'danger' | 'success' | 'warning' | 'info' = 'primary';
  @Input() loading: boolean = false;
  @Input() disabled: boolean = false;
  @Input() icon: string | undefined; // Ícone opcional (ex: 'bx-check')
  @Input() actions: Action[] = []; // Lista de ações
  @Output() actionSelected = new EventEmitter<Action>(); // Evento ao selecionar uma ação

  isOpen: boolean = false; // Estado do menu de ações

  @HostBinding('attr.disabled')
  get isDisabled(): boolean | null {
    return this.loading ? true : null;
  }

  get buttonClass(): string {
    return `btn btn-${this.severity} ${this.isOpen ? 'open' : ''}`;
  }

  // Alterna a visibilidade do menu de ações
  toggleActions(): void {
    if (!this.disabled && !this.loading) {
      this.isOpen = !this.isOpen;
    }
  }

  // Executa a ação selecionada
  handleAction(action: Action): void {
    if (action.handler) {
      action.handler();
    }
    this.actionSelected.emit(action);
    this.isOpen = false; // Fecha o menu após a seleção
  }
}
