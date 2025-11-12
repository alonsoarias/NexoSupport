<?php

/**
 * Email Queue Translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'title' => 'Email Queue',
    'view_title' => 'Email Details',
    'manage' => 'Manage Email Queue',
    'queue' => 'Email Queue',

    // Fields
    'id' => 'ID',
    'to_email' => 'Recipient',
    'subject' => 'Subject',
    'body' => 'Body',
    'status' => 'Status',
    'attempts' => 'Attempts',
    'last_attempt_at' => 'Last Attempt',
    'error_message' => 'Error Message',
    'created_at' => 'Created',
    'updated_at' => 'Updated',

    // Statuses
    'status_pending' => 'Pending',
    'status_sent' => 'Sent',
    'status_failed' => 'Failed',

    // Actions
    'view' => 'View',
    'retry' => 'Retry',
    'delete' => 'Delete',
    'clear' => 'Clear',
    'back' => 'Back',
    'filter' => 'Filter',
    'clear_filters' => 'Clear Filters',

    // Messages
    'no_emails' => 'No emails in queue',
    'not_found' => 'Email not found',
    'retry_success' => 'Email marked for retry',
    'delete_success' => 'Email deleted successfully',
    'clear_success' => ':count old emails deleted successfully',

    // Filters
    'filters' => [
        'status' => 'Status',
        'email' => 'Email',
        'date' => 'Date',
    ],

    // Statistics
    'stats' => [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'total' => 'Total',
    ],

    // Descriptions
    'description' => 'Email queue management for asynchronous email delivery',
    'pending_description' => 'Emails waiting to be sent',
    'sent_description' => 'Emails sent successfully',
    'failed_description' => 'Emails that failed to send',

    // Table
    'table' => [
        'showing' => 'Showing :from to :to of :total emails',
        'per_page' => 'Per page',
        'no_results' => 'No results',
    ],

    // Actions
    'actions' => 'Actions',
    'send_now' => 'Send Now',
    'resend' => 'Resend',
    'mark_as_sent' => 'Mark as Sent',
    'mark_as_failed' => 'Mark as Failed',

    // Periods
    'periods' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'older_than_30_days' => 'Older than 30 Days',
    ],

    // Validations
    'validation' => [
        'email_required' => 'Email is required',
        'subject_required' => 'Subject is required',
        'body_required' => 'Body is required',
    ],
];
