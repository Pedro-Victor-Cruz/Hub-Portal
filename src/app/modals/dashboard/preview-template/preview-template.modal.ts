import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import {DashboardTemplate, TemplatePreview} from '../../../services/dashboard-template.service';
import {ModalRef} from '../../modal/modal.service';

@Component({
  selector: 'app-preview-template-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './preview-template.modal.html',
  styleUrl: './preview-template.modal.scss'
})
export class PreviewTemplateModal {

  template!: DashboardTemplate;
  preview!: TemplatePreview;
  modalRef!: ModalRef;

  constructor() {}

  /**
   * Verifica se há detalhes de integrações
   */
  hasIntegrationDetails(): boolean {
    return this.preview.requirements.details?.integrations !== undefined;
  }

  /**
   * Retorna lista formatada de integrações
   */
  getIntegrationsList(): Array<{name: string, active: boolean}> {
    const integrations = this.preview.requirements.details?.integrations || {};
    return Object.entries(integrations).map(([name, active]) => ({
      name,
      active: active as boolean
    }));
  }

  /**
   * Retorna ícone do widget
   */
  getWidgetIcon(type: string): string {
    const icons: Record<string, string> = {
      'chart_bar': 'bx-bar-chart-alt-2',
      'chart_line': 'bx-line-chart',
      'chart_pie': 'bx-pie-chart-alt-2',
      'chart_donut': 'bx-donut-chart',
      'metric_card': 'bx-card',
      'table': 'bx-table',
      'map': 'bx-map',
      'gauge': 'bx-tachometer'
    };
    return icons[type] || 'bx-widget';
  }

  /**
   * Retorna ícone da categoria
   */
  getCategoryIcon(category: string): string {
    const icons: Record<string, string> = {
      'vendas': 'bx-line-chart',
      'financeiro': 'bx-dollar',
      'operacional': 'bx-cog',
      'marketing': 'bx-bullseye',
      'recursos-humanos': 'bx-user',
      'ti': 'bx-server'
    };
    return icons[category] || 'bx-grid-alt';
  }

  /**
   * Retorna itens de capacidade formatados
   */
  getCapabilityItems(items: any): string[] {
    if (Array.isArray(items)) {
      return items;
    }
    if (typeof items === 'object') {
      return Object.values(items).map(item => String(item));
    }
    return [];
  }

  /**
   * Fecha o modal e retorna flag de importação
   */
  onImport() {
    this.modalRef.close({ import: true });
  }

  /**
   * Fecha o modal sem importar
   */
  onCancel() {
    this.modalRef.close();
  }
}
