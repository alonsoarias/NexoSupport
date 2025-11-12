<?php

/**
 * Traducciones de configuración del sistema - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Título
    'title' => 'Configurações do Sistema',

    // Grupos de configuración
    'groups' => [
        'general' => 'Geral',
        'email' => 'Email',
        'security' => 'Segurança',
        'cache' => 'Cache',
        'logs' => 'Registros',
        'regional' => 'Regional',
        'appearance' => 'Aparência',
        'advanced' => 'Avançado',
    ],

    // Configuraciones generales
    'general' => [
        'app_name' => 'Nome da Aplicação',
        'app_url' => 'URL da Aplicação',
        'app_env' => 'Ambiente',
        'app_debug' => 'Modo de Depuração',
        'maintenance_mode' => 'Modo de Manutenção',
        'timezone' => 'Fuso Horário',
        'locale' => 'Idioma',
    ],

    // Configuraciones de correo
    'email' => [
        'driver' => 'Driver de Email',
        'host' => 'Servidor SMTP',
        'port' => 'Porta',
        'username' => 'Usuário',
        'password' => 'Senha',
        'encryption' => 'Encriptação',
        'from_address' => 'Endereço de Envio',
        'from_name' => 'Nome de Envio',
        'test_connection' => 'Testar Conexão',
    ],

    // Configuraciones de seguridad
    'security' => [
        'password_min_length' => 'Comprimento Mínimo da Senha',
        'password_require_uppercase' => 'Requer Maiúsculas',
        'password_require_lowercase' => 'Requer Minúsculas',
        'password_require_numbers' => 'Requer Números',
        'password_require_symbols' => 'Requer Símbolos',
        'password_expiry_days' => 'Dias de Expiração da Senha',
        'max_login_attempts' => 'Tentativas Máximas de Login',
        'lockout_duration' => 'Duração do Bloqueio (minutos)',
        'session_lifetime' => 'Duração da Sessão (minutos)',
        'jwt_secret' => 'Chave Secreta JWT',
        'jwt_ttl' => 'TTL do Token JWT (minutos)',
        'mfa_enabled' => 'Ativar Autenticação de Dois Fatores',
    ],

    // Configuraciones de caché
    'cache' => [
        'driver' => 'Driver de Cache',
        'ttl' => 'Tempo de Vida (segundos)',
        'prefix' => 'Prefixo de Chaves',
        'clear_cache' => 'Limpar Cache',
    ],

    // Configuraciones de logs
    'logs' => [
        'channel' => 'Canal de Registros',
        'level' => 'Nível de Registro',
        'max_files' => 'Arquivos Máximos',
        'rotation' => 'Rotação de Arquivos',
    ],

    // Configuraciones regionales
    'regional' => [
        'default_timezone' => 'Fuso Horário Padrão',
        'default_locale' => 'Idioma Padrão',
        'available_locales' => 'Idiomas Disponíveis',
        'date_format' => 'Formato de Data',
        'time_format' => 'Formato de Hora',
        'currency' => 'Moeda',
    ],

    // Mensajes
    'saved_message' => 'Configurações salvas com sucesso',
    'restored_message' => 'Configurações restauradas aos valores padrão',
    'test_email_sent' => 'Email de teste enviado para :email',
    'cache_cleared' => 'Cache limpo com sucesso',

    // Acciones
    'save' => 'Salvar Configurações',
    'restore_defaults' => 'Restaurar Valores Padrão',
    'cancel' => 'Cancelar',

    // Ayuda
    'help' => [
        'app_name' => 'Nome que aparecerá em todo o sistema',
        'app_url' => 'URL base da aplicação (sem barra final)',
        'app_debug' => 'Mostrar erros detalhados (apenas para desenvolvimento)',
        'password_min_length' => 'Número mínimo de caracteres necessários para senhas',
        'max_login_attempts' => 'Número de tentativas falhadas antes de bloquear a conta',
        'session_lifetime' => 'Tempo de inatividade antes de fazer logout',
    ],
];
