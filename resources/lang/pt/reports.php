<?php

/**
 * Traducciones de reportes - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Relatórios',
    'generate' => 'Gerar Relatório',
    'view' => 'Visualizar Relatório',
    'export' => 'Exportar Relatório',

    // Tipos de reportes
    'types' => [
        'users' => 'Relatório de Usuários',
        'roles' => 'Relatório de Funções',
        'permissions' => 'Relatório de Permissões',
        'activity' => 'Relatório de Atividade',
        'logins' => 'Relatório de Logins',
        'audit' => 'Relatório de Auditoria',
        'security' => 'Relatório de Segurança',
        'performance' => 'Relatório de Desempenho',
        'system' => 'Relatório do Sistema',
    ],

    // Períodos
    'periods' => [
        'today' => 'Hoje',
        'yesterday' => 'Ontem',
        'last_7_days' => 'Últimos 7 Dias',
        'last_30_days' => 'Últimos 30 Dias',
        'this_month' => 'Este Mês',
        'last_month' => 'Mês Anterior',
        'this_year' => 'Este Ano',
        'custom' => 'Personalizado',
    ],

    // Formatos de exportación
    'formats' => [
        'pdf' => 'PDF',
        'excel' => 'Excel (XLSX)',
        'csv' => 'CSV',
        'json' => 'JSON',
        'html' => 'HTML',
    ],

    // Configuración del reporto
    'config' => [
        'title' => 'Configuração do Relatório',
        'type' => 'Tipo de Relatório',
        'period' => 'Período',
        'date_from' => 'De',
        'date_to' => 'Até',
        'format' => 'Formato',
        'include_charts' => 'Incluir Gráficos',
        'include_summary' => 'Incluir Resumo',
        'filters' => 'Filtros',
    ],

    // Métricas
    'metrics' => [
        'total' => 'Total',
        'active' => 'Ativos',
        'inactive' => 'Inativos',
        'new' => 'Novos',
        'deleted' => 'Excluídos',
        'growth' => 'Crescimento',
        'percentage' => 'Porcentagem',
        'average' => 'Média',
    ],

    // Reporte de usuarios
    'users' => [
        'total_users' => 'Total de Usuários',
        'new_users' => 'Novos Usuários',
        'active_users' => 'Usuários Ativos',
        'suspended_users' => 'Usuários Suspensos',
        'by_role' => 'Usuários por Função',
        'by_status' => 'Usuários por Status',
        'registration_trend' => 'Tendência de Registro',
    ],

    // Reporte de accesos
    'logins' => [
        'total_logins' => 'Total de Logins',
        'successful_logins' => 'Logins Bem-sucedidos',
        'failed_logins' => 'Logins Falhados',
        'unique_users' => 'Usuários Únicos',
        'by_hour' => 'Logins por Hora',
        'by_day' => 'Logins por Dia',
        'by_location' => 'Logins por Localização',
        'peak_times' => 'Horários de Pico',
    ],

    // Reporte de seguridad
    'security' => [
        'failed_attempts' => 'Tentativas Falhadas',
        'locked_accounts' => 'Contas Bloqueadas',
        'suspicious_activity' => 'Atividade Suspeita',
        'ip_blocks' => 'IPs Bloqueados',
        'password_resets' => 'Redefinições de Senha',
        'mfa_usage' => 'Uso de 2FA',
    ],

    // Reporte de actividad
    'activity' => [
        'user_actions' => 'Ações de Usuários',
        'most_active_users' => 'Usuários Mais Ativos',
        'action_types' => 'Tipos de Ação',
        'activity_timeline' => 'Linha do Tempo',
    ],

    // Mensajes
    'generating' => 'Gerando relatório...',
    'generated_successfully' => 'Relatório gerado com sucesso',
    'generation_failed' => 'Erro ao gerar relatório',
    'no_data' => 'Nenhum dado disponível para o período selecionado',
    'exported_successfully' => 'Relatório exportado com sucesso',

    // Acciones
    'generate_button' => 'Gerar',
    'export_button' => 'Exportar',
    'print_button' => 'Imprimir',
    'share_button' => 'Compartilhar',
    'schedule_button' => 'Agendar',
    'download_button' => 'Baixar',

    // Reportes programados
    'scheduled' => [
        'title' => 'Relatórios Agendados',
        'create' => 'Agendar Relatório',
        'frequency' => 'Frequência',
        'daily' => 'Diário',
        'weekly' => 'Semanal',
        'monthly' => 'Mensal',
        'recipients' => 'Destinatários',
        'next_run' => 'Próxima Execução',
        'last_run' => 'Última Execução',
    ],
];
