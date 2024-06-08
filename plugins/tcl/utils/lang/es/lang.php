<?php

/**
 * Converter Plugin Lang File
 * Andres Mejia
 */

return [
    'plugin' => [
        'name' => 'Multimoneda',
        'description' => 'Permite sitios web con conversion de monedas',
    ],
    'currency_picker' => [
        'component_name' => 'Selección de moneda',
        'component_description' => 'Muestra una lista desplegable para seleccionar una moneda para el usuario',
    ],
    'currency' => [
        'title' => 'Administrar Monedas',
        'update_title' => 'Actualizar monedas',
        'create_title' => 'Crear moneda',
        'select_label' => 'Seleccionar moneda',
        'default_suffix' => 'Defecto',
        'unset_default' => '":currency" ya está predeterminada y no puede ser nula por defecto.',
        'disabled_default' => '":currency" esta desactivada y no puede ser moneda por defecto',
        'name' => 'Nombre',
        'code' => 'Código',
        'is_default' => 'Por defecto',
        'is_default_help' => 'La moneda por defecto con el que se representan los valores antes de la conversion.',
        'is_enabled' => 'Habilitada',
        'is_enabled_help' => 'Las monedas desactivadas no estarán disponibles en el front-end',
        'not_available_help' => 'No hay otras monedas establecidas.',
        'hint_locales' => 'Crear nuevas monedas aquí para convertir los valores de front-end. La moneda por defecto representa el valor antes de que haya sido convertido.',
    ],
    'finances' => [
        'title' => 'Administrar Servicios de Monedas',
        'update_title' => 'Actualizar servicio',
        'create_title' => 'Crear servicio',
        'select_label' => 'Seleccionar servicio',
        'default_suffix' => 'Defecto',
        'unset_default' => '":finance" ya está predeterminado y no puede ser nulo por defecto.',
        'disabled_default' => '":finance" esta desactivado y no puede ser servicio por defecto',
        'name' => 'Nombre',
        'code' => 'Código',
        'url' => 'Url',
        'is_default' => 'Por defecto',
        'is_default_help' => 'El servicio por defecto.',
        'is_enabled' => 'Habilitada',
        'is_enabled_help' => 'Los servicios desactivados no estarán disponibles en el front-end',
        'not_available_help' => 'No hay otros servicios¿ establecidos.',
        'hint_locales' => 'Crear nuevos servicios aquí para convertir los valores de front-end.',
    ]
];