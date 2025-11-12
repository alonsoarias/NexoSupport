<?php

/**
 * Traducciones de gestión de plugins - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'management_title' => 'Gestión de Plugins',
    'list_title' => 'Lista de Plugins',
    'details_title' => 'Detalles del Plugin',

    // Campos
    'name' => 'Nombre',
    'slug' => 'Slug',
    'version' => 'Versión',
    'author' => 'Autor',
    'description' => 'Descripción',
    'type' => 'Tipo',
    'status' => 'Estado',
    'dependencies' => 'Dependencias',
    'enabled' => 'Habilitado',
    'disabled' => 'Deshabilitado',
    'is_core' => 'Plugin del Sistema',

    // Acciones
    'install_button' => 'Instalar Plugin',
    'uninstall_button' => 'Desinstalar',
    'enable_button' => 'Habilitar',
    'disable_button' => 'Deshabilitar',
    'upload_button' => 'Subir Nuevo Plugin',
    'discover_button' => 'Descubrir Plugins',
    'view_details_button' => 'Ver Detalles',
    'back_to_plugins' => 'Volver a Plugins',

    // Mensajes
    'installed_message' => 'Plugin instalado correctamente',
    'uninstalled_message' => 'Plugin desinstalado correctamente',
    'enabled_message' => 'Plugin habilitado correctamente',
    'disabled_message' => 'Plugin deshabilitado correctamente',
    'plugin_not_found' => 'Plugin no encontrado',

    // Confirmaciones
    'confirm_uninstall' => '¿Estás seguro de que deseas desinstalar este plugin? Esta acción no se puede deshacer.',
    'confirm_disable' => '¿Estás seguro de que deseas deshabilitar este plugin?',
    'confirm_enable' => '¿Habilitar este plugin?',

    // Filtros
    'filter_all_status' => 'Todos los Estados',
    'filter_enabled' => 'Habilitados',
    'filter_disabled' => 'Deshabilitados',
    'filter_by_type' => 'Filtrar por Tipo',
    'search_plugins' => 'Buscar plugins...',

    // Tipos de Plugin
    'type_auth' => 'Autenticación',
    'type_theme' => 'Tema',
    'type_tool' => 'Herramienta',
    'type_module' => 'Módulo',
    'type_integration' => 'Integración',
    'type_report' => 'Reporte',

    // Estadísticas
    'total_plugins' => 'Plugins Totales',
    'enabled_count' => 'Habilitados',
    'disabled_count' => 'Deshabilitados',
    'by_type' => 'Por Tipo',

    // Sin Datos
    'no_plugins_found' => 'No se encontraron plugins',
    'no_dependencies' => 'Sin dependencias',
    'no_dependents' => 'Sin plugins dependientes',

    // Configuración
    'plugin_settings' => 'Configuración del Plugin',
    'no_settings' => 'Este plugin no tiene configuraciones personalizables',

    // Upload de Plugins
    'upload_title' => 'Subir Nuevo Plugin',
    'upload_description' => 'Instala un nuevo plugin subiendo un archivo ZIP válido',
    'upload_form_title' => 'Formulario de Subida',
    'drag_drop_title' => 'Arrastra y suelta el archivo ZIP aquí',
    'or' => 'o',
    'browse_button' => 'Explorar Archivos',

    // Instrucciones
    'instructions_title' => 'Instrucciones',
    'instruction_1' => 'El archivo debe estar en formato ZIP',
    'instruction_2' => 'El ZIP debe contener un archivo plugin.json válido en la raíz',
    'instruction_3' => 'Verifica que el plugin sea compatible con esta versión del sistema',
    'instruction_4' => 'El tamaño máximo del archivo es 100MB',

    // Requisitos
    'requirements_title' => 'Requisitos del Plugin',
    'requirement_1' => 'Archivo plugin.json con estructura válida',
    'requirement_2' => 'Slug único (no puede existir otro plugin con el mismo slug)',
    'requirement_3' => 'Tipo válido: tool, auth, theme, report, module, integration',
    'requirement_4' => 'Versión en formato semántico (ej: 1.0.0)',

    // Manifest
    'manifest_title' => 'Estructura del Manifest',
    'manifest_description' => 'El archivo plugin.json debe contener al menos estos campos:',

    // Tipos
    'types_title' => 'Tipos de Plugin',

    // Mensajes de Upload
    'uploading' => 'Subiendo archivo',
    'installation_complete' => 'Instalación completada',
    'error_invalid_file' => 'Error: El archivo debe ser un ZIP válido',
    'error_file_too_large' => 'Error: El archivo excede el tamaño máximo de 100MB',
    'error_installation' => 'Error durante la instalación',
    'error_upload' => 'Error al subir el archivo. Código de estado HTTP inválido.',
    'error_network' => 'Error de red. Verifica tu conexión e intenta de nuevo.',

    // Update de Plugins
    'update_available' => 'Actualización disponible',
    'update_button' => 'Actualizar',
    'current_version' => 'Versión actual',
    'new_version' => 'Nueva versión',
    'update_message' => 'Plugin actualizado correctamente',
    'confirm_update' => '¿Actualizar este plugin a la versión {version}?',
];
