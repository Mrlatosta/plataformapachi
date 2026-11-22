<?php

/*
 * T12: comisiones por medico que refiere.
 *
 * PENDIENTE DE CONFIRMAR CON EL LABORATORIO (preguntas del ticket):
 *  - Base de la comision. Por ahora: lo cobrado despues del descuento y SIN IVA.
 *    Cambiar con COMISION_BASE=con_iva si se calcula sobre el total con IVA.
 *  - El % es uno solo por medico (se configura en Gestion de medicos).
 *  - El corte (semanal, quincenal o mensual) no se fija: el reporte se filtra por
 *    rango de fechas y "Marcar como pagadas" marca lo pendiente de ese rango.
 */
return [
    // 'sin_iva': subtotal - descuento.  'con_iva': total con IVA.
    'base' => env('COMISION_BASE', 'sin_iva'),
];
