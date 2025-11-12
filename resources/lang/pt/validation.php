<?php

/**
 * Traducciones de mensajes de validación - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Reglas de validación generales
    'required' => 'O campo :field é obrigatório',
    'required_if' => 'O campo :field é obrigatório quando :other é :value',
    'required_unless' => 'O campo :field é obrigatório a menos que :other esteja em :values',
    'required_with' => 'O campo :field é obrigatório quando :values está presente',
    'required_without' => 'O campo :field é obrigatório quando :values não está presente',

    // Tipos de datos
    'email' => 'O campo :field deve ser um endereço de email válido',
    'url' => 'O campo :field deve ser uma URL válida',
    'numeric' => 'O campo :field deve ser um número',
    'integer' => 'O campo :field deve ser um número inteiro',
    'boolean' => 'O campo :field deve ser verdadeiro ou falso',
    'string' => 'O campo :field deve ser uma string',
    'array' => 'O campo :field deve ser um array',
    'date' => 'O campo :field deve ser uma data válida',
    'ip' => 'O campo :field deve ser um endereço IP válido',
    'json' => 'O campo :field deve ser um JSON válido',
    'regex' => 'O formato do campo :field é inválido',

    // Longitudes
    'min' => [
        'string' => 'O campo :field deve ter pelo menos :min caracteres',
        'numeric' => 'O campo :field deve ser pelo menos :min',
        'array' => 'O campo :field deve ter pelo menos :min itens',
    ],
    'max' => [
        'string' => 'O campo :field não pode ter mais de :max caracteres',
        'numeric' => 'O campo :field não pode ser maior que :max',
        'array' => 'O campo :field não pode ter mais de :max itens',
    ],
    'between' => [
        'string' => 'O campo :field deve ter entre :min e :max caracteres',
        'numeric' => 'O campo :field deve estar entre :min e :max',
        'array' => 'O campo :field deve ter entre :min e :max itens',
    ],
    'size' => [
        'string' => 'O campo :field deve ter exatamente :size caracteres',
        'numeric' => 'O campo :field deve ser :size',
        'array' => 'O campo :field deve conter :size itens',
    ],

    // Comparaciones
    'same' => 'O campo :field deve corresponder a :other',
    'different' => 'O campo :field deve ser diferente de :other',
    'in' => 'O campo selecionado :field é inválido',
    'not_in' => 'O campo selecionado :field é inválido',
    'confirmed' => 'A confirmação do campo :field não corresponde',

    // Unicidad y existencia
    'unique' => 'O campo :field já está em uso',
    'exists' => 'O campo selecionado :field é inválido',
    'distinct' => 'O campo :field tem um valor duplicado',

    // Fechas
    'before' => 'O campo :field deve ser uma data anterior a :date',
    'after' => 'O campo :field deve ser uma data posterior a :date',
    'date_equals' => 'O campo :field deve ser uma data igual a :date',
    'date_format' => 'O campo :field não corresponde ao formato :format',

    // Archivos
    'file' => 'O campo :field deve ser um arquivo',
    'image' => 'O campo :field deve ser uma imagem',
    'mimes' => 'O campo :field deve ser um arquivo do tipo: :values',
    'mimetypes' => 'O campo :field deve ser um arquivo do tipo: :values',
    'uploaded' => 'O campo :field não foi carregado',
    'max_file_size' => 'O campo :field não deve ser maior que :max kilobytes',
    'dimensions' => 'As dimensões da imagem :field são inválidas',

    // Especiales
    'alpha' => 'O campo :field pode conter apenas letras',
    'alpha_dash' => 'O campo :field pode conter apenas letras, números, hífens e underscores',
    'alpha_num' => 'O campo :field pode conter apenas letras e números',
    'starts_with' => 'O campo :field deve começar com: :values',
    'ends_with' => 'O campo :field deve terminar com: :values',
    'timezone' => 'O campo :field deve ser um fuso horário válido',

    // Contraseñas
    'password' => [
        'min_length' => 'A senha deve ter pelo menos :min caracteres',
        'uppercase' => 'A senha deve conter pelo menos uma letra maiúscula',
        'lowercase' => 'A senha deve conter pelo menos uma letra minúscula',
        'number' => 'A senha deve conter pelo menos um número',
        'symbol' => 'A senha deve conter pelo menos um símbolo',
        'common' => 'Esta senha é muito comum',
        'compromised' => 'Esta senha foi comprometida em brechas de segurança',
    ],

    // Nombres de campos comunes
    'attributes' => [
        'username' => 'nome de usuário',
        'email' => 'email',
        'password' => 'senha',
        'password_confirm' => 'confirmação de senha',
        'first_name' => 'nome',
        'last_name' => 'sobrenome',
        'phone' => 'telefone',
        'address' => 'endereço',
        'city' => 'cidade',
        'country' => 'país',
        'role' => 'função',
        'status' => 'status',
        'description' => 'descrição',
        'title' => 'título',
        'content' => 'conteúdo',
        'date' => 'data',
        'time' => 'hora',
    ],

    // Personalización por campo específico
    'custom' => [
        'username' => [
            'required' => 'Você deve inserir um nome de usuário',
            'unique' => 'Este nome de usuário já está registrado',
            'min' => 'O nome de usuário deve ter pelo menos :min caracteres',
            'alpha_dash' => 'O nome de usuário pode conter apenas letras, números, hífens e underscores',
        ],
        'email' => [
            'required' => 'Você deve inserir um email',
            'email' => 'Você deve inserir um email válido',
            'unique' => 'Este email já está registrado',
        ],
        'password' => [
            'required' => 'Você deve inserir uma senha',
            'min' => 'A senha deve ter pelo menos :min caracteres',
            'confirmed' => 'As senhas não correspondem',
        ],
    ],
];
