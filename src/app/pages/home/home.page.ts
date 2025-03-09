import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import ApexCharts from 'apexcharts';

@Component({
  selector: 'app-home',
  templateUrl: './home.page.html',
  styleUrls: ['./home.page.scss'],
  standalone: true,
  imports: [CommonModule]
})
export class HomePage implements OnInit, AfterViewInit {
  @ViewChild('chartCategories') chartCategories!: ElementRef;
  @ViewChild('chartTrend') chartTrend!: ElementRef;
  @ViewChild('chartStatus') chartStatus!: ElementRef;
  @ViewChild('chartPriority') chartPriority!: ElementRef;

  constructor() { }

  ngOnInit(): void { }

  ngAfterViewInit(): void {
    this.initCategoriesChart();
    this.initTrendChart();
    this.initStatusChart();
    this.initPriorityChart();
  }

  private initCategoriesChart(): void {
    const options = {
      series: [{
        data: [44, 55, 41, 37, 22]
      }],
      chart: {
        type: 'bar',
        height: 300,
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          borderRadius: 4,
          horizontal: true,
        }
      },
      colors: ['#2196f3'],
      dataLabels: {
        enabled: true
      },
      xaxis: {
        categories: ['Hardware', 'Software', 'Rede', 'Acesso', 'Outros'],
      }
    };

    const chart = new ApexCharts(this.chartCategories.nativeElement, options);
    chart.render();
  }

  private initTrendChart(): void {
    const options = {
      series: [{
        name: 'Chamados',
        data: [31, 40, 28, 51, 42, 109, 100]
      }],
      chart: {
        height: 300,
        type: 'area',
        toolbar: {
          show: false
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth'
      },
      colors: ['#4caf50'],
      xaxis: {
        type: 'datetime',
        categories: [
          "2024-03-17T00:00:00.000Z",
          "2024-03-18T00:00:00.000Z",
          "2024-03-19T00:00:00.000Z",
          "2024-03-20T00:00:00.000Z",
          "2024-03-21T00:00:00.000Z",
          "2024-03-22T00:00:00.000Z",
          "2024-03-23T00:00:00.000Z"
        ]
      },
      tooltip: {
        x: {
          format: 'dd/MM/yy'
        }
      }
    };

    const chart = new ApexCharts(this.chartTrend.nativeElement, options);
    chart.render();
  }

  private initStatusChart(): void {
    const options = {
      series: [44, 55, 13],
      chart: {
        type: 'donut',
        height: 300
      },
      labels: ['Pendente', 'Resolvido', 'Em Andamento'],
      colors: ['#f57c00', '#4caf50', '#2196f3'],
      legend: {
        position: 'bottom'
      },
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200
          },
          legend: {
            position: 'bottom'
          }
        }
      }]
    };

    const chart = new ApexCharts(this.chartStatus.nativeElement, options);
    chart.render();
  }

  private initPriorityChart(): void {
    const options = {
      series: [{
        name: 'Chamados',
        data: [20, 45, 35]
      }],
      chart: {
        type: 'radar',
        height: 300,
        toolbar: {
          show: false
        }
      },
      xaxis: {
        categories: ['Alta', 'Média', 'Baixa']
      },
      fill: {
        opacity: 0.5
      },
      colors: ['#f44336']
    };

    const chart = new ApexCharts(this.chartPriority.nativeElement, options);
    chart.render();
  }
}
