import {Component, Input, ViewEncapsulation} from '@angular/core';

@Component({
  selector: 'ub-base-input',
  standalone: true,
  imports: [],
  template: `
    <div class="input-container">
      @if (label) {
        <label>{{ label }}</label>
      }
      <ng-content></ng-content> <!-- Aqui o input específico será projetado -->
      @if (helpText) {
        <small class="help">{{ helpText }}</small>
      }
      @if (error) {
        <div class="feedback error">{{ error }}</div>
      } @else if (success) {
        <div class="feedback success">{{ success }}</div>
      }
    </div>
  `,
  styleUrls: ['base-input.component.scss'], // Usando o SCSS global
  encapsulation: ViewEncapsulation.None
})
export class BaseInputComponent {
  @Input() label: string = '';
  @Input() helpText: string = '';
  @Input() error: string = '';
  @Input() success: string = '';
}
