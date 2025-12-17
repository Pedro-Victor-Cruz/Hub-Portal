import { Component, Input, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import {ToastService} from '../../../components/toast/toast.service';
import {DashboardService, DashboardWidget} from '../../../services/dashboard.service';
import {Utils} from '../../../services/utils.service';

@Component({
  selector: 'app-dashboard-table',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="table-card" [class.loading]="loading">

      <div class="table-header">
        <h3 class="table-title">
          <i class="bx bx-table"></i>
          {{ widget.title }}
        </h3>
        <div class="table-actions">
          @if (config.enable_export) {
            <button class="action-btn" (click)="exportData()" title="Exportar">
              <i class="bx bx-download"></i>
            </button>
          }
          <button class="action-btn" (click)="loadData()" [disabled]="loading" title="Atualizar">
            <i class="bx bx-refresh"></i>
          </button>
        </div>
      </div>

      @if (loading) {
        <div class="table-loading">
          <i class="bx bx-loader-alt bx-spin"></i>
          <span>Carregando dados...</span>
        </div>
      }

      @if (!loading && error) {
        <div class="table-error">
          <i class="bx bx-error-circle"></i>
          <p>{{ error }}</p>
          <button class="retry-btn" (click)="loadData()">Tentar novamente</button>
        </div>
      }

      @if (!loading && !error && tableData) {
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                @for (column of tableData.columns; track column) {
                  <th (click)="sortBy(column)" class="sortable">
                    {{ getColumnLabel(column) }}
                    @if (sortColumn === column) {
                      <i class="bx" [class.bx-chevron-up]="sortDirection === 'asc'"
                         [class.bx-chevron-down]="sortDirection === 'desc'"></i>
                    }
                  </th>
                }
              </tr>
            </thead>
            <tbody>
              @for (row of getSortedData(); track $index) {
                <tr>
                  @for (column of tableData.columns; track column) {
                    <td>{{ formatCell(row[column]) }}</td>
                  }
                </tr>
              }
              @if (getSortedData().length === 0) {
                <tr>
                  <td [attr.colspan]="tableData.columns.length" class="no-data">
                    <i class="bx bx-data"></i>
                    <span>Nenhum dado disponível</span>
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>

        @if (tableData.rows.length > 0) {
          <div class="table-footer">
            <span class="table-count">
              <i class="bx bx-list-ul"></i>
              Total: {{ tableData.rows.length }} registros
            </span>
          </div>
        }
      }
    </div>
  `,
  styles: [`
    .table-card {
      background: var(--bg-white);
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      overflow: hidden;
      display: flex;
      flex-direction: column;

      &:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      }

      &.loading {
        opacity: 0.7;
      }
    }

    .table-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.5rem;
      border-bottom: 1px solid var(--input-border-color);
      background: var(--background-color);

      .table-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--text-color);

        i {
          font-size: 22px;
          color: var(--secondary-color);
        }
      }

      .table-actions {
        display: flex;
        gap: 0.5rem;

        .action-btn {
          background: var(--bg-white);
          border: 1px solid var(--input-border-color);
          padding: 0.5rem;
          border-radius: var(--border-radius);
          cursor: pointer;
          color: var(--text-muted);
          transition: all 0.2s;

          i {
            font-size: 18px;
            display: block;
          }

          &:hover:not(:disabled) {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            color: var(--text-color);
          }

          &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
          }
        }
      }
    }

    .table-loading {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem;
      gap: 0.75rem;
      color: var(--text-muted);

      i {
        font-size: 48px;
      }

      span {
        font-size: 14px;
      }
    }

    .table-error {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem;
      gap: 1rem;
      color: var(--danger-color);
      text-align: center;

      i {
        font-size: 48px;
      }

      p {
        margin: 0;
        font-size: 14px;
      }

      .retry-btn {
        padding: 0.75rem 1.5rem;
        background: var(--danger-color);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;

        &:hover {
          opacity: 0.9;
        }
      }
    }

    .table-wrapper {
      overflow-x: auto;
      max-height: 600px;

      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;

        thead {
          background: var(--background-color);
          position: sticky;
          top: 0;
          z-index: 2;

          th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-color);
            border-bottom: 2px solid var(--input-border-color);
            white-space: nowrap;

            &.sortable {
              cursor: pointer;
              user-select: none;
              transition: all 0.2s;

              &:hover {
                background: rgba(247, 194, 18, 0.1);
              }

              i {
                margin-left: 0.5rem;
                font-size: 14px;
                vertical-align: middle;
              }
            }
          }
        }

        tbody {
          tr {
            transition: background 0.2s;

            &:hover {
              background: rgba(247, 194, 18, 0.05);
            }

            &:nth-child(even) {
              background: rgba(0, 0, 0, 0.02);

              &:hover {
                background: rgba(247, 194, 18, 0.05);
              }
            }

            td {
              padding: 1rem;
              border-bottom: 1px solid rgba(0, 0, 0, 0.05);
              color: var(--text-color);

              &.no-data {
                text-align: center;
                padding: 3rem;
                color: var(--text-muted);

                i {
                  display: block;
                  font-size: 48px;
                  margin-bottom: 0.5rem;
                  opacity: 0.3;
                }
              }
            }
          }
        }
      }
    }

    .table-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--input-border-color);
      background: var(--background-color);

      .table-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;

        i {
          font-size: 16px;
        }
      }
    }
  `]
})
export class DashboardTableComponent implements OnInit, OnChanges {
  @Input() widget!: DashboardWidget;
  @Input() filters: any = {};

  loading: boolean = false;
  error: string | null = null;
  tableData: { columns: string[]; rows: any[] } | null = null;
  config: any = {};
  sortColumn: string | null = null;
  sortDirection: 'asc' | 'desc' = 'asc';

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    this.config = this.widget.config || {};
    this.loadData();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['filters'] && !changes['filters'].firstChange) {
      this.loadData();
    }
  }

  async loadData() {
    if (!this.widget.id) return;

    this.loading = true;
    this.error = null;

    try {
      const response = await this.dashboardService.getWidgetData(this.widget.id, this.filters);

      if (response.success && response.data) {
        const rawData = Utils.keysToUpperCase(response.data.data.data);
        this.parseTableData(rawData);
      }
    } catch (error: any) {
      this.error = error?.message || 'Erro ao carregar dados da tabela';
    } finally {
      this.loading = false;
    }
  }

  parseTableData(data: any) {
    if (!data || !Array.isArray(data) || data.length === 0) {
      this.tableData = { columns: [], rows: [] };
      return;
    }

    const columns = Object.keys(data[0]);
    this.tableData = { columns, rows: data };
  }

  getColumnLabel(column: string): string {
    // Tenta pegar do metadata se disponível
    const metadata = this.widget.dynamic_query?.fields_metadata?.[column];
    if (metadata?.label) {
      return metadata.label;
    }

    // Formata o nome da coluna
    return column
      .split('_')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
      .join(' ');
  }

  sortBy(column: string) {
    if (this.sortColumn === column) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = column;
      this.sortDirection = 'asc';
    }
  }

  getSortedData(): any[] {
    if (!this.tableData || !this.sortColumn) {
      return this.tableData?.rows || [];
    }

    return [...this.tableData.rows].sort((a, b) => {
      const aValue = a[this.sortColumn!];
      const bValue = b[this.sortColumn!];

      if (aValue === bValue) return 0;

      const comparison = aValue < bValue ? -1 : 1;
      return this.sortDirection === 'asc' ? comparison : -comparison;
    });
  }

  formatCell(value: any): string {
    if (value === null || value === undefined) return '-';

    if (typeof value === 'number') {
      return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(value);
    }

    return String(value);
  }

  exportData() {
    if (!this.tableData || this.tableData.rows.length === 0) {
      this.toast.warning('Nenhum dado para exportar');
      return;
    }

    const csv = this.convertToCSV(this.tableData);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `${this.widget.key || 'table'}_${Date.now()}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    this.toast.success('Dados exportados com sucesso');
  }

  convertToCSV(data: { columns: string[]; rows: any[] }): string {
    const header = data.columns.join(',');
    const rows = data.rows.map(row =>
      data.columns.map(col => {
        const value = row[col];
        return typeof value === 'string' && value.includes(',')
          ? `"${value}"`
          : value;
      }).join(',')
    );

    return [header, ...rows].join('\n');
  }
}
