import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Dashboard, DashboardSection, DashboardService } from '../../../services/dashboard.service';
import { ToastService } from '../../../components/toast/toast.service';
import { Utils } from '../../../services/utils.service';
import { ButtonComponent } from '../../../components/form/button/button.component';
import { FolderViewComponent, FolderItem, FolderConfig } from '../../../components/folder-view/folder-view.component';
import { ModalService } from '../../modal/modal.service';
import { SectionBuilderComponent } from './section-builder/section-builder.component';
import { InputComponent } from '../../../components/form/input/input.component';
import { FormsModule } from '@angular/forms';
import {ConfirmationService} from '../../../components/confirmation-modal/confirmation-modal.service';
import {WidgetManagerComponent} from '../widget-manager/widget-manager.component';

@Component({
  selector: 'app-dashboard-sections',
  imports: [
    CommonModule,
    ButtonComponent,
    InputComponent,
    FormsModule
  ],
  templateUrl: './dashboard-sections.component.html',
  standalone: true,
  styleUrl: './dashboard-sections.component.scss'
})
export class DashboardSectionsComponent implements OnInit {

  @Input() dashboardKey!: string;
  @Input() dashboard!: Dashboard;

  loading: boolean = false;
  sections: DashboardSection[] = [];
  folderItems: {
    id: number;
    name: string;
    description?: string;
    icon: string;
    level: number;
    key: string;
    active: boolean;
    order: number;
    parent_section_id?: number | null;
    originalData: DashboardSection;
  }[] = [];
  selectedSection: any = null;
  searchQuery: string = '';

  folderConfig: FolderConfig = {
    groupBy: 'level',
    folderName: (item: any) => `Nível ${item.level}`,
    folderIcon: 'bx-folder',
    itemIcon: 'bx-layout',
    itemName: (item: any) => item.title || item.key,
    itemDescription: 'description',
    metaFields: ['key'],
    selectionType: 'checkbox'
  };

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService,
    private modalService: ModalService,
    private confirmationService: ConfirmationService
  ) { }

  ngOnInit() {
    this.loadSections();
  }

  /**
   * Carrega estrutura de seções do dashboard
   */
  async loadSections() {
    this.loading = true;
    try {
      const response = await this.dashboardService.getDashboard(this.dashboardKey);
      const structure = response.data;

      if (structure && structure.sections) {
        this.sections = this.flattenSections(structure.sections);
        this.prepareFolderData();
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar seções'));
    } finally {
      this.loading = false;
    }
  }

  /**
   * Achata estrutura hierárquica de seções
   */
  private flattenSections(sections: any[], result: DashboardSection[] = []): DashboardSection[] {
    sections.forEach(item => {
      result.push(item.section);
      if (item.children && item.children.length > 0) {
        this.flattenSections(item.children, result);
      }
    });
    return result;
  }

  /**
   * Prepara dados para o FolderView
   */
  private prepareFolderData() {
    this.folderItems = this.sections.map(section => ({
      id: section.id || 0,
      name: section.title || section.key,
      description: section.description || undefined,
      icon: 'bx-layout',
      level: section.level,
      key: section.key,
      active: section.active,
      order: section.order,
      parent_section_id: section.parent_section_id,
      originalData: section,
    }));
  }

  /**
   * Adiciona nova seção raiz
   */
  addRootSection() {
    this.openSectionBuilder();
  }

  /**
   * Abre modal para criar/editar seção
   */
  async openSectionBuilder(section?: DashboardSection) {
    const modal = await this.modalService.open({
      title: section ? 'Editar Seção' : 'Nova Seção',
      component: SectionBuilderComponent,
      data: {
        dashboardKey: this.dashboardKey,
        section: section
      }
    });

    if (modal !== undefined) {
      this.loadSections();
    }
  }

  /**
   * Edita seção selecionada
   */
  editSection(item: any) {
    if (item?.originalData) {
      this.openSectionBuilder(item.originalData);
    }
  }

  /**
   * Remove seção
   */
  async deleteSection(item: any) {
    const section = item?.originalData;
    if (!section) return;

    const confirmed = await this.confirmationService.confirm(
      `Tem certeza que deseja excluir a seção "${section.title || section.key}"? Todos os widgets e subseções também serão removidos.`,
      'Sim, excluir',
      'Cancelar'
    );

    if (confirmed) {
      this.loading = true;
      try {
        // Aqui você implementaria a API para deletar seção
        this.toast.info('Funcionalidade de exclusão em desenvolvimento');
        // await this.dashboardService.deleteSection(section.id);
        // this.loadSections();
      } catch (error) {
        this.toast.error(Utils.getErrorMessage(error, 'Erro ao excluir seção'));
      } finally {
        this.loading = false;
      }
    }
  }

  /**
   * Gerencia widgets da seção
   */
  async manageWidgets(item: any) {
    const section = item?.originalData;
    if (!section) return;

    const modal = await this.modalService.open({
      title: `Widgets - ${section.title || section.key}`,
      component: WidgetManagerComponent,
      data: {
        section: section
      },
      size: 'xl'
    });

    if (modal !== undefined) {
      this.loadSections();
    }
  }

  /**
   * Duplica seção
   */
  duplicateSection(item: any) {
    const section = item?.originalData;
    if (!section) return;

    this.toast.info('Funcionalidade de duplicação em desenvolvimento');
  }

  /**
   * Adiciona subseção
   */
  addChildSection(item: any) {
    const section = item?.originalData;
    if (!section) return;

    this.toast.info(`Adicionar subseção à: ${section.title || section.key}`);
    // TODO: Implementar com parent_section_id
  }

  /**
   * Filtra seções
   */
  filterSections() {
    if (!this.searchQuery) {
      this.prepareFolderData();
      return;
    }

    const query = this.searchQuery.toLowerCase();
    this.folderItems = this.sections
      .filter(section =>
        section.title?.toLowerCase().includes(query) ||
        section.key?.toLowerCase().includes(query) ||
        section.description?.toLowerCase().includes(query)
      )
      .map(section => ({
        id: section.id || 0,
        name: section.title || section.key,
        description: section.description || undefined,
        icon: 'bx-layout',
        level: section.level,
        key: section.key,
        active: section.active,
        order: section.order,
        parent_section_id: section.parent_section_id,
        originalData: section
      }));
  }
}
