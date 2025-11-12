<?php

/**
 * Traducciones de mensajes de error - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Errores HTTP
    'http' => [
        '400' => 'Solicitação Inválida',
        '401' => 'Não Autorizado',
        '403' => 'Acesso Proibido',
        '404' => 'Página Não Encontrada',
        '405' => 'Método Não Permitido',
        '408' => 'Tempo de Espera Esgotado',
        '419' => 'Sessão Expirada',
        '429' => 'Muitas Solicitações',
        '500' => 'Erro Interno do Servidor',
        '502' => 'Gateway Inválido',
        '503' => 'Serviço Indisponível',
        '504' => 'Tempo de Espera de Gateway',
    ],

    // Mensajes HTTP detallados
    'http_messages' => [
        '400' => 'A solicitação não pôde ser processada devido a um erro do cliente',
        '401' => 'Você deve fazer login para acessar este recurso',
        '403' => 'Você não tem permissão para acessar este recurso',
        '404' => 'A página que você procura não existe ou foi movida',
        '405' => 'O método HTTP usado não é permitido para esta rota',
        '408' => 'A solicitação levou muito tempo para ser processada',
        '419' => 'Sua sessão expirou. Por favor, faça login novamente',
        '429' => 'Você fez muitas solicitações. Por favor, tente mais tarde',
        '500' => 'Um erro inesperado ocorreu no servidor',
        '502' => 'Erro de comunicação com o servidor',
        '503' => 'O serviço está temporariamente indisponível',
        '504' => 'O servidor não respondeu a tempo',
    ],

    // Errores de autenticación
    'auth' => [
        'invalid_credentials' => 'Credenciais inválidas',
        'user_not_found' => 'Usuário não encontrado',
        'account_suspended' => 'Sua conta foi suspensa',
        'account_locked' => 'Sua conta foi temporariamente bloqueada',
        'account_deleted' => 'Esta conta foi excluída',
        'email_not_verified' => 'Você deve verificar seu email',
        'too_many_attempts' => 'Muitas tentativas falhadas. Conta bloqueada por :minutes minutos',
        'invalid_token' => 'Token inválido ou expirado',
        'token_expired' => 'O token expirou',
        'session_expired' => 'Sua sessão expirou. Por favor, faça login novamente',
    ],

    // Errores de autorización
    'authorization' => [
        'no_permission' => 'Você não tem permissão para executar esta ação',
        'access_denied' => 'Acesso negado',
        'insufficient_privileges' => 'Privilégios insuficientes',
        'role_required' => 'A função :role é necessária para acessar',
    ],

    // Errores de base de datos
    'database' => [
        'connection_failed' => 'Não foi possível conectar ao banco de dados',
        'query_failed' => 'Erro ao executar a consulta',
        'record_not_found' => 'Registro não encontrado',
        'duplicate_entry' => 'Este registro já existe',
        'foreign_key_constraint' => 'Não é possível excluir devido a registros relacionados',
        'transaction_failed' => 'A transação falhou',
    ],

    // Errores de archivos
    'file' => [
        'not_found' => 'Arquivo não encontrado',
        'not_readable' => 'Arquivo não pode ser lido',
        'not_writable' => 'Arquivo não pode ser escrito',
        'upload_failed' => 'Erro ao carregar o arquivo',
        'invalid_format' => 'Formato de arquivo inválido',
        'file_too_large' => 'Arquivo é muito grande (máximo: :max)',
        'extension_not_allowed' => 'Extensão de arquivo não permitida',
    ],

    // Errores de validación general
    'validation' => [
        'required' => 'Este campo é obrigatório',
        'invalid' => 'O valor fornecido é inválido',
        'too_short' => 'O valor é muito curto',
        'too_long' => 'O valor é muito longo',
        'out_of_range' => 'O valor está fora do intervalo',
        'not_unique' => 'Este valor já está em uso',
    ],

    // Errores del sistema
    'system' => [
        'maintenance' => 'O sistema está em manutenção. Por favor, tente mais tarde',
        'unavailable' => 'O serviço está temporariamente indisponível',
        'configuration_error' => 'Erro de configuração do sistema',
        'dependency_missing' => 'Falta uma dependência necessária',
        'cache_error' => 'Erro ao acessar o cache',
        'log_error' => 'Erro ao escrever no registro',
    ],

    // Acciones sugeridas
    'actions' => [
        'go_home' => 'Ir para Início',
        'go_back' => 'Voltar',
        'login' => 'Fazer Login',
        'contact_admin' => 'Contatar Administrador',
        'try_again' => 'Tentar Novamente',
        'reload' => 'Recarregar Página',
    ],

    // General
    'something_went_wrong' => 'Algo deu errado',
    'please_try_again' => 'Por favor, tente novamente',
    'if_problem_persists' => 'Se o problema persistir, entre em contato com o administrador',
];
