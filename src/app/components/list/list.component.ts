// ub-list.component.ts
import { Component, Input, OnInit, OnChanges, SimpleChanges, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ListConfig, ListField, ViewMode, FieldType } from './list.types';
import {UbCardListComponent} from './card-list/card-list.component';
import {UbTableListComponent} from './table-list/table-list.component';
import {ConfirmationService} from '../confirmation-modal/confirmation-modal.service';

@Component({
  selector: 'ub-list',
  standalone: true,
  imports: [CommonModule, FormsModule, UbCardListComponent, UbTableListComponent],
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.scss']
})
export class UbListComponent implements OnInit, OnChanges {
  @Input() data: any[] = [];
  @Input() config!: ListConfig;
  @Output() refresh = new EventEmitter<void>();

  viewMode: ViewMode = 'card';
  searchTerm = '';
  filteredData: any[] = [];
  processedFields: ListField[] = [];

  get hasRefreshListener(): boolean {
    return this.refresh.observed;
  }

  get recordCount(): number {
    return this.filteredData.length;
  }

  get totalCount(): number {
    return this.data.length;
  }

  constructor(
    private confirmService: ConfirmationService
  ) {
  }

  ngOnInit(): void {
    this.initialize();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['data'] || changes['config']) {
      this.initialize();
    }
  }

  private initialize(): void {
    this.viewMode = this.config?.defaultView || 'card';
    this.processFields();
    this.filterData();
  }

  private processFields(): void {
    if (this.config?.fields && this.config.fields.length > 0) {
      this.processedFields = this.config.fields;
    } else if (this.data && this.data.length > 0) {
      this.processedFields = this.autoGenerateFields(this.data[0]);
    }
  }

  private autoGenerateFields(item: any): ListField[] {
    return Object.keys(item).map((key, index) => ({
      key,
      label: this.formatLabel(key),
      type: this.inferType(item[key]),
      visible: true,
      sortable: true,
      isTitleCard: index === 0,
      isSubtitleCard: index === 1
    }));
  }

  private formatLabel(key: string): string {
    return key
      .replace(/([A-Z])/g, ' $1')
      .replace(/^./, str => str.toUpperCase())
      .trim();
  }

  private inferType(value: any): FieldType {
    if (typeof value === 'boolean') return 'boolean';
    if (typeof value === 'number') return 'number';
    if (value instanceof Date) return 'date';
    if (typeof value === 'string') {
      if (value.includes('@')) return 'email';
      if (/^\d{10,}$/.test(value.replace(/\D/g, ''))) return 'phone';
    }
    return 'text';
  }

  filterData(): void {
    if (!this.searchTerm.trim()) {
      this.filteredData = [...this.data];
      return;
    }

    const term = this.searchTerm.toLowerCase();
    this.filteredData = this.data.filter(item =>
      this.processedFields.some(field => {
        const value = this.getFieldValue(item, field);
        return value?.toString().toLowerCase().includes(term);
      })
    );
  }

  getFieldValue(item: any, field: ListField): any {
    const value = item[field.key];
    if (field.formatter) {
      return field.formatter(value, item);
    }
    return this.formatValue(value, field.type);
  }

  private formatValue(value: any, type?: FieldType): string {
    if (value === null || value === undefined) return '-';

    switch (type) {
      case 'date':
        return new Date(value).toLocaleDateString('pt-BR');
      case 'currency':
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
      case 'percentage':
        return `${value}%`;
      case 'boolean':
        return value ? 'Sim' : 'Não';
      case 'number':
        return new Intl.NumberFormat('pt-BR').format(value);
      default:
        return value.toString();
    }
  }

  changeView(mode: ViewMode): void {
    this.viewMode = mode;
  }

  async executeAction(action: any, item?: any): Promise<void> {
    if (action.confirm) {
      const confirmed = await this.confirmService.confirm("Você tem certeza que deseja prosseguir?");
      if (!confirmed) return;
    }
    action.action(item);
  }

  isActionVisible(action: any, item?: any): boolean {
    if (typeof action.visible === 'function') {
      return action.visible(item);
    }
    return action.visible !== false;
  }

  onRefresh(): void {
    this.refresh.emit();
  }

  exportPDF(): void {
    console.log('Exportar PDF');
  }

  exportExcel(): void {
    console.log('Exportar Excel');
  }

  exportCSV(): void {
    console.log('Exportar CSV');
  }
}
