import { Component, Input, Output, EventEmitter, forwardRef } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { InputComponent } from '../input/input.component';
import { DropdownComponent } from '../dropdown/dropdown.component';
import { ToggleSwitchComponent } from '../toggle-switch/toggle-switch.component';
import { ButtonComponent } from '../button/button.component';
import { TextareaComponent } from '../textarea/textarea.component';
import { BaseInputComponent } from '../base-input.component';
import { NgxJsonViewerModule } from 'ngx-json-viewer';

// Interface para opções de tipo
interface TypeOption {
  label: string;
  value: string;
}

// Interface simplificada para atributos
export interface ObjectAttribute {
  name: string;
  value: any;
  type: string;
  collapsed?: boolean;
}

// Enum para modos de visualização
export enum ViewMode {
  VISUAL = 'visual',
  JSON = 'json',
  VIEWER = 'viewer'
}

@Component({
  selector: 'ub-object-editor',
  standalone: true,
  imports: [
    CommonModule,
    BaseInputComponent,
    InputComponent,
    DropdownComponent,
    ToggleSwitchComponent,
    ButtonComponent,
    TextareaComponent,
    NgxJsonViewerModule
  ],
  templateUrl: './object-editor.component.html',
  styleUrls: ['./object-editor.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ObjectEditorComponent),
      multi: true,
    },
  ],
})
export class ObjectEditorComponent implements ControlValueAccessor {
  @Input() label: string = 'Editor de Objeto';
  @Input() helpText: string = 'Adicione e edite propriedades do objeto';
  @Input() error: string = '';
  @Input() success: string = '';
  @Input() addButtonLabel: string = 'Adicionar Propriedade';

  @Output() valueChange = new EventEmitter<Record<string, any>>();

  // Enums e constantes
  ViewMode = ViewMode;
  currentViewMode: ViewMode = ViewMode.VISUAL;

  // Opções de tipo para o dropdown
  typeOptions: TypeOption[] = [
    { label: 'Texto', value: 'string' },
    { label: 'Número', value: 'number' },
    { label: 'Booleano', value: 'boolean' },
    { label: 'Array', value: 'array' },
    { label: 'Objeto', value: 'object' },
    { label: 'Data', value: 'date' }
  ];

  // Opções do modo de visualização
  viewModeOptions = [
    { label: 'Editor Visual', value: ViewMode.VISUAL, icon: 'bx-edit' },
    { label: 'Editor JSON', value: ViewMode.JSON, icon: 'bx-code-alt' },
    { label: 'Visualizador', value: ViewMode.VIEWER, icon: 'bx-show' }
  ];

  attributes: ObjectAttribute[] = [];
  newAttributeName: string = '';
  newAttributeType: string = 'string';
  isAdding: boolean = false;
  jsonString: string = '';
  jsonError: string = '';
  allCollapsed: boolean = false;

  // ControlValueAccessor methods
  onChange: any = () => {};
  onTouched: any = () => {};

  writeValue(value: Record<string, any>): void {
    if (value && typeof value === 'object') {
      this.attributes = Object.entries(value).map(([name, val]) => ({
        name,
        value: val,
        type: this.detectType(val),
        collapsed: false
      }));
    } else {
      this.attributes = [];
    }
    this.updateJsonString();
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  // Detectar o tipo de um valor
  private detectType(value: any): string {
    if (value === null || value === undefined) return 'string';
    if (Array.isArray(value)) return 'array';
    if (typeof value === 'object') {
      if (value instanceof Date) return 'date';
      if (typeof value.getMonth === 'function') return 'date';
      return 'object';
    }
    if (typeof value === 'boolean') return 'boolean';
    if (typeof value === 'number') return 'number';
    return 'string';
  }

  // Gerenciamento de modos de visualização
  setViewMode(mode: ViewMode): void {
    // Se estamos saindo do modo JSON, tentar aplicar as mudanças
    if (this.currentViewMode === ViewMode.JSON && mode !== ViewMode.JSON) {
      this.applyJsonChanges();
    }

    this.currentViewMode = mode;

    // Se estamos entrando no modo JSON, atualizar a string
    if (mode === ViewMode.JSON) {
      this.updateJsonString();
    }
  }

  // Atualizar string JSON baseada nos atributos atuais
  private updateJsonString(): void {
    const obj = this.getObjectValue();
    this.jsonString = JSON.stringify(obj, null, 2);
  }

  // Aplicar mudanças do editor JSON
  applyJsonChanges(): void {
    if (!this.jsonString.trim()) {
      this.attributes = [];
      this.emitValue();
      this.jsonError = '';
      return;
    }

    try {
      const parsed = JSON.parse(this.jsonString);
      if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
        this.attributes = Object.entries(parsed).map(([name, val]) => ({
          name,
          value: val,
          type: this.detectType(val),
          collapsed: false
        }));
        this.emitValue();
        this.jsonError = '';
        this.success = 'JSON aplicado com sucesso!';
        setTimeout(() => this.success = '', 3000);
      } else {
        this.jsonError = 'O JSON deve ser um objeto válido';
      }
    } catch (error) {
      this.jsonError = 'JSON inválido: ' + (error as Error).message;
    }
  }

