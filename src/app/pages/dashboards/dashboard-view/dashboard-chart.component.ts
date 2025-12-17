import { Component, Input, OnInit, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  ChartComponent,
  ApexAxisChartSeries,
  ApexChart,
  ApexXAxis,
  ApexDataLabels,
  ApexStroke,
  ApexYAxis,
  ApexTitleSubtitle,
  ApexLegend,
  NgApexchartsModule,
  ApexPlotOptions,
  ApexTooltip,
  ApexGrid
} from 'ng-apexcharts';
import { DashboardService, DashboardWidget } from '../../../services/dashboard.service';
import { ToastService } from '../../../components/toast/toast.service';
import { Utils } from '../../../services/utils.service';

export type ChartOptions = {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  xaxis: ApexXAxis;
  yaxis: ApexYAxis;
  stroke: ApexStroke;
  dataLabels: ApexDataLabels;
  legend: ApexLegend;
  colors: string[];
  plotOptions?: ApexPlotOptions;
  tooltip: ApexTooltip;
  grid: ApexGrid;
  labels?: string[];
};

@Component({
  selector: 'app-dashboard-chart',
  standalone: true,
  imports: [CommonModule, NgApexchartsModule],
  template: `
    <div class="chart-card" [class.loading]="loading">

      @if (loading) {
        <div class="chart-loading">
          <i class="bx bx-loader-alt bx-spin"></i>
        </div>
      }

      @if (!loading && error) {
        <div class="chart-error">
          <span>{{ error }}</span>
          <button class="retry-btn" (click)="loadData()" title="Tentar novamente">
            <i class="bx bx-refresh"></i>
          </button>
        </div>
      }

      @if (!loading && !error && chartOptions) {
        <div class="chart-header">
          <h3 class="chart-title">{{ widget.title || '' }}</h3>
        </div>

        <div class="chart-container">
          <apx-chart
            #chart
            [series]="chartOptions.series"
            [chart]="chartOptions.chart"
            [xaxis]="chartOptions.xaxis"
            [yaxis]="chartOptions.yaxis"
            [stroke]="chartOptions.stroke"
            [dataLabels]="chartOptions.dataLabels"
            [legend]="chartOptions.legend"
            [colors]="chartOptions.colors"
            [grid]="chartOptions.grid"
            [plotOptions]="chartOptions.plotOptions!"
            [tooltip]="chartOptions.tooltip"
            [labels]="chartOptions.labels!">
          </apx-chart>
        </div>
      }

      <button class="refresh-btn" (click)="loadData()" [disabled]="loading" title="Atualizar">
        <i class="bx bx-refresh"></i>
      </button>
    </div>
  `,
  styles: [`
    .chart-card {
      background: var(--bg-white);
      border-radius: 10px;
      padding: 1.5rem;
      border: 1px solid #e9ecef;
      transition: all 0.2s ease;
      position: relative;
      min-height: 300px;
      display: flex;
      flex-direction: column;

      &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        border-color: var(--secondary-color);

        .refresh-btn {
          opacity: 1;
        }
      }

      &.loading {
        opacity: 0.6;
      }
    }

    .chart-loading {
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 1;
      color: var(--text-muted);

      i {
        font-size: 32px;
      }
    }

    .chart-error {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      flex: 1;
      gap: 1rem;
      color: var(--danger-color);
      font-size: 14px;
      text-align: center;
      padding: 2rem;

      .retry-btn {
        background: transparent;
        border: 1px solid var(--danger-color);
        color: var(--danger-color);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;

        &:hover {
          background: var(--danger-color);
          color: white;
        }

        i {
          font-size: 14px;
        }
      }
    }

    .chart-header {
      margin-bottom: 1.25rem;
      padding-right: 2rem;

      .chart-title {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: var(--text-color);
        letter-spacing: 0.01em;
      }
    }

    .chart-container {
      flex: 1;
      min-height: 250px;
    }

    .refresh-btn {
      position: absolute;
      top: 1.25rem;
      right: 1.25rem;
      background: transparent;
      border: none;
      padding: 0.375rem;
      border-radius: 6px;
      cursor: pointer;
      color: var(--text-muted);
      transition: all 0.2s;
      opacity: 0;
      z-index: 10;

      i {
        font-size: 16px;
        display: block;
      }

      &:hover:not(:disabled) {
        background: var(--background-color);
        color: var(--text-color);
      }

      &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
      }
    }

    :host ::ng-deep {
      .apexcharts-canvas {
        .apexcharts-tooltip {
          border: 1px solid #e9ecef !important;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
          border-radius: 6px !important;

          .apexcharts-tooltip-title {
            background: var(--background-color) !important;
            border-bottom: 1px solid #e9ecef !important;
            font-weight: 600 !important;
          }
        }

        .apexcharts-legend {
          padding: 8px 0 !important;
        }

        .apexcharts-menu {
          border: 1px solid #e9ecef !important;
          border-radius: 6px !important;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }
      }
    }
  `]
})
export class DashboardChartComponent implements OnInit, OnChanges {
  @Input() widget!: DashboardWidget;
  @Input() filters: any = {};

  @ViewChild('chart') chart?: ChartComponent;

  loading: boolean = false;
  error: string | null = null;
  chartOptions: ChartOptions | null = null;
  data: any = null;

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
    if (!this.widget?.id) return;

    this.loading = true;
    this.error = null;

