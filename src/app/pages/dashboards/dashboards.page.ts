import { Component, OnInit } from '@angular/core';
import { ContentComponent } from '../../components/content/content.component';
import { TableComponent, TableConfig } from '../../components/table/table.component';
import { DashboardService, Dashboard } from '../../services/dashboard.service';
import { ToastService } from '../../components/toast/toast.service';
import { Utils } from '../../services/utils.service';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import {ConfirmationService} from '../../components/confirmation-modal/confirmation-modal.service';

@Component({
  selector: 'app-dashboards',
  imports: [
    CommonModule,
    ContentComponent,
    TableComponent
  ],
  templateUrl: './dashboards.page.html',
  standalone: true,
  styleUrl: './dashboards.page.scss'
})
export class DashboardsPage implements OnInit {

  protected loading: boolean = false;
  protected data: Dashboard[] = [];
  protected configTable: TableConfig = {
    columns: [
      {
        headerName: "Nome",
        field: "name",
        sortable: true
      },
      {
        headerName: "Chave",
        field: "key",
        sortable: true
      },
      {
        headerName: "Descrição",
        field: "description"
      },
      {
        headerName: "Status",
        field: "ready"
      },
      {
        headerName: "Ativo",
        field: "active"
      }
    ],
    showAddButton: true,
    showEditButton: true,
    showDeleteButton: true,
    customActions: [
      {
        icon: 'bx bx-copy',
        tooltip: 'Duplicar Dashboard',
        action: (row: Dashboard) => this.duplicateDashboard(row)
      },
      {
        icon: 'bx bx-link-external',
        tooltip: 'Abrir Dashboard',
        action: (row: Dashboard) => this.openDashboard(row)
      }
    ]
  };

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService,
    private confirmationService: ConfirmationService,
    private router: Router
  ) { }

  ngOnInit() {
    this.loadData();
  }

  /**
   * Carrega lista de dashboards
   */
  protected async loadData() {
    this.loading = true;
    try {
      const response = await this.dashboardService.getDashboards(true);
      this.data = response.data || [];
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error));
    } finally {
      this.loading = false;
    }
  }

  /**
   * Abre modal de configuração
   */
  async openConfig(dashboard?: Dashboard) {
    const modal = await this.dashboardService.openDashboardModal(dashboard);

    if (modal !== undefined) {
      this.loadData();
    }
  }

  /**
   * Exclui um dashboard
   */
  async deleteDashboard(dashboard: Dashboard) {
    const confirmed = await this.confirmationService.confirm(
      `Tem certeza que deseja excluir o dashboard "${dashboard.name}"? Esta ação não pode ser desfeita.`,
      'Sim, excluir',
      'Cancelar'
    );

    if (confirmed) {
      this.loading = true;
      try {
        await this.dashboardService.deleteDashboard(dashboard.key);
        this.loadData();
      } catch (error) {
        // Erro já tratado no service
      } finally {
        this.loading = false;
      }
    }
  }

  /**
   * Duplica um dashboard
   */
  async duplicateDashboard(dashboard: Dashboard) {
    // Poderia abrir um dialog para pedir novo nome/chave
    // Por ora, vou usar um prompt simples
    const newName = prompt(`Digite o nome do novo dashboard:`, `${dashboard.name} (Cópia)`);

    if (!newName) return;

    const newKey = newName.toLowerCase().replace(/[^a-z0-9]+/g, '-');

    this.loading = true;
    try {
      await this.dashboardService.duplicateDashboard(dashboard.key, newKey, newName);
      this.loadData();
    } catch (error) {
      // Erro já tratado no service
    } finally {
      this.loading = false;
    }
  }

  /**
   * Abre o dashboard para visualização/uso
   */
  openDashboard(dashboard: Dashboard) {
    //abrir uma nova página onode o link deve ser /dashboards/view/{dashboard.key} blank new
    this.router.navigate([`/dashboards/view/${dashboard.key}`]);
  }
}
