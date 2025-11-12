<?php

/**
 * Traducciones de registros del sistema - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Registros do Sistema',
    'view' => 'Visualizar Registros',
    'search' => 'Pesquisar Registros',

    // Niveles de log
    'levels' => [
        'emergency' => 'Emergência',
        'alert' => 'Alerta',
        'critical' => 'Crítico',
        'error' => 'Erro',
        'warning' => 'Aviso',
        'notice' => 'Aviso',
        'info' => 'Informação',
        'debug' => 'Depuração',
    ],

    // Canales
    'channels' => [
        'application' => 'Aplicação',
        'security' => 'Segurança',
        'database' => 'Banco de Dados',
        'authentication' => 'Autenticação',
        'authorization' => 'Autorização',
        'api' => 'API',
        'email' => 'Email',
        'cache' => 'Cache',
        'queue' => 'Fila',
    ],

    // Filtros
    'filters' => [
        'level' => 'Nível',
        'channel' => 'Canal',
        'date_from' => 'De',
        'date_to' => 'Até',
        'user' => 'Usuário',
        'ip' => 'Endereço IP',
        'message' => 'Mensagem',
    ],

    // Campos
    'timestamp' => 'Data e Hora',
    'level' => 'Nível',
    'channel' => 'Canal',
    'message' => 'Mensagem',
    'context' => 'Contexto',
    'user' => 'Usuário',
    'ip' => 'IP',
    'user_agent' => 'Navegador',
    'url' => 'URL',
    'method' => 'Método',
    'stack_trace' => 'Rastreamento de Pilha',

    // Tipos de eventos
    'events' => [
        'user_login' => 'Login do usuário',
        'user_logout' => 'Logout do usuário',
        'login_failed' => 'Tentativa de login falhada',
        'user_created' => 'Usuário criado',
        'user_updated' => 'Usuário atualizado',
        'user_deleted' => 'Usuário excluído',
        'password_changed' => 'Senha alterada',
        'password_reset' => 'Senha redefinida',
        'role_assigned' => 'Função atribuída',
        'permission_changed' => 'Permissão alterada',
        'settings_updated' => 'Configurações atualizadas',
        'file_uploaded' => 'Arquivo carregado',
        'database_query' => 'Consulta de banco de dados',
        'api_request' => 'Solicitação API',
        'error_occurred' => 'Erro ocorreu',
        'exception_thrown' => 'Exceção lançada',
    ],

    // Acciones
    'view_details' => 'Visualizar Detalhes',
    'export' => 'Exportar',
    'clear_logs' => 'Limpar Registros',
    'download' => 'Baixar',
    'refresh' => 'Atualizar',

    // Mensajes
    'no_logs' => 'Nenhum registro disponível para o período selecionado',
    'loading' => 'Carregando registros...',
    'exported_successfully' => 'Registros exportados com sucesso',
    'cleared_successfully' => 'Registros limpos com sucesso',
    'clear_confirm' => 'Tem certeza de que deseja limpar os registros?',
    'clear_warning' => 'Esta ação não pode ser desfeita',

    // Estadísticas
    'stats' => [
        'total_entries' => 'Total de Entradas',
        'errors_today' => 'Erros Hoje',
        'warnings_today' => 'Avisos Hoje',
        'by_level' => 'Por Nível',
        'by_channel' => 'Por Canal',
        'most_common' => 'Mais Comuns',
    ],

    // Configuración de logs
    'configuration' => [
        'title' => 'Configuração de Registros',
        'log_level' => 'Nível de Registro',
        'log_channel' => 'Canal de Registro',
        'max_files' => 'Arquivos Máximos',
        'max_file_size' => 'Tamanho Máximo de Arquivo',
        'rotation' => 'Rotação de Arquivos',
        'daily' => 'Diária',
        'weekly' => 'Semanal',
        'monthly' => 'Mensal',
    ],

    // Tabla
    'table' => [
        'showing' => 'Mostrando :from a :to de :total registros',
        'per_page' => 'Por página',
        'no_results' => 'Nenhum resultado encontrado',
    ],
];
