import {Component, forwardRef, Input} from '@angular/core';
import {ControlValueAccessor, NG_VALUE_ACCESSOR, FormsModule} from '@angular/forms';
import {CommonModule} from '@angular/common';
import {InputComponent} from '../form/input/input.component';
import {ButtonComponent} from '../form/button/button.component';

interface SeriesItem {
  name: string;
  column: string;
  color: string;
}

@Component({
  selector: 'ub-series-config',
  standalone: true,
  imports: [CommonModule, FormsModule, InputComponent],
  template: `
    <div class="series-config-field">
      <div class="series-config-header">
        @if (label) {
          <label class="field-label">
            {{ label }}
            @if (required) {
              <span class="required-indicator">*</span>
            }
          </label>
        }

        <button
          type="button"
          class="btn-add"
          (click)="addSeries()"
          [disabled]="disabled"
          title="Adicionar série">
          <i class="bx bx-plus"></i>
        </button>
      </div>

      @if (helpText) {
        <small class="help-text mb-2">
          <i class="pi pi-info-circle"></i> {{ helpText }}
        </small>
      }

      <div class="series-list">

        @for (series of seriesList; let i = $index; let last = $last; track i) {
          <div class="series-item">
            <div class="series-header">
              <span class="series-number">Série {{ i + 1 }}</span>
              <div class="series-actions">
                <button
                  type="button"
                  class="btn-remove"
                  (click)="removeSeries(i)"
                  [disabled]="disabled"
                  title="Remover série">
                  <i class="bx bx-trash"></i>
                </button>
                @if (last) {
                  <button
                    type="button"
                    class="btn-add"
                    (click)="addSeries()"
                    [disabled]="disabled"
                    title="Adicionar série">
                    <i class="bx bx-plus"></i>
                  </button>
                }
              </div>
            </div>

            <div class="series-fields">
              <div class="field-group">
                <ub-input
                  label="Nome da Série"
                  [(ngModel)]="series.name"
                  (ngModelChange)="onSeriesChange()"
                  [disabled]="disabled"
                  placeholder="Ex: Vendas, Receita, etc"
                />
              </div>

              <div class="field-group">
                <ub-input
                  label="Coluna de Dados"
                  [(ngModel)]="series.column"
                  (ngModelChange)="onSeriesChange()"
                  [disabled]="disabled"
                  placeholder="Ex: valor, quantidade"
                />
              </div>

              <div class="field-group">
                <div class="color-picker-inline">
                  <ub-input
                    label="Cor"
                    [(ngModel)]="series.color"
                    (ngModelChange)="onSeriesChange()"
                    [disabled]="disabled"
                    placeholder="#000000"
                    maxlength="7"
                  />
                </div>
              </div>
            </div>
          </div>
        }

        @if (seriesList.length === 0) {
          <div class="empty-state">
            <i class="pi pi-chart-bar text-3xl text-gray-400 mb-2"></i>
            <p class="text-gray-500">Nenhuma série configurada</p>
            <p class="text-gray-400 text-sm">Clique no + para começar</p>
          </div>
        }
      </div>

      @if (error) {
        <small class="error-text">
          <i class="pi pi-exclamation-circle"></i> {{ error }}
        </small>
      }
    </div>
  `,
  styles: [`

    .series-config-field {
      margin-bottom: 1rem;
    }

    .series-config-header {
      display: flex;
      justify-content: space-between;
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

    .help-text {
      display: block;
      color: #6c757d;
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
    }

    .help-text i {
      color: #17a2b8;
    }

    .series-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .series-item {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 1rem;
      transition: box-shadow 0.2s;
    }

    .series-item:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .series-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #dee2e6;
    }

    .series-number {
      font-weight: 600;
      color: #495057;
      font-size: 0.875rem;
    }

    .series-actions {
      display: flex;
      gap: 8px;
    }

    .btn-remove {
      background: transparent;
      border: 1px solid #dc3545;
      color: #dc3545;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.875rem;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .btn-remove:hover:not(:disabled) {
      background: #dc3545;
      color: white;
    }

    .btn-remove:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-add {
      background: transparent;
      border: 1px solid #28a745;
      color: #28a745;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.875rem;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .btn-add:hover:not(:disabled) {
      background: #28a745;
      color: white;
    }

    .btn-add:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .series-fields {
      display: grid;
      grid-template-columns: 1fr 1fr 120px;
      gap: 0.75rem;
    }

    @media (max-width: 768px) {
      .series-fields {
        grid-template-columns: 1fr;
      }
    }

    .field-group {
      display: flex;
      flex-direction: column;
    }

    .field-label-small {
      font-size: 0.75rem;
      font-weight: 500;
      color: #6c757d;
      margin-bottom: 0.25rem;
    }

    .field-input {
      padding: 0.5rem;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 0.875rem;
      transition: border-color 0.2s;
    }

    .field-input:focus {
      outline: none;
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .field-input:disabled {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    .color-picker-inline {
      display: flex;
      gap: 0.25rem;
      align-items: center;
    }

    .color-input-inline {
      width: 40px;
      height: 38px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      cursor: pointer;
    }

    .color-text-inline {
      flex: 1;
      padding: 0.5rem;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 0.75rem;
      font-family: monospace;
    }

    .color-text-inline:focus {
      outline: none;
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6c757d;

      ub-button {
        margin-top: 10px;
      }
    }

    .error-text {
      display: block;
      margin-top: 0.5rem;
      color: #dc3545;
      font-size: 0.875rem;
    }
  `],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => SeriesConfigComponent),
      multi: true
    }
  ]
})
export class SeriesConfigComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() required: boolean = false;
  @Input() disabled: boolean = false;
  @Input() helpText: string = '';
  @Input() error: string = '';

  seriesList: SeriesItem[] = [];

  private defaultColors = [
    '#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0',
    '#546E7A', '#26a69a', '#D10CE8'
  ];

  onChange: any = () => {
  };
  onTouched: any = () => {
  };

  addSeries(): void {
    if (this.disabled) return;

    const colorIndex = this.seriesList.length % this.defaultColors.length;
    this.seriesList.push({
      name: '',
      column: '',
      color: this.defaultColors[colorIndex]
    });

    this.onSeriesChange();
  }

  removeSeries(index: number): void {
    if (this.disabled) return;
    this.seriesList.splice(index, 1);
    this.onSeriesChange();
  }

  onSeriesChange(): void {
    this.onChange(this.seriesList);
    this.onTouched();
  }

  writeValue(value: SeriesItem[]): void {
    this.seriesList = value && Array.isArray(value) ? [...value] : [];
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
