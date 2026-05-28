import { Injectable } from '@angular/core';
import { DoughnutChartConfig } from '../models/domain/charts/doughnut-chart-config.model';
import { ChartConfiguration, ChartData, TooltipOptions } from 'chart.js';
import { LineChartConfig } from '../models/domain/charts/line-chart-config.model';
import { BarChartConfig } from '../models/domain/charts/bar-chart-config.model';
import { StackedBarChartConfig } from '../models/domain/charts/stacked-bar-chart-config.model';
import { MixedChartConfig } from '../models/domain/charts/mixed-chart-config.models';

@Injectable({ providedIn: 'root' })
export class ChartService {
  private cachedStyles: ReturnType<typeof this.getStyles> | null = null;
  private getStylesCached() {
    if (!this.cachedStyles) {
      this.cachedStyles = this.getStyles();
    }
    return this.cachedStyles;
  }

  private getStyles() {
    const styles = getComputedStyle(document.documentElement);

    return {
      primary: styles.getPropertyValue('--card-accent-primary').trim(),
      success: styles.getPropertyValue('--card-accent-success').trim(),
      warning: styles.getPropertyValue('--card-accent-warning').trim(),
      danger: styles.getPropertyValue('--card-accent-danger').trim(),
      text: styles.getPropertyValue('--card-text-secondary').trim(),
      border: styles.getPropertyValue('--card-border-light').trim(),
      bg: styles.getPropertyValue('--card-bg-primary').trim(),
    };
  }

  private buildTooltip(): Partial<TooltipOptions<any>> {
    const colors = this.getStylesCached();

    return {
      backgroundColor: colors.bg,
      titleColor: colors.text,
      bodyColor: colors.text,
    };
  }

  private withOpacity(hex: string, opacity: number) {
    const alpha = Math.round(opacity * 255)
      .toString(16)
      .padStart(2, '0');

    return hex + alpha;
  }

  buildDoughnutChart(config: DoughnutChartConfig): ChartData<'doughnut'> {
    const colors = this.getStylesCached();
    const hasData = config.data.some((v) => Number(v) > 0);
    return {
      labels: hasData ? config.labels : ['Sin datos'],
      datasets: [
        {
          data: hasData ? config.data : [1],
          backgroundColor: hasData
            ? (config.colors ?? [
                colors.primary,
                colors.success,
                colors.warning,
                colors.danger,
              ])
            : [colors.border],
          borderColor: colors.border,
          borderWidth: 0,
        },
      ],
    };
  }

