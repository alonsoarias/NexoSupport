<?php

/**
 * Traducciones de configuração do sistema - Português
 *
 * @package ISER\Resources\Lang
 */

return [
    // Título
    'title' => 'Configurações do Sistema',
    'description' => 'Configure todos os aspectos do sistema a partir desta interface centralizada',

    // Tabs
    'tabs' => [
        'general' => 'Geral',
        'email' => 'Email',
        'security' => 'Segurança',
        'appearance' => 'Aparência',
        'advanced' => 'Avançado',
    ],

    // Grupos de configuración
    'groups' => [
        'general' => 'Configurações Gerais',
        'email' => 'Configurações de Email',
        'security' => 'Configurações de Segurança',
        'appearance' => 'Configurações de Aparência',
        'advanced' => 'Configurações Avançadas',
    ],

    // Fields
    'fields' => [
        // General
        'site_name' => 'Nome do Site',
        'site_description' => 'Descrição do Site',
        'timezone' => 'Fuso Horário',
        'locale' => 'Idioma',
        'date_format' => 'Formato de Data',

        // Email
        'from_name' => 'Nome do Remetente',
        'from_address' => 'Endereço de Email',
        'reply_to' => 'Responder Para',
        'mail_driver' => 'Driver de Email',

        // Security
        'session_lifetime' => 'Duração da Sessão (minutos)',
        'password_min_length' => 'Comprimento Mínimo da Senha',
        'require_email_verification' => 'Requer Verificação de Email',
        'login_max_attempts' => 'Tentativas Máximas de Login',
        'lockout_duration' => 'Duração do Bloqueio (minutos)',

        // Appearance
        'theme' => 'Tema',
        'items_per_page' => 'Itens por Página',
        'default_language' => 'Idioma Padrão',

        // Advanced
        'cache_driver' => 'Driver de Cache',
        'log_level' => 'Nível de Registro',
        'debug_mode' => 'Modo de Depuração',
        'maintenance_mode' => 'Modo de Manutenção',
    ],

    // Help texts
    'help' => [
        // General
        'site_name' => 'Nome que aparecerá em todo o sistema',
        'site_description' => 'Breve descrição do propósito do sistema',
        'timezone' => 'Fuso horário para datas e horas do sistema',
        'locale' => 'Idioma padrão da interface',
        'date_format' => 'Formato de exibição de datas',

        // Email
        'from_name' => 'Nome que aparecerá como remetente de emails',
        'from_address' => 'Endereço de email para mensagens enviadas',
        'reply_to' => 'Endereço para respostas dos usuários',
        'mail_driver' => 'Método de envio de emails (SMTP recomendado)',

        // Security
        'session_lifetime' => 'Tempo de inatividade antes de desconectar automaticamente (5-1440 minutos)',
        'password_min_length' => 'Número mínimo de caracteres para senhas (6-32)',
        'require_email_verification' => 'Os usuários devem verificar seu email antes de acessar',
        'login_max_attempts' => 'Tentativas falhadas permitidas antes de bloquear conta (3-20)',
        'lockout_duration' => 'Tempo de bloqueio após exceder tentativas (1-1440 minutos)',

        // Appearance
        'theme' => 'Tema visual do sistema',
        'items_per_page' => 'Número de itens em listas e tabelas (10-100)',
        'default_language' => 'Idioma padrão para novos usuários',

        // Advanced
        'cache_driver' => 'Sistema de armazenamento de cache',
        'log_level' => 'Nível de detalhe nos registros do sistema',
        'debug_mode' => 'Mostrar erros detalhados - SOMENTE PARA DESENVOLVIMENTO',
        'maintenance_mode' => 'Desativar o site para todos exceto administradores',
    ],

    // Messages
    'saved_message' => 'Configurações salvas com sucesso',
    'restored_message' => 'Configurações restauradas aos valores padrão',
    'items_updated' => 'itens atualizados',

    // Actions
    'actions' => [
        'save' => 'Salvar Alterações',
        'cancel' => 'Cancelar',
        'reset' => 'Restaurar Valores Padrão',
    ],

    // Warnings
    'warnings' => [
        'advanced' => 'AVISO: As configurações avançadas podem afetar o funcionamento do sistema. Modifique com cautela.',
    ],

    // Badges
    'badges' => [
        'sensitive' => 'Sensível',
        'critical' => 'Crítico',
    ],

    // Confirmations
    'confirmations' => [
        'reset' => 'Tem certeza de que deseja restaurar todas as configurações para seus valores padrão? Esta ação não pode ser desfeita.',
        'sensitive' => 'AVISO: Você ativou configurações sensíveis que podem afetar o funcionamento do sistema:',
    ],
];
