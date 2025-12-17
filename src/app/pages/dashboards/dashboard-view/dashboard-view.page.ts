import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {ContentComponent} from '../../../components/content/content.component';
import {DashboardWidgetRendererComponent} from './dashboard-widget-renderer/dashboard-widget-renderer.component';
import {DashboardService} from '../../../services/dashboard.service';
import {ToastService} from '../../../components/toast/toast.service';
import {Utils} from '../../../services/utils.service';
import {PanelAreaComponent} from '../../../components/content/panels/panel-area.component';
import {PanelComponent} from '../../../components/content/panels/panel/panel.component';

@Component({
  selector: 'app-dashboard-view',
  imports: [
    CommonModule,
    ContentComponent,
    DashboardWidgetRendererComponent,
    PanelAreaComponent,
    PanelComponent
  ],
  templateUrl: './dashboard-view.page.html',
  standalone: true,
  styleUrl: './dashboard-view.page.scss'
})
export class DashboardViewPage implements OnInit, OnDestroy {
  private destroy$ = new Subject<void>();

  dashboardKey: string = '';
  dashboard: any = null;
  structure: any = null;
  loading: boolean = false;
  activeFilters: any = {};

  // Para navegação breadcrumb
  breadcrumb: any[] = [];
  currentSection: any = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private dashboardService: DashboardService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        this.dashboardKey = params['key'];
        this.loadDashboard();
      });
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * Carrega estrutura completa do dashboard
   */
  async loadDashboard() {
    this.loading = true;
    try {
      const response = await this.dashboardService.getDashboard(this.dashboardKey);
      this.dashboard = response.data?.dashboard;
      this.structure = response.data;

      if (!this.dashboard) {
        this.toast.error('Dashboard não encontrado');
        this.router.navigate(['/dashboards']);
        return;
      }

      // Inicializa na primeira seção raiz
      if (this.structure.sections && this.structure.sections.length > 0) {
        this.navigateToSection(this.structure.sections[0]);
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar dashboard'));
      this.router.navigate(['/dashboards']);
    } finally {
      this.loading = false;
    }
  }

  /**
   * Navega para uma seção específica
   */
  navigateToSection(sectionData: any) {
    this.currentSection = sectionData;
    this.breadcrumb = sectionData.breadcrumb || [];
  }

  /**
   * Executa drill down para subseção
   */
  async executeDrillDown(targetSection: any, sourceData?: any) {
    if (!this.currentSection?.section?.id || !targetSection?.section?.id) return;

    try {
      const response = await this.dashboardService.executeDrillDown(
        this.currentSection.section.id,
        targetSection.section.id,
        sourceData,
        this.activeFilters
      );

      if (response.success) {
        this.navigateToSection(response.data);
      }
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao executar drill down'));
    }
  }

  /**
   * Volta para seção anterior no breadcrumb
   */
  navigateBack(index: number) {
    // Implementar navegação por breadcrumb
    // Por ora, apenas recarrega o dashboard
    this.loadDashboard();
  }

  /**
   * Aplica filtros globais
   */
  applyFilters(filters: any) {
    this.activeFilters = filters;
    // Recarrega dados dos widgets com novos filtros
    this.loadDashboard();
  }

  /**
   * Atualiza dashboard (refresh manual)
   */
  refreshDashboard() {
    this.loadDashboard();
  }

  /**
   * Volta para lista de dashboards
   */
  goBack() {
    this.router.navigate(['/dashboards']);
  }

  /**
   * Abre dashboard em modo de edição
   */
  editDashboard() {
    this.dashboardService.openDashboardModal(this.dashboard);
  }
}
