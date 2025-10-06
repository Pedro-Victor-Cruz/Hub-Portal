import { Component, OnInit } from '@angular/core';
import { ContentComponent } from '../../components/content/content.component';
import { TableComponent, TableConfig } from '../../components/table/table.component';
import { LogSystemService } from '../../services/log-system.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ConfirmationService } from '../../components/confirmation-modal/confirmation-modal.service';
import { ToastService } from '../../components/toast/toast.service';
import {PanelComponent} from '../../components/content/panels/panel/panel.component';
import {PanelAreaComponent} from '../../components/content/panels/panel-area.component';

@Component({
  imports: [
    ContentComponent,
    TableComponent,
    CommonModule,
    FormsModule,
    PanelComponent,
    PanelAreaComponent
  ],
  templateUrl: './logs-system.page.html',
  standalone: true,
  styleUrl: './logs-system.page.scss'
})
export class LogsSystemPage implements OnInit {
  protected loading: boolean = false;
  protected logs: any[] = [];
  protected stats: any = null;
  protected showStats: boolean = false;
  protected showFilters: boolean = false;
  protected showCleanupModal: boolean = false;
  protected cleanupDays: number = 30;

  // Filtros
  protected filters = {
    level: '',
    action: '',
    module: '',
    user_id: '',
    ip_address: '',
    start_date: '',
    end_date: '',
    search: ''
  };

  // Opções para os filtros
  protected levels = [
    { value: '', label: 'Todos os níveis' },
    { value: 'emergency', label: 'Emergency' },
    { value: 'alert', label: 'Alert' },
    { value: 'critical', label: 'Critical' },
    { value: 'error', label: 'Error' },
    { value: 'warning', label: 'Warning' },
    { value: 'notice', label: 'Notice' },
    { value: 'info', label: 'Info' },
    { value: 'debug', label: 'Debug' }
  ];

  // Configuração da tabela
  protected tableConfig: TableConfig = {
    columns: [
      {
        field: 'level',
        headerName: 'Nível',
        width: 120,
        cellRenderer: (params: any) => {
          const level = params.value;
          const colors: any = {
            'emergency': '#dc2626',
            'alert': '#ea580c',
            'critical': '#dc2626',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'notice': '#3b82f6',
            'info': '#06b6d4',
            'debug': '#6b7280'
          };
          const color = colors[level] || '#6b7280';
          return `<span style="
            background: ${color}20;
            color: ${color};
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
          ">${level}</span>`;
        }
      },
      {
        field: 'action',
        headerName: 'Ação',
        width: 150
      },
      {
        field: 'module',
        headerName: 'Módulo',
        width: 150
      },
      {
        field: 'user_name',
        headerName: 'Usuário',
        width: 180,
        valueGetter: (params: any) => params.data.user?.name || params.data.user_name || 'Sistema'
      },
      {
        field: 'description',
        headerName: 'Descrição',
        minWidth: 250
      },
      {
        field: 'ip_address',
        headerName: 'IP',
        width: 140
      },
      {
        field: 'created_at',
        headerName: 'Data/Hora',
        width: 180,
        valueGetter: (params: any) => {
          if (!params.data.created_at) return '';
          return new Date(params.data.created_at).toLocaleString('pt-BR');
        }
      }
    ],
    selectable: false,
    showAddButton: false,
    showEditButton: false,
    showDeleteButton: false,
    enableFiltering: true,
    enableSorting: true,
    pagination: false,
    animateRows: true,
    rowHeight: 52
  };

  constructor(
    private logService: LogSystemService,
    private confirmationService: ConfirmationService,
    private toast: ToastService
  ) {}

  ngOnInit(): void {
    this.load();
  }

  async load() {
    this.loading = true;
    await this.loadLogs();
    await this.loadStats();
    this.loading = false;
  }

  async loadLogs(): Promise<void> {
    try {
      const queryFilters: any = {};

      if (this.filters.level) queryFilters.level = this.filters.level;
      if (this.filters.action) queryFilters.action = this.filters.action;
      if (this.filters.module) queryFilters.module = this.filters.module;
      if (this.filters.user_id) queryFilters.user_id = this.filters.user_id;
      if (this.filters.ip_address) queryFilters.ip_address = this.filters.ip_address;
      if (this.filters.start_date) queryFilters.start_date = this.filters.start_date;
      if (this.filters.end_date) queryFilters.end_date = this.filters.end_date;
      if (this.filters.search) queryFilters.search = this.filters.search;

      this.logs = await this.logService.getLogs(queryFilters);
    } catch (error) {
      console.error('Erro ao carregar logs:', error);
    }
  }

  async loadStats(): Promise<void> {
    try {
      const options: any = {};
      if (this.filters.start_date) options.start_date = this.filters.start_date;
      if (this.filters.end_date) options.end_date = this.filters.end_date;

      this.stats = await this.logService.getStats(options);
      this.showStats = true;
    } catch (error) {
      console.error('Erro ao carregar estatísticas:', error);
    }
  }

  toggleFilters(): void {
    this.showFilters = !this.showFilters;
  }

  applyFilters(): void {
    this.loadLogs();
  }

  clearFilters(): void {
    this.filters = {
      level: '',
      action: '',
      module: '',
      user_id: '',
      ip_address: '',
      start_date: '',
      end_date: '',
      search: ''
    };
    this.loadLogs();
  }

  async cleanupLogs(): Promise<void> {
    this.showCleanupModal = true;
  }

  async confirmCleanup(): Promise<void> {
    if (!this.cleanupDays || this.cleanupDays < 1) {
      this.toast.error('Por favor, informe um número válido de dias');
      return;
    }

    try {
      this.loading = true;
      this.showCleanupModal = false;

      await this.logService.cleanup(this.cleanupDays);
      this.cleanupDays = 30; // Reset
      this.loadLogs();
      this.loadStats();
    } catch (error) {
      console.error('Erro ao limpar logs:', error);
    } finally {
      this.loading = false;
    }
  }

  cancelCleanup(): void {
    this.showCleanupModal = false;
    this.cleanupDays = 30;
  }

  getLevelBadgeClass(level: any): string {
    const classes: any = {
      'emergency': 'badge-emergency',
      'alert': 'badge-alert',
      'critical': 'badge-critical',
      'error': 'badge-error',
      'warning': 'badge-warning',
      'notice': 'badge-notice',
      'info': 'badge-info',
      'debug': 'badge-debug'
    };
    return classes[level] || 'badge-default';
  }

  getStatPercentage(value: any, total: number): number {
    return total > 0 ? Math.round((value / total) * 100) : 0;
  }
}
