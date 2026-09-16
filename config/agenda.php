<?php

// Configuración de la agenda de citas del laboratorio.
return [
    'zona_horaria' => 'America/Cancun',

    // Horario de atención por día de la semana (0 = domingo ... 6 = sábado).
    // Un día sin entrada se considera cerrado. Solo genera avisos, no bloquea.
    'horario' => [
        1 => ['08:00', '18:00'],
        2 => ['08:00', '18:00'],
        3 => ['08:00', '18:00'],
        4 => ['08:00', '18:00'],
        5 => ['08:00', '18:00'],
        6 => ['08:00', '14:00'],
    ],

    // Horas que se dibujan en la vista de día
    'vista_dia' => ['desde' => 7, 'hasta' => 19],

    'duraciones' => [15, 30, 45, 60, 90, 120],
    'duracion_predeterminada' => 15,

    'estados' => [
        'programada' => 'Programada',
        'confirmada' => 'Confirmada',
        'atendida' => 'Atendida',
        'no_asistio' => 'No asistió',
        'cancelada' => 'Cancelada',
    ],

    'origenes' => [
        'whatsapp' => 'WhatsApp',
        'telefono' => 'Teléfono',
        'presencial' => 'En persona',
        'landing' => 'Página web',
        'otro' => 'Otro',
    ],

    // Rango máximo de días que se puede pedir de una sola vez
    'rango_maximo_dias' => 62,
];
