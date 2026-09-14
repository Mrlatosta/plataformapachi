<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR impreso en los PDFs de cada reporte. Lleva a la pagina publica de
 * verificacion, identificada por un token aleatorio (nunca por id o folio,
 * que son consecutivos y permitirian ver reportes de otros pacientes).
 */
class QrVerificacion
{
    /**
     * URL absoluta de verificacion. Se arma con config/verificacion.php (y no con
     * el host de la peticion) para que el QR impreso siempre apunte al dominio publico.
     */
    public static function url(string $token): string
    {
        return rtrim(config('verificacion.url_base'), '/') . route('reportes.verificar', $token, false);
    }

    /**
     * Imagen del QR como data URI, lista para usarse en un <img> de DomPDF.
     * Devuelve null si el reporte aun no tiene token (vista previa del borrador).
     */
    public static function dataUri(?string $token): ?string
    {
        if (! $token) {
            return null;
        }

        $options = new QROptions([
            // PNG con GD; si el servidor no tiene GD se usa SVG, que DomPDF tambien soporta
            'outputInterface' => extension_loaded('gd') ? QRGdImagePNG::class : QRMarkupSVG::class,
            'outputBase64' => true,
            'eccLevel' => EccLevel::M,
            'scale' => 6,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render(self::url($token));
    }
}