    try {
      const response = await this.dashboardService.getWidgetData(this.widget.id, this.filters);

      if (response.success && response.data?.data) {
        // Corrigido: acessando corretamente o caminho dos dados
        this.data = Utils.keysToUpperCase(response.data.data.data || response.data.data);
        console.log('Dados recebidos:', this.data);
        this.buildChart();
      } else {
        this.error = 'Dados não disponíveis';
      }
    } catch (error: any) {
      console.error('Erro ao carregar dados:', error);
      this.error = error?.message || 'Erro ao carregar dados do gráfico';
    } finally {
      this.loading = false;
    }
  }

  buildChart() {
    if (!this.data || !Array.isArray(this.data) || this.data.length === 0) {
      console.error('Dados inválidos ou vazios');
      this.error = 'Sem dados para exibir';
      return;
    }

    const config = this.widget.config || {};
    const chartType = (this.widget.widget_type?.replace('chart_', '') as any) || 'line';

    console.log('Construindo gráfico tipo:', chartType);
    console.log('Config:', config);

    // Paleta de cores
    const colors = [
      '#667eea', '#10b981', '#8b5cf6', '#f59e0b',
      '#ef4444', '#6b7280', '#3b82f6', '#84cc16'
    ];

    const series = this.buildSeries(chartType);
    console.log('Séries construídas:', series);

    if (!series || series.length === 0) {
      this.error = 'Não foi possível construir as séries do gráfico';
      return;
    }

    // Configuração base
    const options: ChartOptions = {
      series: series,
      chart: {
        type: chartType,
        height: 350,
        toolbar: {
          show: true,
          tools: {
            download: true,
            selection: false,
            zoom: false,
            zoomin: false,
            zoomout: false,
            pan: false,
            reset: false
          }
        },
        animations: {
          enabled: true,
          speed: 400
        },
        fontFamily: 'inherit',
        foreColor: '#6c757d'
      },
      colors: colors,
      dataLabels: {
        enabled: config.enable_data_labels || false,
        style: {
          fontSize: '11px',
          fontWeight: 500
        }
      },
      grid: {
        borderColor: 'rgba(0, 0, 0, 0.05)',
        strokeDashArray: 3,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } }
      },
      legend: {
        show: config.show_legend !== false,
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '12px',
        fontWeight: 400,
        labels: {
          colors: '#6c757d'
        }
      },
      stroke: {
        curve: 'smooth',
        width: 2
      },
      xaxis: {
        categories: [],
        labels: {
          style: {
            colors: '#6c757d',
            fontSize: '11px'
          }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: {
            colors: '#6c757d',
            fontSize: '11px'
          },
          formatter: (value: number) => this.formatYAxis(value)
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: (value: number) => {
            return new Intl.NumberFormat('pt-BR', {
              style: 'currency',
              currency: 'BRL',
              minimumFractionDigits: 2
            }).format(value);
          }
        }
      }
    };

    // Configurações específicas por tipo
    if (chartType === 'pie' || chartType === 'donut') {
      options.labels = this.extractCategories();
      options.plotOptions = {
        pie: {
          donut: {
            size: chartType === 'donut' ? '70%' : undefined,
            labels: {
              show: true,
              name: {
                show: true,
                fontSize: '13px',
                fontWeight: 500
              },
              value: {
                show: true,
                fontSize: '20px',
                fontWeight: 600,
                formatter: (val: any) => {
                  return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                  }).format(Number(val));
                }
              },
              total: {
                show: true,
                label: 'Total',
                formatter: (w: any) => {
                  const total = w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0);
                  return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                  }).format(total);
                }
              }
            }
          }
        }
      };
    } else if (chartType === 'bar') {
      options.xaxis.categories = this.extractCategories();
      options.plotOptions = {
        bar: {
          borderRadius: 4,
          columnWidth: '60%',
          dataLabels: {
            position: 'top'
          }
        }
      };
    } else {
      // line, area, etc
      options.xaxis.categories = this.extractCategories();
    }

    this.chartOptions = options;
    console.log('ChartOptions final:', this.chartOptions);
  }

  buildSeries(chartType: string): ApexAxisChartSeries {
    if (!this.data || !Array.isArray(this.data)) return [];

    const config = this.widget.config || {};
    const valueKey = config.y_axis_label || 'VALUE';
    const labelKey = config.x_axis_label || 'LABEL';

    // Para gráficos de pizza/donut, retorna apenas os valores
    if (chartType === 'pie' || chartType === 'donut') {
      return [{
        name: this.widget.title || 'Dados',
        data: this.data.map((item: any) => {
          const value = item[valueKey] || item.VALOR || item.VALUE || 0;
          return Number(value) || 0;
        })
      }];
    }

    // Para outros gráficos
    const seriesData = this.data.map((item: any) => {
      const value = item[valueKey] || item.VALOR || item.VALUE || 0;
      return Number(value) || 0;
    });

    return [{
      name: this.widget.title || 'Dados',
      data: seriesData
    }];
  }

  extractCategories(): string[] {
    if (!this.data || !Array.isArray(this.data)) return [];

    const config = this.widget.config || {};
    const labelKey = config.x_axis_label || 'LABEL';

    return this.data.map((item: any) => {
      return String(item[labelKey] || item.NAME || item.NOME || item.LABEL || '');
    });
  }

  formatYAxis(value: number): string {
    if (value >= 1000000) {
      return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
      return 'R$ ' + (value / 1000).toFixed(1) + 'K';
    }
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value);
  }
}
