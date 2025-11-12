<?php

/**
 * Traduções do Módulo de Backups - Português
 *
 * @package ISER\Resources\Lang
 */

return [
    // Page titles
    'page_title' => 'Backups de Banco de Dados',
    'title' => 'Gerenciamento de Backups de Banco de Dados',

    // Actions
    'create_backup' => 'Criar Backup',
    'backup_list' => 'Lista de Backups',
    'download' => 'Baixar',
    'delete' => 'Deletar',

    // Table columns
    'filename' => 'Nome do Arquivo',
    'size' => 'Tamanho',
    'date' => 'Data de Criação',
    'actions' => 'Ações',

    // Messages
    'no_backups_yet' => 'Nenhum backup ainda. Crie um novo para começar.',
    'backup_created_success' => 'Backup criado com sucesso',
    'backup_creation_failed' => 'Falha ao criar backup',
    'backup_deleted_success' => 'Backup deletado com sucesso',
    'backup_deletion_failed' => 'Falha ao deletar backup',
    'error_listing_backups' => 'Erro ao listar backups',
    'error_creating_backup' => 'Erro ao criar backup',

    // UI Labels
    'creating_backup' => 'Criando backup... Por favor, aguarde...',
    'backup_dir_warning' => 'Aviso: O diretório de backup não tem permissões de escrita',
    'total_backup_size' => 'Tamanho total de backups',

    // Warnings and Instructions
    'warning_restore' => 'Aviso de Segurança',
    'restore_instructions' => 'Restaurar backups é uma operação potencialmente perigosa que requer acesso direto à linha de comando do servidor. Uma interface web não é fornecida para esta operação por motivos de segurança. Se você precisar restaurar um backup, contate o administrador do seu servidor.',

    // Backup info
    'backup_info' => 'Informações do Backup',
    'backup_directory' => 'Diretório de Backup',
    'backup_location' => 'Localização: :path',
    'backup_permissions' => 'Permissões do Diretório',
    'writable' => 'Gravável',
    'not_writable' => 'Não gravável',

    // Success messages
    'success' => [
        'backup_created' => 'Backup criado com sucesso',
        'backup_downloaded' => 'Download do backup iniciado',
        'backup_deleted' => 'Backup deletado com sucesso',
    ],

    // Error messages
    'errors' => [
        'backup_creation_failed' => 'Falha ao criar backup',
        'backup_download_failed' => 'Falha ao baixar backup',
        'backup_deletion_failed' => 'Falha ao deletar backup',
        'invalid_backup_file' => 'Arquivo de backup inválido',
        'backup_directory_not_writable' => 'Diretório de backup não tem permissões de escrita',
        'insufficient_disk_space' => 'Espaço em disco insuficiente',
    ],
];
