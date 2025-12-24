// ub-card-list.component.ts
import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ListField, ListConfig } from '../list.types';

@Component({
  selector: 'ub-card-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './card-list.component.html',
  styleUrls: ['./card-list.component.scss']
})
export class UbCardListComponent {
  @Input() data: any[] = [];
  @Input() fields: ListField[] = [];
  @Input() config!: ListConfig;
  @Input() itemActions: any[] = [];
  @Output() actionClick = new EventEmitter<{ action: any; item: any }>();

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

  getFieldColor(item: any, field: ListField): string {
    if (field.color) {
      return field.color(item[field.key], item);
    }
    return '';
  }

  getFieldIcon(item: any, field: ListField): string {
    if (field.icon) {
      return field.icon(item[field.key], item);
    }
    return '';
  }

  isFieldVisible(item: any, field: ListField): boolean {
    if (typeof field.visible === 'function') {
      return field.visible(item);
    }
    return field.visible !== false;
  }

  isActionVisible(action: any, item: any): boolean {
    if (typeof action.visible === 'function') {
      return action.visible(item);
    }
    return action.visible !== false;
  }

  onActionClick(action: any, item: any): void {
    this.actionClick.emit({ action, item });
  }

  getCardImage(item: any): string | null {
    const imageField = this.config?.card?.imageField;
    if (imageField && item[imageField]) {
      return item[imageField];
    }
    return null;
  }

  getTitleField(): ListField | undefined {
    return this.fields.find(f => f.isTitleCard);
  }

  getSubtitleField(): ListField | undefined {
    return this.fields.find(f => f.isSubtitleCard);
  }

  getCardTitle(item: any): string {
    const titleField = this.getTitleField();
    if (titleField) {
      return this.getFieldValue(item, titleField);
    }
    const firstField = this.fields.find(f => this.isFieldVisible(item, f));
    return firstField ? this.getFieldValue(item, firstField) : '';
  }

  getCardSubtitle(item: any): string {
    const subtitleField = this.getSubtitleField();
    if (subtitleField) {
      return this.getFieldValue(item, subtitleField);
    }
    return '';
  }

  getVisibleFields(item: any): ListField[] {
    return this.fields.filter(field =>
      this.isFieldVisible(item, field) &&
      !field.isTitleCard &&
      !field.isSubtitleCard
    );
  }
}
