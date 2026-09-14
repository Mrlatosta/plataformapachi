<?php

return [

    /*
    |--------------------------------------------------------------------------
    | URL publica para el QR de verificacion
    |--------------------------------------------------------------------------
    |
    | Dominio al que apunta el QR impreso en cada reporte. Es independiente de
    | APP_URL para que los PDFs generados en cualquier entorno (incluso en
    | local) lleven siempre al sitio publico. Si el dominio cambia, los QR ya
    | impresos dejan de funcionar, asi que conviene no moverlo.
    |
    */

    'url_base' => env('VERIFICACION_URL', 'https://biolab.up.railway.app'),

];
