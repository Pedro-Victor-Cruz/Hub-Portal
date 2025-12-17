import { Component, Input, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import {DashboardService, DashboardWidget} from '../../../services/dashboard.service';
import {ToastService} from '../../../components/toast/toast.service';
import {Utils} from '../../../services/utils.service';

@Component({
  selector: 'app-dashboard-metric-card',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="metric-card" [class.loading]="loading">

      @if (loading) {
        <div class="metric-loading">
          <i class="bx bx-loader-alt bx-spin"></i>
        </div>
      }

      @if (!loading && error) {
        <div class="metric-error">
          <span>{{ error }}</span>
          <button class="retry-btn" (click)="loadData()" title="Tentar novamente">
            <i class="bx bx-refresh"></i>
          </button>
        </div>
      }

      @if (!loading && !error && metricData) {
        <div class="metric-content">
          <div class="metric-header">
            <div class="metric-icon">
              <i class="bx {{ metricData.icon }}"></i>
            </div>
            <div class="metric-label">{{ metricData.label }}</div>
          </div>

          <div class="metric-value">{{ formatValue(metricData.value) }}</div>

          @if (metricData.trend) {
            <div class="metric-trend" [class.trend-up]="metricData.trend.direction === 'up'"
                 [class.trend-down]="metricData.trend.direction === 'down'">
              <i class="bx" [class.bx-trending-up]="metricData.trend.direction === 'up'"
                 [class.bx-trending-down]="metricData.trend.direction === 'down'"></i>
              {{ metricData.trend.value }}%
            </div>
          }
        </div>
      }

      <button class="refresh-btn" (click)="loadData()" [disabled]="loading" title="Atualizar">
        <i class="bx bx-refresh"></i>
      </button>
    </div>
  `,
  styles: [`
    .metric-card {
      background: var(--bg-white);
      border-radius: 10px;
      padding: 1.25rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      border: 1px solid var(--border-color, #e9ecef);
      transition: all 0.2s ease;
      position: relative;
      min-height: 120px;
      display: flex;
      align-items: center;
      overflow: hidden;

      &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        border-color: var(--primary-light, #e3f2fd);

        .refresh-btn {
          opacity: 1;
        }
      }

      &.loading {
        opacity: 0.6;
      }
    }

    .metric-loading {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      color: var(--text-muted, #6c757d);

      i {
        font-size: 20px;
      }
    }

    .metric-error {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      width: 100%;
      color: var(--danger-color, #dc3545);
      font-size: 14px;

      .retry-btn {
        background: transparent;
        border: 1px solid var(--danger-color, #dc3545);
        color: var(--danger-color, #dc3545);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.25rem;

        &:hover {
          background: var(--danger-color, #dc3545);
          color: white;
        }

        i {
          font-size: 14px;
        }
      }
    }

    .metric-content {
      width: 100%;

      .metric-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;

        .metric-icon {
          width: 36px;
          height: 36px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: var(--bg-light, #f8f9fa);
          color: var(--text-color, #333);

          i {
            font-size: 18px;
            opacity: 0.8;
          }
        }

        .metric-label {
          font-size: 13px;
          color: var(--text-muted, #6c757d);
          font-weight: 500;
          letter-spacing: 0.02em;
        }
      }

      .metric-value {
        font-size: 24px;
        font-weight: 600;
        color: var(--text-color, #212529);
        line-height: 1.2;
        margin-bottom: 0.5rem;
        font-feature-settings: 'tnum' 1;
      }

      .metric-trend {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 12px;
        font-weight: 500;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        background: var(--bg-light, #f8f9fa);

        &.trend-up {
          color: var(--success-color, #28a745);

          i {
            color: var(--success-color, #28a745);
          }
        }

        &.trend-down {
          color: var(--danger-color, #dc3545);

          i {
            color: var(--danger-color, #dc3545);
          }
        }

        i {
          font-size: 14px;
        }
      }
    }

    .refresh-btn {
      position: absolute;
      top: 0.75rem;
      right: 0.75rem;
      background: transparent;
      border: none;
      padding: 0.375rem;
      border-radius: 6px;
      cursor: pointer;
      color: var(--text-muted, #adb5bd);
      transition: all 0.2s;
      opacity: 0;
      font-size: 0;

      i {
        font-size: 16px;
        display: block;
      }

      &:hover:not(:disabled) {
        background: var(--bg-light, #f8f9fa);
        color: var(--text-color, #495057);
      }

      &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
      }
    }
  `]
})
export class DashboardMetricCardComponent implements OnInit, OnChanges {
  @Input() widget!: DashboardWidget;
  @Input() filters: any = {};

  loading: boolean = false;
  error: string | null = null;
  metricData: any = null;

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService
  ) {}

  ngOnInit() {
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
        this.metricData = this.parseMetricData(rawData);
      }
    } catch (error: any) {
      this.error = error?.message || 'Erro ao carregar';
    } finally {
      this.loading = false;
    }
  }

  parseMetricData(data: any) {
    const config = this.widget.config || {};

    // Se data já está no formato esperado
    if (data.value !== undefined) {
      return {
        value: data.value,
        label: data.label || config.metric_label || this.widget.title,
        icon: data.icon || config.icon || 'bx-trending-up',
        trend: data.trend
      };
    }

    // Se data é um array, pega o primeiro item
    if (Array.isArray(data) && data.length > 0) {
      const firstItem = data[0];
      const valueColumn = config.data_column || 'VALUE';

      return {
        value: firstItem[valueColumn] || firstItem[Object.keys(firstItem)[0]],
        label: config.metric_label || this.widget.title,
        icon: config.icon || 'bx-trending-up',
        trend: firstItem.TREND
      };
    }

    return null;
  }

  formatValue(value: any): string {
    if (value === null || value === undefined) return '-';

    if (typeof value === 'number') {
      return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(value);
    }

    return String(value);
  }

  getIconBackground(): string {
    const gradients = [
      'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
      'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
      'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
      'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
    ];

    const index = (this.widget.id || 0) % gradients.length;
    return gradients[index];
  }
}
