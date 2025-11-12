<?php

/**
 * Traducciones de auditoría - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Registro de Auditoria',
    'view' => 'Visualizar Auditoria',
    'search' => 'Pesquisar em Auditoria',
    'trail' => 'Rastro de Auditoria',

    // Tipos de eventos
    'event_types' => [
        'create' => 'Criar',
        'read' => 'Ler',
        'update' => 'Atualizar',
        'delete' => 'Excluir',
        'restore' => 'Restaurar',
        'login' => 'Entrar',
        'logout' => 'Sair',
        'failed_login' => 'Falha ao Entrar',
        'password_change' => 'Alteração de Senha',
        'password_reset' => 'Redefinição de Senha',
        'permission_change' => 'Alteração de Permissão',
        'role_change' => 'Alteração de Função',
        'settings_change' => 'Alteração de Configuração',
        'export' => 'Exportar',
        'import' => 'Importar',
    ],

    // Entidades auditadas
    'entities' => [
        'user' => 'Usuário',
        'role' => 'Função',
        'permission' => 'Permissão',
        'setting' => 'Configuração',
        'log' => 'Registro',
        'session' => 'Sessão',
        'plugin' => 'Plugin',
        'report' => 'Relatório',
    ],

    // Campos
    'id' => 'ID',
    'timestamp' => 'Data e Hora',
    'user' => 'Usuário',
    'event' => 'Evento',
    'entity_type' => 'Tipo de Entidade',
    'entity_id' => 'ID da Entidade',
    'description' => 'Descrição',
    'ip_address' => 'Endereço IP',
    'user_agent' => 'Navegador',
    'old_values' => 'Valores Anteriores',
    'new_values' => 'Valores Novos',
    'changes' => 'Alterações',
    'details' => 'Detalhes',

    // Filtros
    'filters' => [
        'event_type' => 'Tipo de Evento',
        'entity_type' => 'Tipo de Entidade',
        'user' => 'Usuário',
        'date_from' => 'De',
        'date_to' => 'Até',
        'ip' => 'IP',
    ],

    // Descripciones de eventos
    'descriptions' => [
        'user_created' => ':user criou o usuário :target',
        'user_updated' => ':user atualizou o usuário :target',
        'user_deleted' => ':user excluiu o usuário :target',
        'user_restored' => ':user restaurou o usuário :target',
        'role_created' => ':user criou a função :target',
        'role_updated' => ':user atualizou a função :target',
        'role_deleted' => ':user excluiu a função :target',
        'role_assigned' => ':user atribuiu a função :role ao usuário :target',
        'role_removed' => ':user removeu a função :role do usuário :target',
        'permission_granted' => ':user concedeu a permissão :permission',
        'permission_revoked' => ':user revogou a permissão :permission',
        'settings_updated' => ':user atualizou a configuração :setting',
        'login_success' => ':user entrou de :ip',
        'login_failed' => 'Tentativa de login falhada para :username de :ip',
        'logout' => ':user saiu',
        'password_changed' => ':user alterou sua senha',
        'password_reset' => ':user redefiniu a senha de :target',
        'data_exported' => ':user exportou :entity',
        'data_imported' => ':user importou :entity',
    ],

    // Acciones
    'view_details' => 'Visualizar Detalhes',
    'view_changes' => 'Visualizar Alterações',
    'export' => 'Exportar Auditoria',
    'filter' => 'Filtrar',
    'clear_filters' => 'Limpar Filtros',
    'refresh' => 'Atualizar',

    // Mensajes
    'no_records' => 'Nenhum registro de auditoria para exibir',
    'loading' => 'Carregando auditoria...',
    'exported_successfully' => 'Auditoria exportada com sucesso',

    // Estadísticas
    'stats' => [
        'total_events' => 'Total de Eventos',
        'events_today' => 'Eventos Hoje',
        'unique_users' => 'Usuários Únicos',
        'by_event_type' => 'Por Tipo de Evento',
        'by_entity' => 'Por Entidade',
        'most_active_users' => 'Usuários Mais Ativos',
        'recent_activity' => 'Atividade Recente',
    ],

    // Detalles de cambios
    'change_details' => [
        'field' => 'Campo',
        'old_value' => 'Valor Anterior',
        'new_value' => 'Valor Novo',
        'no_changes' => 'Nenhuma alteração registrada',
    ],

    // Tabla
    'table' => [
        'showing' => 'Mostrando :from a :to de :total registros',
        'per_page' => 'Por página',
        'no_results' => 'Nenhum resultado encontrado',
    ],

    // Períodos
    'periods' => [
        'today' => 'Hoje',
        'yesterday' => 'Ontem',
        'last_7_days' => 'Últimos 7 Dias',
        'last_30_days' => 'Últimos 30 Dias',
        'this_month' => 'Este Mês',
        'last_month' => 'Mês Anterior',
        'custom' => 'Personalizado',
    ],
];
