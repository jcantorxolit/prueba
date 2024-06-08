<?php

namespace AdeN\Api\Helpers;

/**
 * 
 */
class ValidatorHelper
{
    public static function customMessages()
    {
        return [
            'required' => 'El campo [:attribute] es requerido.',
            'max' => 'El temaño del campo [:attribute] es inválido.',
            'numeric' => 'El tipo de dato del campo [:attribute] debe ser numérico.',
            'boolean' => 'El tipo de dato del campo [:attribute] debe ser booleano.',
            'date_format' => 'El fomato del campo [:attribute] no coincide con el formato :format.',
            'date' => 'El campo [:attribute] no es una fecha válida.',
            'after' => 'La fecha del campo [:attribute] debe ser una fecha posterior a :date.',
            'email' => 'El formato del campo [:attribute] es inválido.',
            'in' => 'El campo [:attribute] debe ser uno de los siguientes valores: :values',
            'digits_between' => 'El campo [:attribute] debe tener entre :min y :max dígitos',
            'present' => 'El campo [:attribute] debe estar presente en la petición.',
        ];
    }
}
