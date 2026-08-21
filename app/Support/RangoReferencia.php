<?php

namespace App\Support;

/**
 * Determina la direccion de un resultado fuera de rango (alto / bajo)
 * comparando el resultado capturado contra el valor de referencia del examen.
 */
class RangoReferencia
{
    public const ALTO = 'alto';
    public const BAJO = 'bajo';

    /**
     * Devuelve 'alto', 'bajo' o null cuando no se puede determinar
     * (resultados o referencias no numericas, p. ej. "Negativo").
     */
    public static function direccion($resultado, $valorReferencia): ?string
    {
        $valor = self::aNumero($resultado);
        if ($valor === null) {
            return null;
        }

        $referencia = self::normalizar($valorReferencia);
        if ($referencia === '') {
            return null;
        }

        // Rango: "70 - 110", "0.5 a 1.2", "70 hasta 110"
        if (preg_match('/(-?\d+(?:\.\d+)?)\s*(?:-|a|hasta)\s*(-?\d+(?:\.\d+)?)/', $referencia, $m)) {
            $min = (float) $m[1];
            $max = (float) $m[2];

            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }

            if ($valor < $min) {
                return self::BAJO;
            }

            if ($valor > $max) {
                return self::ALTO;
            }

            return null;
        }

        // Limite superior: "< 200", "<= 200", "menor a 200", "hasta 200", "maximo 200"
        if (preg_match('/^(?:<=?|menor(?:\s+(?:a|de|que))?|inferior(?:\s+a)?|hasta|maximo(?:\s+de)?)\s*(-?\d+(?:\.\d+)?)/', $referencia, $m)) {
            return $valor > (float) $m[1] ? self::ALTO : null;
        }

        // Limite inferior: "> 40", ">= 40", "mayor a 40", "minimo 40"
        if (preg_match('/^(?:>=?|mayor(?:\s+(?:a|de|que))?|superior(?:\s+a)?|minimo(?:\s+de)?)\s*(-?\d+(?:\.\d+)?)/', $referencia, $m)) {
            return $valor < (float) $m[1] ? self::BAJO : null;
        }

        return null;
    }

    /**
     * Flecha que corresponde a la direccion recibida ('' si no aplica).
     */
    public static function flecha(?string $direccion): string
    {
        return match ($direccion) {
            self::ALTO => '↑',
            self::BAJO => '↓',
            default => '',
        };
    }

    private static function aNumero($valor): ?float
    {
        $texto = self::normalizar($valor);

        if ($texto === '' || !preg_match('/-?\d+(?:\.\d+)?/', $texto, $m)) {
            return null;
        }

        return (float) $m[0];
    }

    private static function normalizar($valor): string
    {
        if ($valor === null) {
            return '';
        }

        $texto = mb_strtolower(trim((string) $valor));

        // Guiones y acentos que llegan desde la captura
        $texto = str_replace(['–', '—', '−'], '-', $texto);
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        // 5,000 -> 5000 (miles) y 1,5 -> 1.5 (decimal)
        $texto = preg_replace('/(\d),(\d{3})(?!\d)/', '$1$2', $texto);
        $texto = preg_replace('/(\d),(\d+)/', '$1.$2', $texto);

        return preg_replace('/\s+/', ' ', $texto);
    }
}