  buildDoughnutOptions(): ChartConfiguration<'doughnut'>['options'] {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: this.buildTooltip(),
      },
    };
  }

  buildLineChart(config: LineChartConfig): ChartData<'line'> {
    const colors = this.getStylesCached();
    const hasData = config.data.some((v) => Number(v) > 0);

    return {
      labels: hasData ? config.labels : ['Sin datos'],
      datasets: [
        {
          label: hasData ? (config.label ?? '') : 'Sin datos',
          data: hasData ? config.data : [0],
          borderColor: colors.primary,
          backgroundColor: this.withOpacity(colors.primary, 0.2),
          fill: true,
          tension: 0.4,
        },
      ],
    };
  }

  buildLineOptions(): ChartConfiguration<'line'>['options'] {
    const colors = this.getStylesCached();

    return {
      responsive: true,
      maintainAspectRatio: false,

      plugins: {
        legend: { display: false },
        tooltip: this.buildTooltip(),
      },

      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: colors.text },
          grid: { color: colors.border },
        },
        x: {
          ticks: { color: colors.text },
          grid: { display: false },
        },
      },

      elements: {
        line: {
          tension: 0.4,
          borderWidth: 3,
        },
        point: {
          radius: 4,
        },
      },
    };
  }
  buildBarChart(config: BarChartConfig): ChartData<'bar'> {
    const colors = this.getStylesCached();

    return {
      labels: config.labels,
      datasets: [
        {
          label: config.label ?? '',
          data: config.data,
          backgroundColor: this.withOpacity(colors.primary, 0.7),
          borderColor: colors.primary,
          borderWidth: 1,
          borderRadius: 6,
          maxBarThickness: 40,
        },
      ],
    };
  }

  buildBarOptions(): ChartConfiguration<'bar'>['options'] {
    const colors = this.getStylesCached();

    return {
      responsive: true,
      maintainAspectRatio: false,

      plugins: {
        legend: { display: false },
        tooltip: this.buildTooltip(),
      },

      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            color: colors.text,
          },
          grid: {
            color: colors.border,
          },
        },
        x: {
          ticks: {
            color: colors.text,
          },
          grid: {
            display: false,
          },
        },
      },
    };
  }

  buildHorizontalBarChart(config: BarChartConfig): ChartData<'bar'> {
    const colors = this.getStylesCached();
    return {
      labels: config.labels,
      datasets: [
        {
          label: config.label ?? '',
          data: config.data,
          backgroundColor: this.withOpacity(colors.primary, 0.7),
          borderColor: colors.primary,
          borderWidth: 1,
          borderRadius: 6,
          maxBarThickness: 40,
        },
      ],
    };
  }
  buildHorizontalBarOptions(): ChartConfiguration<'bar'>['options'] {
    const colors = this.getStylesCached();
    return {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',

      plugins: {
        legend: { display: false },
        tooltip: this.buildTooltip(),
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            color: colors.text,
          },
          grid: {
            color: colors.border,
          },
        },
        y: {
          ticks: {
            color: colors.text,
          },
          grid: {
            display: false,
          },
        },
      },
    };
  }

  buildStackedBarChart(config: StackedBarChartConfig): ChartData<'bar'> {
    const colors = this.getStylesCached();
    const fallbackColors = [
      colors.primary,
      colors.success,
      colors.warning,
      colors.danger,
    ];
    return {
      labels: config.labels,
      datasets: config.datasets.map((dataset, index) => ({
        label: dataset.label,
        data: dataset.data,
        backgroundColor:
          dataset.color || fallbackColors[index % fallbackColors.length],
        borderRadius: 4,
      })),
    };
  }

  buildStackedBarOptions(): ChartConfiguration<'bar'>['options'] {
    const colors = this.getStylesCached();

    return {
      responsive: true,
      maintainAspectRatio: false,

      plugins: {
        tooltip: this.buildTooltip(),
      },

      scales: {
        x: {
          stacked: true,

          ticks: {
            color: colors.text,
          },

          grid: {
            display: false,
          },
        },

        y: {
          stacked: true,

          beginAtZero: true,

          ticks: {
            color: colors.text,
          },

          grid: {
            color: colors.border,
          },
        },
      },
    };
  }

  buildMixedChart(config: MixedChartConfig): ChartData<'bar' | 'line'> {
    const colors = this.getStylesCached();

    const fallbackColors = [
      colors.primary,
      colors.success,
      colors.warning,
      colors.danger,
    ];

    return {
      labels: config.labels,

      datasets: config.datasets.map((dataset, index) => {
        const color =
          dataset.color ?? fallbackColors[index % fallbackColors.length];

        return {
          type: dataset.type,

          label: dataset.label,

          data: dataset.data,

          backgroundColor:
            dataset.type === 'bar'
              ? this.withOpacity(color, 0.7)
              : this.withOpacity(color, 0.2),

          borderColor: color,

          fill: dataset.type === 'line',

          tension: dataset.type === 'line' ? 0.4 : undefined,

          borderRadius: dataset.type === 'bar' ? 6 : undefined,
        };
      }),
    };
  }
  buildMixedChartOptions(): ChartConfiguration<'bar' | 'line'>['options'] {
    const colors = this.getStylesCached();

    return {
      responsive: true,
      maintainAspectRatio: false,

      plugins: {
        tooltip: this.buildTooltip(),
      },

      scales: {
        y: {
          beginAtZero: true,

          ticks: {
            color: colors.text,
          },

          grid: {
            color: colors.border,
          },
        },

        x: {
          ticks: {
            color: colors.text,
          },

          grid: {
            display: false,
          },
        },
      },
    };
  }
}
