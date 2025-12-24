import {Component, OnInit} from '@angular/core';
import {ContentComponent} from '../../components/content/content.component';
import {TableConfig} from '../../components/table/table.component';
import {DashboardService, Dashboard} from '../../services/dashboard.service';
import {ToastService} from '../../components/toast/toast.service';
import {Utils} from '../../services/utils.service';
import {CommonModule} from '@angular/common';
import {Router} from '@angular/router';
import {ConfirmationService} from '../../components/confirmation-modal/confirmation-modal.service';
import {UbListComponent} from '../../components/list/list.component';
import {ListConfig} from '../../components/list/list.types';

@Component({
  selector: 'app-dashboards',
  imports: [
    CommonModule,
    ContentComponent,
    UbListComponent
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
        headerName: "Ativo",
        field: "active"
      },
      {
        headerName: "Página Inicial",
        field: "is_home"
      },
      {
        headerName: "Navegável",
        field: "is_navigable"
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
  schamaCard: ListConfig = {
    display: {
      title: 'Lista de dashboards',
      subtitle: 'Veja todos os dashboards disponíveis'
    },
    actions: [
      {
        label: 'Adicionar Dashboard',
        icon: 'bx bx-plus',
        action: () => this.openConfig()
      }
    ],
    itemActions: [
      {
        label: 'Editar',
        icon: 'bx bx-edit',
        action: (item: Dashboard) => this.openConfig(item)
      },
      {
        label: 'Duplicar',
        icon: 'bx bx-copy',
        confirm: true,
        action: (item: Dashboard) => this.duplicateDashboard(item)
      },
      {
        label: 'Excluir',
        icon: 'bx bx-trash',
        color: 'danger',
        confirm: true,
        action: (item: Dashboard) => this.deleteDashboard(item)
      }
    ],
    fields: [
      {
        label: 'Nome',
        key: 'name',
        isTitleCard: true
      },
      {
        label: 'Chave',
        key: 'key',
        isSubtitleCard: true
      },
      {
        label: 'Descrição',
        key: 'description'
      },
      {
        label: 'Ativo',
        key: 'active',
        type: 'boolean',
      },
      {
        label: 'Página Inicial',
        key: 'is_home',
        type: 'boolean'
      },
      {
        label: 'Navegável',
        key: 'is_navigable',
        type: 'boolean'
      }
    ]
  };

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService,
    private confirmationService: ConfirmationService,
    private router: Router
  ) {
  }

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
    this.router.navigate([`/dashboards/${dashboard.key}`]);
  }
}
