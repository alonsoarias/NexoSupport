<?php

/**
 * Traducciones de cola de correo - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Cola de Correos',
    'view_title' => 'Detalles del Correo',
    'manage' => 'Gestionar Cola de Correos',
    'queue' => 'Cola de Correos',

    // Campos
    'id' => 'ID',
    'to_email' => 'Destinatario',
    'subject' => 'Asunto',
    'body' => 'Cuerpo',
    'status' => 'Estado',
    'attempts' => 'Intentos',
    'last_attempt_at' => 'Último Intento',
    'error_message' => 'Mensaje de Error',
    'created_at' => 'Creado',
    'updated_at' => 'Actualizado',

    // Estados
    'status_pending' => 'Pendiente',
    'status_sent' => 'Enviado',
    'status_failed' => 'Fallido',

    // Acciones
    'view' => 'Ver',
    'retry' => 'Reintentar',
    'delete' => 'Eliminar',
    'clear' => 'Limpiar',
    'back' => 'Atrás',
    'filter' => 'Filtrar',
    'clear_filters' => 'Limpiar Filtros',

    // Mensajes
    'no_emails' => 'No hay correos en la cola',
    'not_found' => 'Correo no encontrado',
    'retry_success' => 'Correo marcado para reintentar',
    'delete_success' => 'Correo eliminado correctamente',
    'clear_success' => ':count correos antiguos eliminados correctamente',

    // Filtros
    'filters' => [
        'status' => 'Estado',
        'email' => 'Correo',
        'date' => 'Fecha',
    ],

    // Estadísticas
    'stats' => [
        'pending' => 'Pendientes',
        'sent' => 'Enviados',
        'failed' => 'Fallidos',
        'total' => 'Total',
    ],

    // Descripciones
    'description' => 'Gestión de la cola de correos electrónicos para envío asincrónico',
    'pending_description' => 'Correos esperando ser enviados',
    'sent_description' => 'Correos enviados exitosamente',
    'failed_description' => 'Correos que fallaron en el envío',

    // Tabla
    'table' => [
        'showing' => 'Mostrando :from a :to de :total correos',
        'per_page' => 'Por página',
        'no_results' => 'Sin resultados',
    ],

    // Acciones
    'actions' => 'Acciones',
    'send_now' => 'Enviar Ahora',
    'resend' => 'Reenviar',
    'mark_as_sent' => 'Marcar como Enviado',
    'mark_as_failed' => 'Marcar como Fallido',

    // Períodos
    'periods' => [
        'today' => 'Hoy',
        'yesterday' => 'Ayer',
        'last_7_days' => 'Últimos 7 Días',
        'last_30_days' => 'Últimos 30 Días',
        'older_than_30_days' => 'Más de 30 Días',
    ],

    // Validaciones
    'validation' => [
        'email_required' => 'El correo es requerido',
        'subject_required' => 'El asunto es requerido',
        'body_required' => 'El cuerpo es requerido',
    ],
];
