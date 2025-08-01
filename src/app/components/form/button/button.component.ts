import {Component, HostBinding, Input} from '@angular/core';
import {BaseInputComponent} from '../base-input.component';

@Component({
  selector: 'ub-button',
  imports: [
    BaseInputComponent
  ],
  templateUrl: './button.component.html',
  standalone: true,
  styleUrl: './button.component.scss'
})
export class ButtonComponent {

  @Input() type: 'button' | 'submit' | 'reset' = 'button';
  @Input() severity: 'primary' | 'danger' | 'success' | 'warning' | 'info' = 'primary';
  @Input() loading: boolean = false;
  @Input() disabled: boolean = false;
  @Input() icon: string | undefined; // Ícone opcional (ex: 'bx-check')

  @HostBinding('attr.disabled')
  get isDisabled(): boolean | null {
    return this.loading ? true : null;
  }

  get buttonClass(): string {
    return `btn btn-${this.severity}`;
  }

}
