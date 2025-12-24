// ub-table-list.component.ts
import { Component, Input, Output, EventEmitter, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ListField, ListAction, ListConfig } from '../list.types';

interface ColumnFilter {
  [key: string]: string;
}

interface SortState {
  field: string | null;
  direction: 'asc' | 'desc' | null;
}

@Component({
  selector: 'ub-table-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './table-list.component.html',
  styleUrls: ['./table-list.component.scss']
})
export class UbTableListComponent implements OnInit, OnChanges {
  @Input() data: any[] = [];
  @Input() fields: ListField[] = [];
  @Input() config!: ListConfig;
  @Input() itemActions: ListAction[] = [];
  @Output() actionClick = new EventEmitter<{ action: ListAction; item: any }>();

  visibleFields: ListField[] = [];
  sortedData: any[] = [];
  columnFilters: ColumnFilter = {};
  sortState: SortState = { field: null, direction: null };
  showColumnFilters = false;

  get hasActions(): boolean {
    return this.itemActions && this.itemActions.length > 0;
  }

  get tableConfig() {
    return this.config?.table || {};
  }

  ngOnInit(): void {
    this.initialize();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['data'] || changes['fields']) {
      this.initialize();
    }
  }

  private initialize(): void {
    this.visibleFields = this.fields.filter(field => {
      if (typeof field.visible === 'function') {
        return field.visible(null);
      }
      return field.visible !== false;
    });
    this.applyFiltersAndSort();
  }

  toggleColumnFilters(): void {
    this.showColumnFilters = !this.showColumnFilters;
    if (!this.showColumnFilters) {
      this.columnFilters = {};
      this.applyFiltersAndSort();
    }
  }

  onColumnFilterChange(): void {
    this.applyFiltersAndSort();
  }

  sortColumn(field: ListField): void {
    if (!field.sortable) return;

    if (this.sortState.field === field.key) {
      if (this.sortState.direction === 'asc') {
        this.sortState.direction = 'desc';
      } else if (this.sortState.direction === 'desc') {
        this.sortState.field = null;
        this.sortState.direction = null;
      }
    } else {
      this.sortState.field = field.key;
      this.sortState.direction = 'asc';
    }

    this.applyFiltersAndSort();
  }

  private applyFiltersAndSort(): void {
    let result = [...this.data];

    // Apply column filters
    Object.keys(this.columnFilters).forEach(key => {
      const filterValue = this.columnFilters[key]?.toLowerCase().trim();
      if (filterValue) {
        result = result.filter(item => {
          const field = this.fields.find(f => f.key === key);
          const value = this.getFieldValue(item, field!);
          return value?.toString().toLowerCase().includes(filterValue);
        });
      }
    });

    // Apply sorting
    if (this.sortState.field && this.sortState.direction) {
      const field = this.fields.find(f => f.key === this.sortState.field);
      result.sort((a, b) => {
        const aVal = this.getFieldValue(a, field!);
        const bVal = this.getFieldValue(b, field!);

        let comparison = 0;
        if (aVal < bVal) comparison = -1;
        if (aVal > bVal) comparison = 1;

        return this.sortState.direction === 'asc' ? comparison : -comparison;
      });
    }

    this.sortedData = result;
  }

  getFieldValue(item: any, field: ListField): any {
    const value = item[field.key];
    if (field.formatter) {
      return field.formatter(value, item);
    }
    return this.formatValue(value, field.type);
  }

  private formatValue(value: any, type?: string): string {
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

  getFieldColor(item: any, field: ListField): string | null {
    if (field.color) {
      return field.color(item[field.key], item);
    }
    return null;
  }

  getFieldIcon(item: any, field: ListField): string | null {
    if (field.icon) {
      return field.icon(item[field.key], item);
    }

    // Default icons based on type
    if (field.type === 'boolean') {
      return item[field.key] ? 'bx-check-circle' : 'bx-x-circle';
    }

    return null;
  }

  isActionVisible(action: ListAction, item: any): boolean {
    if (typeof action.visible === 'function') {
      return action.visible(item);
    }
    return action.visible !== false;
  }

  onActionClick(action: ListAction, item: any, event: Event): void {
    event.stopPropagation();
    this.actionClick.emit({ action, item });
  }

  getSortIcon(field: ListField): string {
    if (this.sortState.field !== field.key) {
      return 'bx-sort';
    }
    return this.sortState.direction === 'asc' ? 'bx-sort-up' : 'bx-sort-down';
  }

  clearColumnFilter(fieldKey: string): void {
    delete this.columnFilters[fieldKey];
    this.onColumnFilterChange();
  }

  hasActiveFilters(): boolean {
    return Object.keys(this.columnFilters).some(key => this.columnFilters[key]?.trim());
  }

  clearAllFilters(): void {
    this.columnFilters = {};
    this.applyFiltersAndSort();
  }

  protected readonly Object = Object;
}
