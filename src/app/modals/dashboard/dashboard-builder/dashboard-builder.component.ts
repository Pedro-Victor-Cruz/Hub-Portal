import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Dashboard, DashboardSection, DashboardWidget, DashboardService } from '../../../services/dashboard.service';
import { ToastService } from '../../../components/toast/toast.service';
import { Utils } from '../../../services/utils.service';
import { ButtonComponent } from '../../../components/form/button/button.component';
import { ModalService } from '../../modal/modal.service';
import { ConfirmationService } from '../../../components/confirmation-modal/confirmation-modal.service';
import { WidgetBuilderComponent } from '../widget-builder/widget-builder.component';
import { SectionBuilderComponent } from '../section-builder/section-builder.component';
import { TabComponent } from '../../../components/tabs/tab/tab.component';
import { FiltersComponent } from '../../../components/filters/filters.component';
import { TabsComponent } from '../../../components/tabs/tabs.component';
import {DashboardInvitationsComponent} from '../dashboard-invitations/dashboard-invitations.component';

interface SectionNode {
  section: DashboardSection;
  widgets: DashboardWidget[];
  children: SectionNode[];
  expanded: boolean;
}

@Component({
  selector: 'app-dashboard-builder',
  imports: [
    CommonModule,
    ButtonComponent,
    TabComponent,
    FiltersComponent,
    TabsComponent,
    DashboardInvitationsComponent
  ],
  templateUrl: './dashboard-builder.component.html',
  standalone: true,
  styleUrl: './dashboard-builder.component.scss'
})
export class DashboardBuilderComponent implements OnInit {
  @Input() dashboardKey!: string;
  @Input() dashboard!: Dashboard;
  @Output() onClose = new EventEmitter<void>();
  @Output() onEditDashboard = new EventEmitter<void>();

  loading: boolean = false;
  sectionsTree: SectionNode[] = [];

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService,
    private modalService: ModalService,
    private confirmationService: ConfirmationService
  ) {}

  ngOnInit() {
    this.loadDashboardStructure();
  }

  async loadDashboardStructure() {
    this.loading = true;
    try {
      const response = await this.dashboardService.getDashboardDetails(this.dashboardKey);
      const structure = response.data;

      if (structure && structure.sections) {
        this.sectionsTree = await this.buildTree(structure.sections);
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar estrutura'));
    } finally {
      this.loading = false;
    }
  }

  private async buildTree(sections: any[]): Promise<SectionNode[]> {
    const nodes: SectionNode[] = [];

    for (const item of sections) {
      const widgets = await this.loadSectionWidgets(item.section.id);

      const node: SectionNode = {
        section: item.section,
        widgets: widgets,
        children: item.children ? await this.buildTree(item.children) : [],
        expanded: true
      };

      nodes.push(node);
    }

    return nodes;
  }

  private async loadSectionWidgets(sectionId: number): Promise<DashboardWidget[]> {
    try {
      const response = await this.dashboardService.listSectionWidgets(sectionId);
      return response.data || [];
    } catch (error) {
      return [];
    }
  }

  async addRootSection() {
    const modal = await this.modalService.open({
      title: 'Nova Seção',
      component: SectionBuilderComponent,
      data: {
        dashboardKey: this.dashboardKey,
        section: null
      }
    });

    if (modal !== undefined) {
      await this.loadDashboardStructure();
    }
  }

  async addChildSection(parentSection: DashboardSection) {
    const modal = await this.modalService.open({
      title: 'Nova Subseção',
      component: SectionBuilderComponent,
      data: {
        dashboardKey: this.dashboardKey,
        parentSectionId: parentSection.id,
        section: null
      }
    });

    if (modal !== undefined) {
      await this.loadDashboardStructure();
    }
  }

  async editSection(section: DashboardSection) {
    const modal = await this.modalService.open({
      title: 'Editar Seção',
      component: SectionBuilderComponent,
      data: {
        dashboardKey: this.dashboardKey,
        section: section
      }
    });

    if (modal !== undefined) {
      await this.loadDashboardStructure();
    }
  }

  async deleteSection(section: DashboardSection) {
    const confirmed = await this.confirmationService.confirm(
      `Tem certeza que deseja excluir a seção "${section.title || section.key}"? Todos os widgets e subseções também serão removidos.`,
      'Sim, excluir',
      'Cancelar'
    );

    if (confirmed) {
      this.toast.info('Funcionalidade de exclusão em desenvolvimento');
    }
  }

  async addWidget(section: DashboardSection) {
    const modal = await this.modalService.open({
      title: `Novo Widget - ${section.title || section.key}`,
      component: WidgetBuilderComponent,
      data: {
        sectionId: section.id,
        widget: null
      },
      size: 'xl'
    });

    if (modal !== undefined) {
      await this.loadDashboardStructure();
    }
  }

  async editWidget(widget: DashboardWidget, section: DashboardSection) {
    const modal = await this.modalService.open({
      title: `Editar Widget - ${widget.title || widget.key}`,
      component: WidgetBuilderComponent,
      data: {
        sectionId: section.id,
        widget: widget
      },
      size: 'xl'
    });

    if (modal !== undefined) {
      await this.loadDashboardStructure();
    }
  }

  async deleteWidget(widget: DashboardWidget) {
    const confirmed = await this.confirmationService.confirm(
      `Tem certeza que deseja excluir o widget "${widget.title || widget.key}"?`,
      'Sim, excluir',
      'Cancelar'
    );

    if (confirmed && widget.id) {
      try {
        await this.dashboardService.deleteWidget(widget.id);
        await this.loadDashboardStructure();
      } catch (error) {
        this.toast.error(Utils.getErrorMessage(error, 'Erro ao excluir widget'));
      }
    }
  }

  toggleSection(node: SectionNode) {
    node.expanded = !node.expanded;
  }

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

  close() {
    this.onClose.emit();
  }

  editDashboard() {
    this.onEditDashboard.emit();
  }
}
