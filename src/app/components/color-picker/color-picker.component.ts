import {Component, forwardRef, Input} from '@angular/core';
import {ControlValueAccessor, NG_VALUE_ACCESSOR, FormsModule} from '@angular/forms';
import {CommonModule} from '@angular/common';

@Component({
  selector: 'ub-color-picker',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="color-picker-field">
      <label *ngIf="label" class="field-label">
        {{ label }}
        <span *ngIf="required" class="required-indicator">*</span>
      </label>

      <div class="color-picker-container">
        <div class="color-preview" [style.background-color]="value" (click)="colorInput.click()">
          <span class="color-value">{{ value || '#000000' }}</span>
        </div>

        <input
          #colorInput
          type="color"
          [value]="value || '#000000'"
          (input)="onColorChange($event)"
          [disabled]="disabled"
          class="color-input"
        />

        <input
          type="text"
          [value]="value || '#000000'"
          (input)="onTextChange($event)"
          [disabled]="disabled"
          [placeholder]="placeholder"
          class="color-text-input"
          maxlength="7"
        />
      </div>

      <small *ngIf="helpText" class="help-text">
        <i class="pi pi-info-circle"></i> {{ helpText }}
      </small>

      <small *ngIf="error" class="error-text">
        <i class="pi pi-exclamation-circle"></i> {{ error }}
      </small>

      <div *ngIf="showPresets" class="color-presets">
        <button
          *ngFor="let preset of presetColors"
          type="button"
          class="preset-color"
          [style.background-color]="preset"
          [title]="preset"
          (click)="selectPreset(preset)"
          [disabled]="disabled">
        </button>
      </div>
    </div>
  `,
  styles: [`
    .color-picker-field {
      margin-bottom: 1rem;
    }

    .field-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: #495057;
      font-size: 0.875rem;
    }

    .required-indicator {
      color: #dc3545;
      margin-left: 2px;
    }

    .color-picker-container {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .color-preview {
      width: 50px;
      height: 38px;
      border: 2px solid #ced4da;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: border-color 0.2s;
      position: relative;
    }

    .color-preview:hover {
      border-color: #80bdff;
    }

    .color-preview .color-value {
      font-size: 0.7rem;
      font-weight: 600;
      color: white;
      text-shadow: 0 0 3px rgba(0,0,0,0.8);
      pointer-events: none;
    }

    .color-input {
      opacity: 0;
      position: absolute;
      pointer-events: none;
    }

    .color-text-input {
      flex: 1;
      height: 38px;
      padding: 0.5rem;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-family: monospace;
      font-size: 0.875rem;
      transition: border-color 0.2s;
    }

    .color-text-input:focus {
      outline: none;
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .color-text-input:disabled {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    .help-text {
      display: block;
      margin-top: 0.25rem;
      color: #6c757d;
      font-size: 0.875rem;
    }

    .help-text i {
      color: #17a2b8;
    }

    .error-text {
      display: block;
      margin-top: 0.25rem;
      color: #dc3545;
      font-size: 0.875rem;
    }

    .color-presets {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-top: 0.75rem;
      padding-top: 0.75rem;
      border-top: 1px solid #e9ecef;
    }

    .preset-color {
      width: 32px;
      height: 32px;
      border: 2px solid #dee2e6;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .preset-color:hover:not(:disabled) {
      border-color: #495057;
      transform: scale(1.1);
    }

    .preset-color:disabled {
      cursor: not-allowed;
      opacity: 0.5;
    }
  `],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ColorPickerComponent),
      multi: true
    }
  ]
})
export class ColorPickerComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() required: boolean = false;
  @Input() disabled: boolean = false;
  @Input() placeholder: string = '#000000';
  @Input() helpText: string = '';
  @Input() error: string = '';
  @Input() showPresets: boolean = true;

  value: string = '#000000';

  presetColors = [
    '#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0',
    '#546E7A', '#26a69a', '#D10CE8', '#F86624', '#EA5545',
    '#4472C4', '#70AD47', '#FFC000', '#C00000', '#7030A0'
  ];

  onChange: any = () => {};
  onTouched: any = () => {};

  onColorChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.value = input.value;
    this.onChange(this.value);
    this.onTouched();
  }

  onTextChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    let color = input.value.trim();

    // Adiciona # se não tiver
    if (color && !color.startsWith('#')) {
      color = '#' + color;
    }

    // Valida formato hex
    if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
      this.value = color;
      this.onChange(this.value);
      this.onTouched();
    }
  }

  selectPreset(color: string): void {
    if (this.disabled) return;
    this.value = color;
    this.onChange(this.value);
    this.onTouched();
  }

  writeValue(value: string): void {
    this.value = value || '#000000';
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }
}
