<?php

namespace App\Services\Dashboard;

use App\Services\Parameter\ServiceParameter;
use App\Services\Parameter\ServiceParameterManager;

class WidgetParameterFactory
{
    /**
     * Retorna os parâmetros necessários para cada tipo de widget
     */
    public static function getParametersForWidget(string $widgetType): ServiceParameterManager
    {
        $manager = new ServiceParameterManager();

        return match ($widgetType) {
            'chart_line', 'chart_pie', 'chart_bar', 'chart_donut', 'chart_area', 'chart_radar'
            => self::getChartParameters($manager),
            'table' => self::getTableParameters($manager),
            'metric_card' => self::getMetricCardParameters($manager),
            default => $manager,
        };
    }

    /**
     * Parâmetros para gráficos
     *
     * @param ServiceParameterManager $manager
     * @return ServiceParameterManager
     */
    private static function getChartParameters(ServiceParameterManager $manager): ServiceParameterManager
    {
        return $manager->addMany([
            ServiceParameter::boolean(
                name: 'show_legend',
                defaultValue: true
            )
                ->withLabel('Exibir Legenda')
                ->withGroup('Aparência'),

            ServiceParameter::boolean(
                name: 'enable_data_labels',
                defaultValue: false
            )
                ->withLabel('Habilitar Rótulos de Dados')
                ->withGroup('Aparência'),

            ServiceParameter::text(
                name: 'x_axis_label',
                description: 'Rótulo do eixo X'
            )
                ->withLabel('Rótulo do Eixo X')
                ->withGroup('Configuração de Dados')
                ->withPlaceholder('ex: Tempo, Categorias'),

            ServiceParameter::text(
                name: 'y_axis_label',
                description: 'Rótulo do eixo Y'
            )
                ->withLabel('Rótulo do Eixo Y')
                ->withGroup('Configuração de Dados')
                ->withPlaceholder('ex: Vendas, Quantidade'),

            ServiceParameter::seriesConfig(
                name: 'data_series',
                description: 'Séries de dados para o gráfico'
            )
                ->withLabel('Séries de Dados')
                ->withGroup('Configuração de Dados'),
        ]);
    }

    /**
     * Parâmetros para tabela
     */
    private static function getTableParameters(ServiceParameterManager $manager): ServiceParameterManager
    {
        return $manager->addMany([

            // Grupo: Exportação
            ServiceParameter::boolean(
                name: 'enable_export',
                defaultValue: false
            )
                ->withLabel('Habilitar Exportação')
                ->withGroup('Exportação'),

        ]);
    }

    /**
     * Parâmetros para card de métrica
     */
    private static function getMetricCardParameters(ServiceParameterManager $manager): ServiceParameterManager
    {
        return $manager->addMany([

            ServiceParameter::select(
                name: 'metric_type',
                options: [
                    'simple'     => 'Simples',
                    'percentage' => 'Porcentagem',
                    'comparison' => 'Comparação'
                ],
                defaultValue: 'simple',
                description: 'Tipo de métrica a ser exibida'
            )
                ->withLabel('Tipo de Métrica')
                ->withGroup('Configuração'),

            // Grupo: Dados
            ServiceParameter::text(
                name: 'metric_label',
                required: true,
                description: 'Nome da métrica exibida'
            )
                ->withLabel('Nome da Métrica')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: Total de Vendas'),

            ServiceParameter::text(
                name: 'data_column',
                description: 'Coluna de onde extrair o valor. Padrão: primeiro valor da consulta.'
            )
                ->withLabel('Coluna de Dados')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: total, count'),

            ServiceParameter::text(
                name: 'comparison_label',
                description: 'Nome métrica de comparação'
            )
                ->withLabel('Rótulo de Comparação')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: Mês Anterior'),

            ServiceParameter::text(
                name: 'comparison_column',
                description: 'Coluna de onde extrair o valor de comparação'
            )
                ->withLabel('Coluna de Comparação')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: previous_total, previous_count'),

            ServiceParameter::text(
                name: 'target',
                description: 'Valor alvo ou coluna para comparação de meta'
            )
                ->withLabel('Meta / Alvo')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: 10000 ou target_value'),

            // Grupo: Aparência
            ServiceParameter::text(
                name: 'icon',
                description: 'Classe do ícone (ex: bx-dollar, bx-user)'
            )
                ->withLabel('Ícone')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: bx-dollar'),

        ]);
    }

    /**
     * Lista todos os tipos de widgets disponíveis com seus metadados
     */
    public static function getAvailableWidgets(): array
    {
        return [
            [
                'value'       => 'chart_line',
                'label'       => 'Gráfico de Linha',
                'icon'        => 'bx-line-chart',
                'description' => 'Gráfico de linhas para visualizar tendências ao longo do tempo',
                'category'    => 'charts'
            ],
            [
                'value'       => 'chart_bar',
                'label'       => 'Gráfico de Barras',
                'icon'        => 'bx-bar-chart',
                'description' => 'Gráfico de barras para comparar valores entre categorias',
                'category'    => 'charts'
            ],
            [
                'value'       => 'chart_pie',
                'label'       => 'Gráfico de Pizza',
                'icon'        => 'bx-pie-chart-alt',
                'description' => 'Gráfico de pizza para mostrar proporções',
                'category'    => 'charts'
            ],
            [
                'value'       => 'table',
                'label'       => 'Tabela',
                'icon'        => 'bx-table',
                'description' => 'Tabela de dados com recursos de ordenação, filtro e paginação',
                'category'    => 'data'
            ],
            [
                'value'       => 'metric_card',
                'label'       => 'Card de Métrica',
                'icon'        => 'bx-card',
                'description' => 'Card para exibir uma métrica principal com comparações',
                'category'    => 'metrics'
            ],
        ];
    }
}