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
            'chart_line' => self::getChartLineParameters($manager),
            'chart_bar' => self::getChartBarParameters($manager),
            'chart_pie' => self::getChartPieParameters($manager),
            'table' => self::getTableParameters($manager),
            'metric_card' => self::getMetricCardParameters($manager),
            default => $manager,
        };
    }

    /**
     * Parâmetros para gráfico de linha
     */
    private static function getChartLineParameters(ServiceParameterManager $manager): ServiceParameterManager
    {
        return $manager->addMany([
            // Grupo: Fonte de Dados
            ServiceParameter::select(
                name: 'data_source_type',
                options: ['column' => 'Coluna Específica', 'custom' => 'Configuração Manual'],
                required: true,
                defaultValue: 'column',
                description: 'Define como os dados serão obtidos'
            )
                ->withLabel('Tipo de Fonte de Dados')
                ->withGroup('Dados'),

            ServiceParameter::text(
                name: 'data_column_x',
                required: false,
                description: 'Nome da coluna para eixo X'
            )
                ->withLabel('Coluna Eixo X')
                ->withGroup('Dados')
                ->withPlaceholder('ex: date, category')
                ->withDependencies(['data_source_type' => 'column']),

            ServiceParameter::seriesConfig(
                name: 'series',
                required: true,
                defaultValue: [],
                description: 'Configuração das séries do gráfico'
            )
                ->withLabel('Séries')
                ->withGroup('Dados'),

            // Grupo: Aparência
            ServiceParameter::text(
                name: 'chart_title',
                required: false,
                description: 'Título do gráfico'
            )
                ->withLabel('Título do Gráfico')
                ->withGroup('Aparência'),

            ServiceParameter::boolean(
                name: 'show_legend',
                defaultValue: true,
                description: 'Exibir legenda do gráfico'
            )
                ->withLabel('Mostrar Legenda')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'legend_position',
                options: [
                    'top' => 'Topo',
                    'bottom' => 'Rodapé',
                    'left' => 'Esquerda',
                    'right' => 'Direita'
                ],
                defaultValue: 'bottom'
            )
                ->withLabel('Posição da Legenda')
                ->withGroup('Aparência')
                ->withDependencies(['show_legend' => true]),

            ServiceParameter::boolean(
                name: 'show_grid',
                defaultValue: true,
                description: 'Exibir grade no gráfico'
            )
                ->withLabel('Mostrar Grade')
                ->withGroup('Aparência'),

            ServiceParameter::boolean(
                name: 'show_data_labels',
                defaultValue: false,
                description: 'Exibir valores nos pontos'
            )
                ->withLabel('Mostrar Valores')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'curve_type',
                options: [
                    'smooth' => 'Suave',
                    'straight' => 'Reta',
                    'stepline' => 'Degrau'
                ],
                defaultValue: 'smooth'
            )
                ->withLabel('Tipo de Curva')
                ->withGroup('Aparência'),

            // Grupo: Eixos
            ServiceParameter::text(
                name: 'x_label',
                required: false
            )
                ->withLabel('Rótulo Eixo X')
                ->withGroup('Eixos'),

            ServiceParameter::text(
                name: 'y_label',
                required: false
            )
                ->withLabel('Rótulo Eixo Y')
                ->withGroup('Eixos'),

            ServiceParameter::boolean(
                name: 'x_categories',
                defaultValue: false,
                description: 'Usar valores do eixo X como categorias'
            )
                ->withLabel('Eixo X Categórico')
                ->withGroup('Eixos'),

        ]);
    }

    /**
     * Parâmetros para gráfico de barras
     */
    private static function getChartBarParameters(ServiceParameterManager $manager): ServiceParameterManager
    {
        return $manager->addMany([
            // Grupo: Fonte de Dados
            ServiceParameter::select(
                name: 'data_source_type',
                options: ['column' => 'Coluna Específica', 'custom' => 'Configuração Manual'],
                required: true,
                defaultValue: 'column'
            )
                ->withLabel('Tipo de Fonte de Dados')
                ->withGroup('Dados'),

            ServiceParameter::text(
                name: 'data_column_x',
                required: false
            )
                ->withLabel('Coluna Eixo X')
                ->withGroup('Dados')
                ->withPlaceholder('ex: category, name')
                ->withDependencies(['data_source_type' => 'column']),

            ServiceParameter::array(
                name: 'series',
                required: true,
                defaultValue: []
            )
                ->withLabel('Séries')
                ->withGroup('Dados'),

            // Grupo: Aparência
            ServiceParameter::text(
                name: 'chart_title',
                required: false
            )
                ->withLabel('Título do Gráfico')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'orientation',
                options: ['vertical' => 'Vertical', 'horizontal' => 'Horizontal'],
                defaultValue: 'vertical'
            )
                ->withLabel('Orientação')
                ->withGroup('Aparência'),

            ServiceParameter::boolean(
                name: 'stacked',
                defaultValue: false,
                description: 'Empilhar barras'
            )
                ->withLabel('Barras Empilhadas')
                ->withGroup('Aparência'),

            ServiceParameter::boolean(
                name: 'show_legend',
                defaultValue: true
            )
                ->withLabel('Mostrar Legenda')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'legend_position',
                options: [
                    'top' => 'Topo',
                    'bottom' => 'Rodapé',
                    'left' => 'Esquerda',
                    'right' => 'Direita'
                ],
                defaultValue: 'bottom'
            )
                ->withLabel('Posição da Legenda')
                ->withGroup('Aparência')
                ->withDependencies(['show_legend' => true]),

            ServiceParameter::boolean(
                name: 'show_data_labels',
                defaultValue: false
            )
                ->withLabel('Mostrar Valores')
                ->withGroup('Aparência'),

            // Grupo: Eixos
            ServiceParameter::text(
                name: 'x_label',
                required: false
            )
                // explique oq é o Rótulo do Eixo X
                ->withLabel('Rótulo Eixo X (Isso é: o título que aparece abaixo das categorias)')
                ->withGroup('Eixos'),

            ServiceParameter::text(
                name: 'y_label',
                required: false
            )
                ->withLabel('Rótulo Eixo Y (Isso é: o título que aparece ao lado dos valores)')
                ->withGroup('Eixos'),

        ]);
    }

    /**
     * Parâmetros para gráfico de pizza
     */
    private static function getChartPieParameters(ServiceParameterManager $manager): ServiceParameterManager
    {
        return $manager->addMany([
            // Grupo: Fonte de Dados
            ServiceParameter::select(
                name: 'data_source_type',
                options: ['column' => 'Coluna Específica', 'custom' => 'Configuração Manual'],
                required: true,
                defaultValue: 'column'
            )
                ->withLabel('Tipo de Fonte de Dados')
                ->withGroup('Dados'),

            ServiceParameter::text(
                name: 'data_column_label',
                required: false,
                description: 'Coluna para os rótulos das fatias'
            )
                ->withLabel('Coluna de Rótulos')
                ->withGroup('Dados')
                ->withPlaceholder('ex: category, name')
                ->withDependencies(['data_source_type' => 'column']),

            ServiceParameter::text(
                name: 'data_column_value',
                required: false,
                description: 'Coluna para os valores das fatias'
            )
                ->withLabel('Coluna de Valores')
                ->withGroup('Dados')
                ->withPlaceholder('ex: total, count')
                ->withDependencies(['data_source_type' => 'column']),

            ServiceParameter::array(
                name: 'custom_data',
                required: false,
                defaultValue: [],
                description: 'Dados personalizados: [{label: "A", value: 100}]'
            )
                ->withLabel('Dados Personalizados')
                ->withGroup('Dados')
                ->withDependencies(['data_source_type' => 'custom']),

            // Grupo: Aparência
            ServiceParameter::text(
                name: 'chart_title',
                required: false
            )
                ->withLabel('Título do Gráfico')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'chart_type',
                options: [
                    'pie' => 'Pizza',
                    'donut' => 'Rosca',
                    'radialBar' => 'Barra Radial'
                ],
                defaultValue: 'pie'
            )
                ->withLabel('Tipo de Gráfico')
                ->withGroup('Aparência'),


            ServiceParameter::boolean(
                name: 'show_legend',
                defaultValue: true
            )
                ->withLabel('Mostrar Legenda')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'legend_position',
                options: [
                    'top' => 'Topo',
                    'bottom' => 'Rodapé',
                    'left' => 'Esquerda',
                    'right' => 'Direita'
                ],
                defaultValue: 'right'
            )
                ->withLabel('Posição da Legenda')
                ->withGroup('Aparência')
                ->withDependencies(['show_legend' => true]),

            ServiceParameter::boolean(
                name: 'show_data_labels',
                defaultValue: true
            )
                ->withLabel('Mostrar Valores')
                ->withGroup('Aparência'),

            ServiceParameter::select(
                name: 'data_labels_format',
                options: [
                    'percent' => 'Porcentagem',
                    'value' => 'Valor Absoluto',
                    'both' => 'Ambos'
                ],
                defaultValue: 'percent'
            )
                ->withLabel('Formato dos Rótulos')
                ->withGroup('Aparência')
                ->withDependencies(['show_data_labels' => true]),

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
                required: false,
                description: 'Coluna de onde extrair o valor. Padrão: primeiro valor da consulta.'
            )
                ->withLabel('Coluna de Dados')
                ->withGroup('Configuração')
                ->withPlaceholder('ex: total, count'),

            // Grupo: Aparência
            ServiceParameter::text(
                name: 'icon',
                required: false,
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
                'value' => 'chart_line',
                'label' => 'Gráfico de Linha',
                'icon' => 'bx-line-chart',
                'description' => 'Gráfico de linhas para visualizar tendências ao longo do tempo',
                'category' => 'charts'
            ],
            [
                'value' => 'chart_bar',
                'label' => 'Gráfico de Barras',
                'icon' => 'bx-bar-chart',
                'description' => 'Gráfico de barras para comparar valores entre categorias',
                'category' => 'charts'
            ],
            [
                'value' => 'chart_pie',
                'label' => 'Gráfico de Pizza',
                'icon' => 'bx-pie-chart-alt',
                'description' => 'Gráfico de pizza para mostrar proporções',
                'category' => 'charts'
            ],
            [
                'value' => 'table',
                'label' => 'Tabela',
                'icon' => 'bx-table',
                'description' => 'Tabela de dados com recursos de ordenação, filtro e paginação',
                'category' => 'data'
            ],
            [
                'value' => 'metric_card',
                'label' => 'Card de Métrica',
                'icon' => 'bx-card',
                'description' => 'Card para exibir uma métrica principal com comparações',
                'category' => 'metrics'
            ],
        ];
    }
}