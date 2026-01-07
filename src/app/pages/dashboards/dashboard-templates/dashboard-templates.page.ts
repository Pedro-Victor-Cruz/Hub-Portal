import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ContentComponent } from '../../../components/content/content.component';
import { ToastService } from '../../../components/toast/toast.service';
import { DashboardTemplate, DashboardTemplateService } from '../../../services/dashboard-template.service';
import { ModalService } from '../../../modals/modal/modal.service';
import { Utils } from '../../../services/utils.service';
import { ImportTemplateModal } from '../../../modals/dashboard/import-template/import-template.modal';
import { PreviewTemplateModal } from '../../../modals/dashboard/preview-template/preview-template.modal';
import { IntegrationService } from '../../../services/integration.service';

export interface Integration {
  integration_name: string;
  name: string;
  description: string;
  img: string;
}

@Component({
  selector: 'app-dashboard-templates',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    ContentComponent
  ],
  templateUrl: './dashboard-templates.page.html',
  styleUrl: './dashboard-templates.page.scss'
})
export class DashboardTemplatesPage implements OnInit, OnDestroy {

  protected loading: boolean = false;
  protected templates: DashboardTemplate[] = [];
  protected categories: string[] = [];
  protected integrations: Integration[] = [];

  // Filtros
  protected searchTerm: string = '';
  protected selectedCategory: string | null = null;
  protected selectedIntegration: string | null = null;

  // Sidebar mobile
  protected sidebarOpen: boolean = false;

  // Subject para debounce da pesquisa
  private searchSubject = new Subject<string>();

  constructor(
    private templateService: DashboardTemplateService,
    private integrationService: IntegrationService,
    private toast: ToastService,
    private router: Router,
    private modalService: ModalService
  ) {}

  ngOnInit() {
    this.loadCategories();
    this.loadIntegrations();
    this.loadTemplates();
    this.setupSearchDebounce();
  }

  ngOnDestroy() {
    this.searchSubject.complete();
  }

  /**
   * Configura o debounce para a pesquisa
   */
  private setupSearchDebounce() {
    this.searchSubject.pipe(
      debounceTime(500),
      distinctUntilChanged()
    ).subscribe(searchTerm => {
      this.searchTerm = searchTerm;
      this.loadTemplates();
    });
  }

  /**
   * Handler para mudança no input de pesquisa
   */
  protected onSearchChange(value: string) {
    this.searchSubject.next(value);
  }

  /**
   * Carrega lista de templates com filtros aplicados
   */
  protected async loadTemplates() {
    this.loading = true;
    try {
      const filters: any = {};

      if (this.searchTerm) {
        filters.search = this.searchTerm;
      }

      if (this.selectedCategory) {
        filters.category = this.selectedCategory;
      }

      if (this.selectedIntegration) {
        filters.integration = this.selectedIntegration;
      }

      const response = await this.templateService.getTemplates(filters);
      this.templates = response.data || [];
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar templates'));
    } finally {
      this.loading = false;
    }
  }

  /**
   * Carrega categorias disponíveis
   */
  protected async loadCategories() {
    try {
      const response = await this.templateService.getCategories();
      this.categories = response.data || [];
    } catch (error) {
      console.error('Erro ao carregar categorias:', error);
    }
  }

  /**
   * Carrega integrações disponíveis
   */
  protected async loadIntegrations() {
    try {
      const response = await this.integrationService.getIntegrations();
      this.integrations = response || [];
    } catch (error) {
      console.error('Erro ao carregar integrações:', error);
    }
  }

  /**
   * Seleciona categoria para filtro
   */
  protected selectCategory(category: string | null) {
    this.selectedCategory = this.selectedCategory === category ? null : category;
    this.loadTemplates();
    this.closeSidebarOnMobile();
  }

  /**
   * Seleciona integração para filtro
   */
  protected selectIntegration(integration: string | null) {
    this.selectedIntegration = this.selectedIntegration === integration ? null : integration;
    this.loadTemplates();
    this.closeSidebarOnMobile();
  }

  /**
   * Limpa todos os filtros
   */
  protected clearFilters() {
    this.searchTerm = '';
    this.selectedCategory = null;
    this.selectedIntegration = null;
    this.loadTemplates();
  }

  /**
   * Verifica se há filtros ativos
   */
  protected hasActiveFilters(): boolean {
    return !!(this.searchTerm || this.selectedCategory || this.selectedIntegration);
  }

  /**
   * Toggle sidebar em mobile
   */
  protected toggleSidebar() {
    this.sidebarOpen = !this.sidebarOpen;
  }

  /**
   * Fecha sidebar em mobile
   */
  private closeSidebarOnMobile() {
    if (window.innerWidth <= 768) {
      this.sidebarOpen = false;
    }
  }

  /**
   * Abre modal de preview do template
   */
  protected async showPreview(template: DashboardTemplate) {
    try {
      const response = await this.templateService.getPreview(template.key);

      const modal = await this.modalService.open({
        title: `${template.name}`,
        component: PreviewTemplateModal,
        data: {
          template: template,
          preview: response.data
        },
        size: 'xl'
      });

      if (modal?.import) {
        this.importTemplate(template);
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar preview'));
    }
  }

  /**
   * Importa um template
   */
  protected async importTemplate(template: DashboardTemplate) {
    const modal = await this.modalService.open({
      title: `${template.name}`,
      component: ImportTemplateModal,
      data: {
        template: template
      },
      size: 'xl'
    });

    if (modal?.success) {
      this.toast.success('Template importado com sucesso!');
      this.router.navigate(['/dashboards', modal.dashboardKey]);
    }
  }

  /**
   * Retorna o ícone da categoria
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
   * Retorna o nome formatado da categoria
   */
  getCategoryName(category: string): string {
    const names: Record<string, string> = {
      'vendas': 'Vendas',
      'financeiro': 'Financeiro',
      'operacional': 'Operacional',
      'marketing': 'Marketing',
      'recursos-humanos': 'Recursos Humanos',
      'ti': 'TI'
    };
    return names[category] || category;
  }
}