  // Exportar JSON
  exportJson(): void {
    const obj = this.getObjectValue();
    const jsonString = JSON.stringify(obj, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'object-config.json';
    link.click();
    URL.revokeObjectURL(url);
  }

  // Importar JSON
  onFileSelect(event: any): void {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const content = e.target?.result as string;
        const parsed = JSON.parse(content);

        if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
          this.attributes = Object.entries(parsed).map(([name, val]) => ({
            name,
            value: val,
            type: this.detectType(val),
            collapsed: false
          }));
          this.emitValue();
          this.updateJsonString();
          this.success = 'JSON importado com sucesso!';
          setTimeout(() => this.success = '', 3000);
        } else {
          this.error = 'O arquivo deve conter um objeto JSON válido';
          setTimeout(() => this.error = '', 5000);
        }
      } catch (error) {
        this.error = 'Erro ao processar o arquivo: ' + (error as Error).message;
        setTimeout(() => this.error = '', 5000);
      }
    };
    reader.readAsText(file);

    // Limpar o input
    event.target.value = '';
  }

  // Gerenciamento de accordion
  toggleAttribute(index: number): void {
    this.attributes[index].collapsed = !this.attributes[index].collapsed;
  }

  toggleAllAttributes(): void {
    this.allCollapsed = !this.allCollapsed;
    this.attributes.forEach(attr => {
      attr.collapsed = this.allCollapsed;
    });
  }

  // Obter valor do objeto atual
  getObjectValue(): Record<string, any> {
    const result: Record<string, any> = {};
    this.attributes.forEach(attr => {
      if (attr.name) {
        result[attr.name] = attr.value;
      }
    });
    return result;
  }

  // Iniciar adição de nova propriedade
  startAddAttribute(): void {
    this.isAdding = true;
    this.newAttributeName = '';
    this.newAttributeType = 'string';
  }

  // Cancelar adição
  cancelAddAttribute(): void {
    this.isAdding = false;
    this.newAttributeName = '';
    this.error = '';
  }

  // Adicionar uma nova propriedade
  addAttribute(): void {
    if (!this.newAttributeName.trim()) {
      this.error = 'O nome da propriedade é obrigatório';
      return;
    }

    const attributeName = this.newAttributeName.trim();

    if (this.attributes.some(attr => attr.name === attributeName)) {
      this.error = `A propriedade "${attributeName}" já existe`;
      return;
    }

    let defaultValue: any;
    switch (this.newAttributeType) {
      case 'number': defaultValue = 0; break;
      case 'boolean': defaultValue = false; break;
      case 'array': defaultValue = []; break;
      case 'object': defaultValue = {}; break;
      case 'date': defaultValue = new Date().toISOString().split('T')[0]; break;
      default: defaultValue = ''; break;
    }

    const newAttr: ObjectAttribute = {
      name: attributeName,
      value: defaultValue,
      type: this.newAttributeType,
      collapsed: false
    };

    this.attributes.push(newAttr);
    this.emitValue();

    this.isAdding = false;
    this.newAttributeName = '';
    this.error = '';
  }

  // Remover uma propriedade
  removeAttribute(index: number): void {
    this.attributes.splice(index, 1);
    this.emitValue();
  }

  // Atualizar o valor de uma propriedade
  updateAttributeValue(index: number, value: any): void {
    this.attributes[index].value = value;
    this.emitValue();
  }

  // Atualizar o tipo de uma propriedade
  updateAttributeType(index: number, type: string): void {
    if (this.attributes[index].type !== type) {
      let defaultValue: any;
      switch (type) {
        case 'number': defaultValue = 0; break;
        case 'boolean': defaultValue = false; break;
        case 'array': defaultValue = []; break;
        case 'object': defaultValue = {}; break;
        case 'date': defaultValue = new Date().toISOString().split('T')[0]; break;
        default: defaultValue = ''; break;
      }
      this.attributes[index].value = defaultValue;
    }

    this.attributes[index].type = type;
    this.emitValue();
  }

  // Atualizar o nome de uma propriedade
  updateAttributeName(index: number, name: string): void {
    const newName = name.trim();

    if (!newName) {
      this.error = 'O nome da propriedade não pode estar vazio';
      return;
    }

    if (this.attributes.some((attr, i) => i !== index && attr.name === newName)) {
      this.error = `A propriedade "${newName}" já existe`;
      return;
    }

    this.attributes[index].name = newName;
    this.emitValue();
    this.error = '';
  }

  // Emitir o valor atualizado
  private emitValue(): void {
    const result = this.getObjectValue();
    this.onChange(result);
    this.valueChange.emit(result);

    // Atualizar JSON string se estamos no modo JSON
    if (this.currentViewMode === ViewMode.JSON) {
      this.updateJsonString();
    }
  }

  // Obter o tipo de input para uma propriedade
  getInputType(attribute: ObjectAttribute): string {
    switch (attribute.type) {
      case 'number': return 'number';
      case 'date': return 'date';
      default: return 'text';
    }
  }

  // Obter placeholder para o campo de valor
  getValuePlaceholder(type: string): string {
    switch (type) {
      case 'string': return 'Digite o texto';
      case 'number': return 'Digite o número';
      case 'date': return 'Selecione a data';
      case 'array': return 'Ex: ["item1", "item2"]';
      case 'object': return 'Ex: {"chave": "valor"}';
      default: return 'Digite o valor';
    }
  }

  // Verificar se é um campo de texto simples
  isTextInput(attribute: ObjectAttribute): boolean {
    return ['string', 'number', 'date'].includes(attribute.type);
  }

  // Verificar se é um campo de textarea (para arrays/objetos)
  isTextareaInput(attribute: ObjectAttribute): boolean {
    return ['array', 'object'].includes(attribute.type);
  }

  // Formatar valor para exibição em textarea
  formatValueForDisplay(value: any): string {
    if (value === null || value === undefined) return '';
    if (Array.isArray(value)) return JSON.stringify(value, null, 2);
    if (typeof value === 'object') return JSON.stringify(value, null, 2);
    return String(value);
  }

  // Converter valor de string para o tipo apropriado
  parseInputValue(type: string, value: string): any {
    if (!value) {
      switch (type) {
        case 'number': return 0;
        case 'boolean': return false;
        case 'array': return [];
        case 'object': return {};
        case 'date': return new Date().toISOString().split('T')[0];
        default: return '';
      }
    }

    switch (type) {
      case 'number':
        const num = Number(value);
        return isNaN(num) ? 0 : num;

      case 'boolean':
        return value.toLowerCase() === 'true';

      case 'array':
        try {
          return JSON.parse(value);
        } catch {
          return value.split(',').map(item => item.trim()).filter(item => item);
        }

      case 'object':
        try {
          return JSON.parse(value);
        } catch {
          return {};
        }

      case 'date':
        return value;

      default:
        return value;
    }
  }

  getLabelType(attr: ObjectAttribute) {
    return this.typeOptions.find(t => t.value === attr.type)?.label;
  }
}
