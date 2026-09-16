<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Da formato de lectura a textos capturados en MAYUSCULAS en el catalogo
 * ("BIOMETRIA HEMATICA COMPLETA (BHC)" -> "Biometria hematica completa (BHC)").
 * Si el texto ya trae minusculas se respeta tal cual.
 */
class TextoPublico
{
    private const SIGLAS = [
        'VIH', 'PSA', 'TSH', 'HCG', 'EGO', 'BHC', 'PCR', 'VSG', 'ASO', 'AEL', 'TP', 'TPT', 'RH',
        'VDRL', 'HGH', 'PIE', 'SOH', 'TORCH', 'SARS', 'COVID', 'NT', 'QS', 'ES', 'PFH', 'EDTA',
        'I', 'II', 'III', 'IV',
    ];

    private const ESPECIALES = ['IGG' => 'IgG', 'IGM' => 'IgM'];

    public static function nombre(?string $texto): string
    {
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $texto));

        if ($texto === '' || mb_strtoupper($texto) !== $texto) {
            return $texto;
        }

        // Lo que va entre parentesis suele ser una sigla: se deja igual
        $partes = preg_split('/(\([^)]*\))/u', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);

        $resultado = implode('', array_map(function (string $parte) {
            if (str_starts_with($parte, '(')) {
                return $parte;
            }

            return preg_replace_callback('/[\p{L}\p{N}]+/u', function ($m) {
                $palabra = $m[0];

                return match (true) {
                    isset(self::ESPECIALES[$palabra]) => self::ESPECIALES[$palabra],
                    in_array($palabra, self::SIGLAS, true) => $palabra,
                    (bool) preg_match('/\p{N}/u', $palabra) => $palabra,
                    default => mb_strtolower($palabra),
                };
            }, $parte);
        }, $partes));

        return Str::ucfirst($resultado);
    }

    /** Texto sin acentos y en minusculas para buscar desde el navegador. */
    public static function paraBuscar(string ...$textos): string
    {
        return Str::lower(Str::ascii(implode(' ', array_filter($textos))));
    }

    public static function precio(float $monto): string
    {
        return '$' . number_format($monto, fmod($monto, 1) == 0.0 ? 0 : 2);
    }
}
