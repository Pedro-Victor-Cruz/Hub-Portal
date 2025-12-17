import {Component, forwardRef, Input} from '@angular/core';
import {ControlValueAccessor, NG_VALUE_ACCESSOR, FormsModule} from '@angular/forms';
import {CommonModule} from '@angular/common';

interface ColumnMapping {
  source: string;
  target: string;
}

@Component({
  selector: 'ub-column-mapping',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="column-mapping-field">
      <label *ngIf="label" class="field-label">
        {{ label }}
        <span *ngIf="required" class="required-indicator">*</span>
      </label>

      <small *ngIf="helpText" class="help-text mb-2">
        <i class="pi pi-info-circle"></i> {{ helpText }}
      </small>

      <div class="mapping-list">
        <div *ngFor="let mapping of mappings; let i = index" class="mapping-item">
          <div class="mapping-row">
            <div class="mapping-field">
              <label class="field-label-small">Coluna Origem</label>
              <div class="input-with-icon">
                <i class="pi pi-database icon-left"></i>
                <input
                  type="text"
                  [(ngModel)]="mapping.source"
                  (ngModelChange)="onMappingChange()"
                  [disabled]="disabled"
                  placeholder="Ex: nome_cliente"
                  class="field-input with-icon-left"
                />
              </div>
            </div>

            <div class="mapping-arrow">
              <i class="pi pi-arrow-right"></i>
            </div>

            <div class="mapping-field">
              <label class="field-label-small">Coluna Destino</label>
              <div class="input-with-icon">
                <i class="pi pi-table icon-left"></i>
                <input
                  type="text"
                  [(ngModel)]="mapping.target"
                  (ngModelChange)="onMappingChange()"
                  [disabled]="disabled"
                  placeholder="Ex: customer_name"
                  class="field-input with-icon-left"
                />
              </div>
            </div>

            <button
              type="button"
              class="btn-remove-mapping"
              (click)="removeMapping(i)"
              [disabled]="disabled"
              title="Remover mapeamento">
              <i class="pi pi-trash"></i>
            </button>
          </div>
        </div>

        <div *ngIf="mappings.length === 0" class="empty-state">
          <i class="pi pi-sitemap text-3xl text-gray-400 mb-2"></i>
          <p class="text-gray-500">Nenhum mapeamento configurado</p>
          <p class="text-gray-400 text-sm">Adicione mapeamentos para transformar suas colunas</p>
        </div>
      </div>

      <button
        type="button"
        class="btn-add"
        (click)="addMapping()"
        [disabled]="disabled">
        <i class="pi pi-plus"></i>
        Adicionar Mapeamento
      </button>

      <small *ngIf="error" class="error-text">
        <i class="pi pi-exclamation-circle"></i> {{ error }}
      </small>
    </div>
  `,
  styles: [`
    .column-mapping-field {
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

    .help-text {
      display: block;
      color: #6c757d;
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
    }

    .help-text i {
      color: #17a2b8;
    }

    .mapping-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .mapping-item {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 1rem;
      transition: box-shadow 0.2s;
    }

    .mapping-item:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .mapping-row {
      display: grid;
      grid-template-columns: 1fr auto 1fr auto;
      gap: 1rem;
      align-items: end;
    }

    @media (max-width: 768px) {
      .mapping-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
      }

      .mapping-arrow {
        transform: rotate(90deg);
      }
    }

    .mapping-field {
      display: flex;
      flex-direction: column;
    }

    .field-label-small {
      font-size: 0.75rem;
      font-weight: 500;
      color: #6c757d;
      margin-bottom: 0.25rem;
    }

    .input-with-icon {
      position: relative;
      display: flex;
      align-items: center;
    }

    .icon-left {
      position: absolute;
      left: 0.75rem;
      color: #6c757d;
      font-size: 0.875rem;
      pointer-events: none;
    }

    .field-input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 0.875rem;
      transition: border-color 0.2s;
    }

    .field-input.with-icon-left {
      padding-left: 2.5rem;
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

    .mapping-arrow {
      display: flex;
      align-items: center;
      justify-content: center;
      color: #007bff;
      font-size: 1.25rem;
      padding-bottom: 0.25rem;
    }

    @media (max-width: 768px) {
      .mapping-arrow {
        padding-bottom: 0;
        padding-top: 0.5rem;
      }
    }

    .btn-remove-mapping {
      background: transparent;
      border: 1px solid #dc3545;
      color: #dc3545;
      padding: 0.5rem;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 38px;
      width: 38px;
    }

    .btn-remove-mapping:hover:not(:disabled) {
      background: #dc3545;
      color: white;
    }

    .btn-remove-mapping:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
      border: 2px dashed #dee2e6;
      border-radius: 6px;
      background: white;
    }

    .btn-add {
      width: 100%;
      padding: 0.5rem 1rem;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: background-color 0.2s;
    }

    .btn-add:hover:not(:disabled) {
      background: #0056b3;
    }

    .btn-add:disabled {
      background: #6c757d;
      cursor: not-allowed;
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
      useExisting: forwardRef(() => ColumnMappingComponent),
      multi: true
    }
  ]
})
export class ColumnMappingComponent implements ControlValueAccessor {
  @Input() label: string = '';
  @Input() required: boolean = false;
  @Input() disabled: boolean = false;
  @Input() helpText: string = '';
  @Input() error: string = '';

  mappings: ColumnMapping[] = [];

  onChange: any = () => {};
  onTouched: any = () => {};

  addMapping(): void {
    if (this.disabled) return;

    this.mappings.push({
      source: '',
      target: ''
    });

    this.onMappingChange();
  }

  removeMapping(index: number): void {
    if (this.disabled) return;
    this.mappings.splice(index, 1);
    this.onMappingChange();
  }

  onMappingChange(): void {
    // Remove mappings vazios antes de emitir
    const validMappings = this.mappings.filter(m => m.source || m.target);
    this.onChange(validMappings);
    this.onTouched();
  }

  writeValue(value: ColumnMapping[]): void {
    this.mappings = value && Array.isArray(value) ? [...value] : [];
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
