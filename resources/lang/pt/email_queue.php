<?php

/**
 * Tradução de Fila de E-mail - Português
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Fila de E-mail',
    'view_title' => 'Detalhes do E-mail',
    'manage' => 'Gerenciar Fila de E-mail',
    'queue' => 'Fila de E-mail',

    // Campos
    'id' => 'ID',
    'to_email' => 'Destinatário',
    'subject' => 'Assunto',
    'body' => 'Corpo',
    'status' => 'Status',
    'attempts' => 'Tentativas',
    'last_attempt_at' => 'Última Tentativa',
    'error_message' => 'Mensagem de Erro',
    'created_at' => 'Criado',
    'updated_at' => 'Atualizado',

    // Estados
    'status_pending' => 'Pendente',
    'status_sent' => 'Enviado',
    'status_failed' => 'Falhou',

    // Ações
    'view' => 'Visualizar',
    'retry' => 'Repetir',
    'delete' => 'Excluir',
    'clear' => 'Limpar',
    'back' => 'Voltar',
    'filter' => 'Filtrar',
    'clear_filters' => 'Limpar Filtros',

    // Mensagens
    'no_emails' => 'Nenhum e-mail na fila',
    'not_found' => 'E-mail não encontrado',
    'retry_success' => 'E-mail marcado para repetir',
    'delete_success' => 'E-mail excluído com sucesso',
    'clear_success' => ':count e-mails antigos excluídos com sucesso',

    // Filtros
    'filters' => [
        'status' => 'Status',
        'email' => 'E-mail',
        'date' => 'Data',
    ],

    // Estatísticas
    'stats' => [
        'pending' => 'Pendentes',
        'sent' => 'Enviados',
        'failed' => 'Falhados',
        'total' => 'Total',
    ],

    // Descrições
    'description' => 'Gerenciamento de fila de e-mail para entrega assincronizada',
    'pending_description' => 'E-mails aguardando serem enviados',
    'sent_description' => 'E-mails enviados com sucesso',
    'failed_description' => 'E-mails que falharam no envio',

    // Tabela
    'table' => [
        'showing' => 'Mostrando :from a :to de :total e-mails',
        'per_page' => 'Por página',
        'no_results' => 'Sem resultados',
    ],

    // Ações
    'actions' => 'Ações',
    'send_now' => 'Enviar Agora',
    'resend' => 'Reenviar',
    'mark_as_sent' => 'Marcar como Enviado',
    'mark_as_failed' => 'Marcar como Falhado',

    // Períodos
    'periods' => [
        'today' => 'Hoje',
        'yesterday' => 'Ontem',
        'last_7_days' => 'Últimos 7 Dias',
        'last_30_days' => 'Últimos 30 Dias',
        'older_than_30_days' => 'Mais de 30 Dias',
    ],

    // Validações
    'validation' => [
        'email_required' => 'E-mail é obrigatório',
        'subject_required' => 'Assunto é obrigatório',
        'body_required' => 'Corpo é obrigatório',
    ],
];
