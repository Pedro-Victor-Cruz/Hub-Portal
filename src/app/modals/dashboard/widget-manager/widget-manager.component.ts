import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ModalRef } from '../../modal/modal.service';
import { DashboardService, DashboardWidget, DashboardSection } from '../../../services/dashboard.service';
import { ToastService } from '../../../components/toast/toast.service';
import { Utils } from '../../../services/utils.service';
import { ButtonComponent } from '../../../components/form/button/button.component';
import { ModalService } from '../../modal/modal.service';
import { ConfirmationService } from '../../../components/confirmation-modal/confirmation-modal.service';
import {WidgetBuilderComponent} from './widget-builder/widget-builder.component';

@Component({
  selector: 'app-widget-manager',
  imports: [CommonModule, ButtonComponent],
  templateUrl: './widget-manager.component.html',
  standalone: true,
  styleUrl: './widget-manager.component.scss'
})
export class WidgetManagerComponent implements OnInit {
  modalRef!: ModalRef;
  section!: DashboardSection;

  loading: boolean = false;
  widgets: DashboardWidget[] = [];

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService,
    private modalService: ModalService,
    private confirmationService: ConfirmationService
  ) {}

  ngOnInit() {
    this.loadWidgets();
  }

  /**
   * Carrega widgets da seção
   */
  async loadWidgets() {
    this.loading = true;
    try {
      const response = await this.dashboardService.listSectionWidgets(this.section.id);
      this.widgets = response.data || [];
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar widgets'));
    } finally {
      this.loading = false;
    }
  }

  /**
   * Adiciona novo widget
   */
  async addWidget() {
    const modal = await this.modalService.open({
      title: 'Adicionar Widget',
      component: WidgetBuilderComponent,
      data: {
        sectionId: this.section.id
      },
      size: 'xl'
    });

    if (modal !== undefined) {
      this.loadWidgets();
    }
  }

  /**
   * Edita widget
   */
  async editWidget(widget: DashboardWidget) {
    const modal = await this.modalService.open({
      title: 'Editar Widget',
      component: WidgetBuilderComponent,
      data: {
        sectionId: this.section.id,
        widget: widget
      },
      size: 'xl'
    });

    if (modal !== undefined) {
      this.loadWidgets();
    }
  }

  /**
   * Remove widget
   */
  async deleteWidget(widget: DashboardWidget) {
    const confirmed = await this.confirmationService.confirm(
      `Tem certeza que deseja excluir o widget "${widget.title || widget.key}"?`,
      'Sim, excluir',
      'Cancelar'
    );

    if (confirmed && widget.id) {
      this.loading = true;
      try {
        await this.dashboardService.deleteWidget(widget.id);
        this.loadWidgets();
      } catch (error) {
        // Erro já tratado no service
      } finally {
        this.loading = false;
      }
    }
  }

  /**
   * Duplica widget
   */
  async duplicateWidget(widget: DashboardWidget) {
    this.toast.info('Funcionalidade de duplicação em desenvolvimento');
  }

  /**
   * Retorna ícone do tipo de widget
   */
  getWidgetIcon(type: string): string {
    const icons: any = {
      'chart_line': 'bx-line-chart',
      'chart_bar': 'bx-bar-chart',
      'chart_pie': 'bx-pie-chart-alt',
      'chart_area': 'bx-area',
      'table': 'bx-table',
      'metric_card': 'bx-card',
      'progress': 'bx-trending-up',
      'gauge': 'bx-tachometer',
      'list': 'bx-list-ul',
      'map': 'bx-map',
      'custom': 'bx-customize'
    };
    return icons[type] || 'bx-widget';
  }

  /**
   * Retorna label do tipo de widget
   */
  getWidgetTypeLabel(type: string): string {
    const labels: any = {
      'chart_line': 'Gráfico de Linha',
      'chart_bar': 'Gráfico de Barras',
      'chart_pie': 'Gráfico de Pizza',
      'chart_area': 'Gráfico de Área',
      'table': 'Tabela',
      'metric_card': 'Card de Métrica',
      'progress': 'Barra de Progresso',
      'gauge': 'Medidor',
      'list': 'Lista',
      'map': 'Mapa',
      'custom': 'Personalizado'
    };
    return labels[type] || type;
  }
}
