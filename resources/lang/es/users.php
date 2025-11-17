<?php

/**
 * Traducciones de gestión de usuarios - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'management_title' => 'Gestión de Usuarios',
    'list_title' => 'Lista de Usuarios',
    'create_title' => 'Crear Usuario',
    'edit_title' => 'Editar Usuario',
    'view_title' => 'Ver Usuario',
    'profile_title' => 'Perfil de Usuario',

    // Campos
    'id' => 'ID',
    'username' => 'Nombre de Usuario',
    'email' => 'Correo Electrónico',
    'password' => 'Contraseña',
    'password_confirm' => 'Confirmar Contraseña',
    'first_name' => 'Nombre',
    'last_name' => 'Apellido',
    'full_name' => 'Nombre Completo',
    'status' => 'Estado',
    'role' => 'Rol',
    'roles' => 'Roles',
    'created_at' => 'Fecha de Registro',
    'updated_at' => 'Última Actualización',
    'last_login' => 'Último Acceso',
    'last_login_ip' => 'Última IP de Acceso',
    'avatar' => 'Avatar',
    'bio' => 'Biografía',
    'phone' => 'Teléfono',
    'timezone' => 'Zona Horaria',
    'locale' => 'Idioma',

    // Acciones
    'create_button' => 'Crear Usuario',
    'edit_button' => 'Editar',
    'delete_button' => 'Eliminar',
    'restore_button' => 'Restaurar',
    'suspend_button' => 'Suspender',
    'activate_button' => 'Activar',
    'reset_password' => 'Restablecer Contraseña',
    'send_verification' => 'Enviar Verificación',
    'view_profile' => 'Ver Perfil',
    'edit_profile' => 'Editar Perfil',

    // Mensajes
    'created_message' => 'Usuario :name creado exitosamente',
    'updated_message' => 'Usuario :name actualizado exitosamente',
    'deleted_message' => 'Usuario :name eliminado exitosamente',
    'restored_message' => 'Usuario :name restaurado exitosamente',
    'suspended_message' => 'Usuario :name suspendido',
    'activated_message' => 'Usuario :name activado',
    'password_reset_sent' => 'Enlace de restablecimiento enviado a :email',
    'verification_sent' => 'Correo de verificación enviado a :email',

    // Estados
    'status_active' => 'Activo',
    'status_inactive' => 'Inactivo',
    'status_suspended' => 'Suspendido',
    'status_deleted' => 'Eliminado',
    'status_pending' => 'Pendiente',

    // Filtros
    'filter_all' => 'Todos',
    'filter_active' => 'Activos',
    'filter_inactive' => 'Inactivos',
    'filter_suspended' => 'Suspendidos',
    'filter_deleted' => 'Eliminados',
    'filter_by_role' => 'Filtrar por Rol',
    'search_placeholder' => 'Buscar usuarios...',

    // Placeholders
    'username_placeholder' => 'Ingrese nombre de usuario',
    'email_placeholder' => 'usuario@ejemplo.com',
    'password_placeholder' => 'Ingrese contraseña',
    'first_name_placeholder' => 'Ingrese nombre',
    'last_name_placeholder' => 'Ingrese apellido',
    'phone_placeholder' => '+XX XXXXXXXXX',

    // Confirmaciones
    'delete_confirm' => '¿Está seguro de que desea eliminar al usuario :name?',
    'suspend_confirm' => '¿Está seguro de que desea suspender al usuario :name?',
    'restore_confirm' => '¿Está seguro de que desea restaurar al usuario :name?',

    // Validaciones
    'username_required' => 'El nombre de usuario es requerido',
    'username_min_length' => 'El nombre de usuario debe tener al menos :min caracteres',
    'username_format' => 'El nombre de usuario solo puede contener letras, números y guiones bajos',
    'username_unique' => 'El nombre de usuario ya está en uso',
    'email_required' => 'El correo electrónico es requerido',
    'email_valid' => 'Ingrese un correo electrónico válido',
    'email_unique' => 'El correo electrónico ya está registrado',
    'password_required' => 'La contraseña es requerida',
    'password_min' => 'La contraseña debe tener al menos :min caracteres',
    'password_confirm_match' => 'Las contraseñas no coinciden',

    // Estadísticas
    'total_users' => 'Total de Usuarios',
    'active_users' => 'Usuarios Activos',
    'new_today' => 'Nuevos Hoy',
    'online_now' => 'En Línea Ahora',

    // Contadores con pluralización
    'count_label' => '{0} No hay usuarios|{1} 1 usuario|[2,*] :count usuarios',
];
