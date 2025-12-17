import {Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {CdkDragDrop, DragDropModule, moveItemInArray} from '@angular/cdk/drag-drop';
import {InputComponent} from '../../form/input/input.component';
import {DropdownComponent} from '../../form/dropdown/dropdown.component';
import {ButtonComponent} from '../../form/button/button.component';
import {ToggleSwitchComponent} from '../../form/toggle-switch/toggle-switch.component';


export interface FieldMetadata {
  label: string;
  visible: boolean;
  aggregation?: string | null;
  format?: string | null;
  order: number;
}

export interface FieldMetadataConfig {
  [fieldName: string]: FieldMetadata;
}

interface FieldItem {
  fieldName: string;
  label: string;
  visible: boolean;
  format: string | null;
  aggregation: string | null;
}

@Component({
  selector: 'app-dynamic-query-fields-metadata-builder',
  imports: [
    CommonModule,
    FormsModule,
    DragDropModule,
    InputComponent,
    DropdownComponent,
    ButtonComponent,
    ToggleSwitchComponent
  ],
  templateUrl: './dynamic-query-fields-metadata-builder.component.html',
  styleUrl: './dynamic-query-fields-metadata-builder.component.scss',
  standalone: true
})
export class DynamicQueryFieldsMetadataBuilderComponent implements OnInit {

  @Input() metadataConfig: FieldMetadataConfig = {};
  @Output() metadataConfigChange = new EventEmitter<FieldMetadataConfig>();

  fields: FieldItem[] = [];
  originalConfig: string = '';

  aggregationOptions = [
    { label: 'Nenhuma', value: null },
    { label: 'Soma', value: 'sum' },
    { label: 'Média', value: 'average' },
    { label: 'Contagem', value: 'count' },
    { label: 'Mínimo', value: 'min' },
    { label: 'Máximo', value: 'max' }
  ];

  formatOptions = [
    { label: 'Nenhum', value: '' },
    { label: 'Data', value: 'date' },
    { label: 'Data e Hora', value: 'datetime' },
    { label: 'Moeda', value: 'currency' },
    { label: 'Porcentagem', value: 'percentage' },
    { label: 'Maiúsculas', value: 'upper' },
    { label: 'Minúsculas', value: 'lower' },
    { label: 'Capitalizar', value: 'capitalize' },
    { label: 'Remover Espaços', value: 'trim' }
  ];

  newFieldName: string = '';
  showAddFieldForm: boolean = false;

  ngOnInit() {
    this.loadFields();
  }

  private loadFields() {
    const fieldItems: FieldItem[] = [];

    // Carrega campos já configurados
    const configuredFields = Object.entries(this.metadataConfig)
      .sort(([, a], [, b]) => a.order - b.order)
      .map(([fieldName, config]) => ({
        fieldName,
        label: config.label,
        visible: config.visible,
        aggregation: config.aggregation || null,
        format: config.format || null
      }));

    fieldItems.push(...configuredFields);

    this.fields = fieldItems;
    this.originalConfig = JSON.stringify(this.buildConfig());
  }

  onDrop(event: CdkDragDrop<FieldItem[]>) {
    //moveItemInArray(this.fields, event.previousIndex, event.currentIndex);
  }

  addNewField() {
    const fieldName = this.newFieldName.trim();

    console.log('Adicionando novo campo:', fieldName);

    if (!fieldName) {
      return;
    }

    // Verifica se o campo já existe
    if (this.fields.some(f => f.fieldName === fieldName)) {
      return;
    }

    console.log('Campo adicionado com sucesso:', fieldName);

    this.fields.push({
      fieldName,
      label: this.formatLabel(fieldName),
      visible: true,
      aggregation: null,
      format: null
    });

    console.log('Campos atuais:', this.fields);

    this.newFieldName = '';
    this.showAddFieldForm = false;
  }

  removeField(fieldName: string) {
    this.fields = this.fields.filter(f => f.fieldName !== fieldName);
  }

  saveChanges() {
    const config = this.buildConfig();
    this.metadataConfigChange.emit(config);
    this.originalConfig = JSON.stringify(config);
  }

  cancelChanges() {
    this.loadFields();
    this.showAddFieldForm = false;
    this.newFieldName = '';
  }

  get hasPendingChanges(): boolean {
    const currentConfig = JSON.stringify(this.buildConfig());
    return currentConfig !== this.originalConfig;
  }

  private buildConfig(): FieldMetadataConfig {
    const config: FieldMetadataConfig = {};

    this.fields.forEach((field, index) => {
      config[field.fieldName] = {
        label: field.label,
        visible: field.visible,
        aggregation: field.aggregation,
        format: field.format,
        order: index
      };
    });

    return config;
  }

  private formatLabel(fieldName: string): string {
    return fieldName
      .replace(/_/g, ' ')
      .replace(/([A-Z])/g, ' $1')
      .replace(/^./, str => str.toUpperCase())
      .trim();
  }
}
