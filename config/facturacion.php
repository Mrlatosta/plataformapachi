<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Porcentaje de IVA
    |--------------------------------------------------------------------------
    |
    | Porcentaje aplicado cuando una cotizacion u orden de trabajo se marca
    | con "aplica IVA". Se usa tanto en los calculos del backend como en las
    | plantillas PDF. El equivalente para el front esta en
    | resources/js/utils/iva.js
    |
    */

    'iva' => (float) env('IVA_PORCENTAJE', 16),

];
