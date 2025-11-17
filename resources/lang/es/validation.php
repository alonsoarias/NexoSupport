<?php

/**
 * Traducciones de mensajes de validación - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Reglas de validación generales
    'required' => 'El campo :field es requerido',
    'required_if' => 'El campo :field es requerido cuando :other es :value',
    'required_unless' => 'El campo :field es requerido a menos que :other esté en :values',
    'required_with' => 'El campo :field es requerido cuando :values está presente',
    'required_without' => 'El campo :field es requerido cuando :values no está presente',

    // Tipos de datos
    'email' => 'El campo :field debe ser un correo electrónico válido',
    'url' => 'El campo :field debe ser una URL válida',
    'numeric' => 'El campo :field debe ser un número',
    'integer' => 'El campo :field debe ser un número entero',
    'boolean' => 'El campo :field debe ser verdadero o falso',
    'string' => 'El campo :field debe ser una cadena de texto',
    'array' => 'El campo :field debe ser un arreglo',
    'date' => 'El campo :field debe ser una fecha válida',
    'ip' => 'El campo :field debe ser una dirección IP válida',
    'json' => 'El campo :field debe ser un JSON válido',
    'regex' => 'El formato del campo :field es inválido',

    // Longitudes
    'min' => [
        'string' => 'El campo :field debe tener al menos :min caracteres',
        'numeric' => 'El campo :field debe ser al menos :min',
        'array' => 'El campo :field debe tener al menos :min elementos',
    ],
    'max' => [
        'string' => 'El campo :field no puede tener más de :max caracteres',
        'numeric' => 'El campo :field no puede ser mayor a :max',
        'array' => 'El campo :field no puede tener más de :max elementos',
    ],
    'between' => [
        'string' => 'El campo :field debe tener entre :min y :max caracteres',
        'numeric' => 'El campo :field debe estar entre :min y :max',
        'array' => 'El campo :field debe tener entre :min y :max elementos',
    ],
    'size' => [
        'string' => 'El campo :field debe tener exactamente :size caracteres',
        'numeric' => 'El campo :field debe ser :size',
        'array' => 'El campo :field debe contener :size elementos',
    ],

    // Comparaciones
    'same' => 'El campo :field debe coincidir con :other',
    'different' => 'El campo :field debe ser diferente de :other',
    'in' => 'El campo :field seleccionado es inválido',
    'not_in' => 'El campo :field seleccionado es inválido',
    'confirmed' => 'La confirmación del campo :field no coincide',

    // Unicidad y existencia
    'unique' => 'El campo :field ya está en uso',
    'exists' => 'El campo :field seleccionado es inválido',
    'distinct' => 'El campo :field tiene un valor duplicado',

    // Fechas
    'before' => 'El campo :field debe ser una fecha anterior a :date',
    'after' => 'El campo :field debe ser una fecha posterior a :date',
    'date_equals' => 'El campo :field debe ser una fecha igual a :date',
    'date_format' => 'El campo :field no coincide con el formato :format',

    // Archivos
    'file' => 'El campo :field debe ser un archivo',
    'image' => 'El campo :field debe ser una imagen',
    'mimes' => 'El campo :field debe ser un archivo de tipo: :values',
    'mimetypes' => 'El campo :field debe ser un archivo de tipo: :values',
    'uploaded' => 'El campo :field no se pudo cargar',
    'max_file_size' => 'El campo :field no debe ser mayor a :max kilobytes',
    'dimensions' => 'Las dimensiones de la imagen :field son inválidas',

    // Especiales
    'alpha' => 'El campo :field solo puede contener letras',
    'alpha_dash' => 'El campo :field solo puede contener letras, números, guiones y guiones bajos',
    'alpha_num' => 'El campo :field solo puede contener letras y números',
    'slug' => 'El campo :field debe ser un slug válido (solo minúsculas, números y guiones)',
    'starts_with' => 'El campo :field debe comenzar con: :values',
    'ends_with' => 'El campo :field debe terminar con: :values',
    'timezone' => 'El campo :field debe ser una zona horaria válida',
    'matches' => 'El campo :field debe coincidir con :other',
    'in_list' => 'El campo :field debe ser uno de: :values',
    'unknown_rule' => 'Regla de validación desconocida: :rule',

    // Contraseñas
    'password' => [
        'min_length' => 'La contraseña debe tener al menos :min caracteres',
        'uppercase' => 'La contraseña debe contener al menos una letra mayúscula',
        'lowercase' => 'La contraseña debe contener al menos una letra minúscula',
        'number' => 'La contraseña debe contener al menos un número',
        'symbol' => 'La contraseña debe contener al menos un símbolo',
        'common' => 'Esta contraseña es muy común',
        'compromised' => 'Esta contraseña ha sido comprometida en brechas de seguridad',
    ],

    // Profile-specific validations
    'phone_too_long' => 'El teléfono no debe exceder 20 caracteres',
    'phone_invalid' => 'El formato del teléfono no es válido',
    'postalcode_too_long' => 'El código postal no debe exceder 20 caracteres',
    'city_too_long' => 'La ciudad no debe exceder 100 caracteres',
    'country_too_long' => 'El país no debe exceder 100 caracteres',
    'website_invalid' => 'El sitio web debe ser una URL válida',
    'linkedin_invalid' => 'LinkedIn debe ser una URL válida',
    'bio_too_long' => 'La biografía no debe exceder 1000 caracteres',

    // Nombres de campos comunes
    'attributes' => [
        'username' => 'nombre de usuario',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirm' => 'confirmación de contraseña',
        'first_name' => 'nombre',
        'last_name' => 'apellido',
        'phone' => 'teléfono',
        'mobile' => 'móvil',
        'address' => 'dirección',
        'city' => 'ciudad',
        'state' => 'estado/provincia',
        'country' => 'país',
        'postalcode' => 'código postal',
        'postal_code' => 'código postal',
        'bio' => 'biografía',
        'website' => 'sitio web',
        'linkedin' => 'LinkedIn',
        'twitter' => 'Twitter',
        'institution' => 'institución',
        'department' => 'departamento',
        'position' => 'cargo',
        'role' => 'rol',
        'status' => 'estado',
        'description' => 'descripción',
        'title' => 'título',
        'content' => 'contenido',
        'date' => 'fecha',
        'time' => 'hora',
    ],

    // Personalización por campo específico
    'custom' => [
        'username' => [
            'required' => 'Debe ingresar un nombre de usuario',
            'unique' => 'Este nombre de usuario ya está registrado',
            'min' => 'El nombre de usuario debe tener al menos :min caracteres',
            'alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guiones bajos',
        ],
        'email' => [
            'required' => 'Debe ingresar un correo electrónico',
            'email' => 'Debe ingresar un correo electrónico válido',
            'unique' => 'Este correo electrónico ya está registrado',
        ],
        'password' => [
            'required' => 'Debe ingresar una contraseña',
            'min' => 'La contraseña debe tener al menos :min caracteres',
            'confirmed' => 'Las contraseñas no coinciden',
        ],
    ],
];
