import { Component, Input, OnInit, OnDestroy, Output, EventEmitter, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subject, interval, takeUntil } from 'rxjs';
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
  NgApexchartsModule
} from 'ng-apexcharts';
import {DashboardService, DashboardWidget} from '../../../../services/dashboard.service';
import {ToastService} from '../../../../components/toast/toast.service';
import {Utils} from '../../../../services/utils.service';

export type ChartOptions = {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  xaxis: ApexXAxis;
  yaxis: ApexYAxis;
  stroke: ApexStroke;
  dataLabels: ApexDataLabels;
  legend: ApexLegend;
  colors: string[];
  title: ApexTitleSubtitle;
};

@Component({
  selector: 'app-dashboard-widget-renderer',
  imports: [CommonModule, NgApexchartsModule],
  templateUrl: './dashboard-widget-renderer.component.html',
  standalone: true,
  styleUrl: './dashboard-widget-renderer.component.scss'
})
export class DashboardWidgetRendererComponent implements OnInit, OnDestroy {
  @Input() widget!: DashboardWidget;
  @Input() filters: any = {};

  @ViewChild('chart') chart?: ChartComponent;

  private destroy$ = new Subject<void>();

  loading: boolean = false;
  data: any = null;
  error: string | null = null;
  chartOptions: Partial<ChartOptions> | null = null;

  constructor(
    private dashboardService: DashboardService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    this.loadData();
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * Carrega dados do widget
   */
  async loadData() {
    if (!this.widget.id || !this.widget.dynamic_query_id) {
      return;
    }

    this.loading = true;
    this.error = null;

    try {
      const response = await this.dashboardService.getWidgetData(this.widget.id, this.filters);

      if (response.success && response.data) {
        this.data = Utils.keysToUpperCase(response.data.data);
        this.prepareChart();
      }
    } catch (error: any) {
      this.error = error?.message || 'Erro ao carregar dados';
      this.toast.error(this.error!);
    } finally {
      this.loading = false;
    }
  }

  /**
   * Prepara configuração do gráfico baseado no tipo
   */
  prepareChart() {
    if (!this.data) return;

    const type = this.widget.widget_type;

    if (type.startsWith('chart_')) {
      this.chartOptions = this.buildChartOptions();
    }
  }

  /**
   * Constrói options do ApexCharts
   */
  buildChartOptions(): Partial<ChartOptions> {
    const type = this.widget.widget_type.replace('chart_', '') as any;

    return {
      series: this.buildSeries(),
      chart: {
        type: type,
        height: 350,
        toolbar: {
          show: true
        },
        animations: {
          enabled: true,
          speed: 800
        },
      },
      colors: ['#667eea', '#764ba2', '#f093fb', '#4facfe'],
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 2
      },
      xaxis: {
        categories: this.extractCategories()
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right'
      },
      title: {
        text: this.widget.title || '',
        align: 'left',
        style: {
          fontSize: '16px',
          fontWeight: 600
        }
      }
    };
  }

  /**
   * Extrai séries de dados
   * Formatado esperado:
   * [
   *  { name: 'Série 1', data: [10, 20, 30] },
   *  { name: 'Série 2', data: [15, 25, 35] }
   *  ]
   */
  buildSeries(): ApexAxisChartSeries {
    if (!this.data) return [];

    // Se data já estiver no formato ApexCharts
    if (Array.isArray(this.data) && this.data[0]?.name && this.data[0]?.data) {
      return this.data;
    }

    console.log('Converting data to ApexCharts format', this.data);

    // Converte data simples para formato ApexCharts
    return [{
      name: this.widget.title || 'Dados',
      data: Array.isArray(this.data) ? this.data.map((d: any) => {
        return d.VALUE || d.VALOR || 0
      }) : []
    }];
  }

  /**
   * Extrai categorias para eixo X
   */
  extractCategories(): string[] {
    if (!this.data || !Array.isArray(this.data)) return [];

    return this.data.map((d: any) => {
      return d.LABEL || d.NAME || d.NOME ||d.CATEGORIA || d.CATEGORY || '';
    });
  }

  /**
   * Renderiza widget tipo métrica
   */
  renderMetricCard() {
    if (!this.data) return null;

    const value = this.data.value || this.data[0]?.value || 0;
    const label = this.data.label || this.widget.title;
    const icon = this.data.icon || 'bx-trending-up';
    const trend = this.data.trend; // Ex: { value: 12, direction: 'up' }

    return { value, label, icon, trend };
  }

  /**
   * Renderiza widget tipo tabela
   */
  renderTable() {
    if (!this.data || !Array.isArray(this.data)) return null;

    return {
      columns: Object.keys(this.data[0] || {}),
      rows: this.data
    };
  }

  /**
   * Renderiza widget tipo lista
   */
  renderList() {
    if (!this.data || !Array.isArray(this.data)) return null;
    return this.data;
  }

  /**
   * Renderiza widget tipo progress
   */
  renderProgress() {
    if (!this.data) return null;

    return {
      value: this.data.value || 0,
      max: this.data.max || 100,
      label: this.data.label || this.widget.title
    };
  }

  /**
   * Renderiza widget tipo gauge
   */
  renderGauge() {
    if (!this.data) return null;

    return {
      value: this.data.value || 0,
      min: this.data.min || 0,
      max: this.data.max || 100,
      label: this.data.label || this.widget.title
    };
  }

  /**
   * Verifica se é tipo chart
   */
  isChartType(): boolean {
    return this.widget.widget_type.startsWith('chart_');
  }

  /**
   * Atualiza widget manualmente
   */
  refresh() {
    this.loadData();
  }
}
