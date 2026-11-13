<?php

namespace App\Support;

/**
 * Determina si un resultado esta fuera del intervalo de referencia y hacia donde
 * (alto / bajo). Espejo en JS: resources/js/utils/rangoReferencia.js; cualquier
 * cambio aqui debe hacerse tambien alla (las pruebas de ambos usan los mismos casos).
 *
 * El intervalo puede venir:
 *  - de la tabla de niveles del examen (T5): se usa el nivel marcado como normal;
 *  - del texto del catalogo, eligiendo el tramo que le toca al paciente:
 *    "Mujeres > 55 Hombres > 45", "Niños 150-400 Adultos 250-450",
 *    "< 75 años 0 - 300 ≥ 75 años 0 - 450", "Normal < 130 Límite 130 - 160...".
 */
class RangoReferencia
{
    public const ALTO = 'alto';
    public const BAJO = 'bajo';

    /** Edad a partir de la cual aplica el tramo "Adultos". */
    private const EDAD_ADULTO = 18;

    private const NUMERO = '(-?\d+(?:\.\d+)?)';

    /** Limite superior al inicio del texto: "< 200", "menor a 200", "menos de 17", "hasta 200". */
    private const PATRON_MAXIMO = '/^(?:<=?|≤|menor(?:\s+(?:a|de|que))?|menos\s+de|inferior(?:\s+a)?|hasta|maximo(?:\s+de)?)\s*' . self::NUMERO . '/u';

    /** Limite inferior al inicio del texto: "> 40", "mayor a 40", "minimo 40". */
    private const PATRON_MINIMO = '/^(?:>=?|≥|mayor(?:\s+(?:a|de|que))?|mas\s+de|superior(?:\s+a)?|minimo(?:\s+de)?)\s*' . self::NUMERO . '/u';

    /** Rango en cualquier parte: "70 - 110", "0.5 a 1.2", "70 hasta 110". */
    private const PATRON_RANGO = '/' . self::NUMERO . '\s*(?:-|a|hasta)\s*' . self::NUMERO . '/u';

    private const PATRON_SEXO = '/\b(hombres?|varon(?:es)?|masculino|mujer(?:es)?|femenino)\b\s*:?\s*/u';

    private const PATRON_GRUPO_EDAD = '/\b(ni(?:ñ|n)os?|pediatricos?|adultos?)\b\s*:?\s*/u';

    private const PATRON_CORTE_EDAD = '/(<=?|>=?|≥|≤)\s*(\d+)\s*a(?:ñ|n)os?\.?\s*/u';

    /** Etiqueta del tramo normal seguida de un numero o limite: "Normal < 130", "Valores normales 4.0 - 6.0". */
    private const PATRON_ETIQUETA_NORMAL = '/\b(?:valores?\s+)?(?:normal(?:es)?|negativo)\b\s*:?\s*(?=[<>≤≥\d-]|menor|menos|mayor|hasta|inferior|superior|maximo|minimo)/u';

    /**
     * Pares normal => anormal de las referencias cualitativas (antidoping, serologias).
     */
    private const CUALITATIVOS = [
        'negativo' => 'positivo',
        'no reactivo' => 'reactivo',
        'ausente' => 'presente',
    ];

    /**
     * Resultado completo de la evaluacion: ['fuera_rango' => bool, 'direccion' => 'alto'|'bajo'|null].
     */
    public static function evaluar($resultado, $valorReferencia, $sexo = null, $edad = null, $niveles = null): array
    {
        $direccion = self::direccion($resultado, $valorReferencia, $sexo, $edad, $niveles);

        return [
            'fuera_rango' => $direccion !== null || self::cualitativoAnormal($resultado, $valorReferencia),
            'direccion' => $direccion,
        ];
    }

    /**
     * Devuelve 'alto', 'bajo' o null cuando el valor esta dentro del intervalo o no se
     * puede determinar (resultados o referencias no numericas, p. ej. "Negativo").
     */
    public static function direccion($resultado, $valorReferencia, $sexo = null, $edad = null, $niveles = null): ?string
    {
        $valor = self::aNumero($resultado);
        if ($valor === null) {
            return null;
        }

        $intervalo = self::intervaloDeNiveles($niveles) ?? self::intervaloDeTexto($valorReferencia, $sexo, $edad);
        if ($intervalo === null) {
            return null;
        }

        [$min, $max] = $intervalo;

        if ($min !== null && $valor < $min) {
            return self::BAJO;
        }

        if ($max !== null && $valor > $max) {
            return self::ALTO;
        }

        return null;
    }

    /**
     * Fuera de rango: un cualitativo distinto del valor normal, o un numero
     * por arriba / debajo del intervalo. Textos libres nunca se marcan.
     */
    public static function fueraDeRango($resultado, $valorReferencia, $sexo = null, $edad = null, $niveles = null): bool
    {
        return self::evaluar($resultado, $valorReferencia, $sexo, $edad, $niveles)['fuera_rango'];
    }

    /**
     * Para referencias como "Negativo" devuelve [normal, anormal] con la misma
     * capitalizacion que la referencia: ['NEGATIVO', 'POSITIVO']. Null si no aplica.
     */
    public static function opcionesCualitativas($valorReferencia): ?array
    {
        $normal = self::claveCualitativa($valorReferencia);
        if ($normal === null) {
            return null;
        }

        $original = trim((string) $valorReferencia);
        if (self::normalizar($original) !== $normal) {
            // "NEGATIVO/NEGATIVE": se propone solo la palabra en espanol
            $original = mb_substr($original, 0, mb_strlen($normal));
        }
        $anormal = self::CUALITATIVOS[$normal];

        if ($original === mb_strtoupper($original)) {
            return [$original, mb_strtoupper($anormal)];
        }
        if (mb_substr($original, 0, 1) === mb_strtoupper(mb_substr($original, 0, 1))) {
            return [$original, ucfirst($anormal)];
        }

        return [$original, $anormal];
    }

    /**
     * Resultado de texto que no coincide con el valor normal ("Positivo" cuando la
     * referencia es "Negativo"). En pantalla y PDF se marca con *.
     */
    public static function cualitativoAnormal($resultado, $valorReferencia): bool
    {
        $normal = self::claveCualitativa($valorReferencia);
        $valor = self::normalizar($resultado);

        if ($normal === null || $valor === '') {
            return false;
        }

        return $valor !== $normal && $valor !== self::normalizar($valorReferencia);
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

    /**
     * [min, max] del nivel marcado como normal (T5). Null si el examen no tiene uno.
     */
    private static function intervaloDeNiveles($niveles): ?array
    {
        foreach ($niveles ?? [] as $nivel) {
            $nivel = (array) (is_object($nivel) && method_exists($nivel, 'toArray') ? $nivel->toArray() : $nivel);

            if (! empty($nivel['es_normal'])) {
                $min = isset($nivel['desde']) && $nivel['desde'] !== '' ? (float) $nivel['desde'] : null;
                $max = isset($nivel['hasta']) && $nivel['hasta'] !== '' ? (float) $nivel['hasta'] : null;

                return ($min === null && $max === null) ? null : [$min, $max];
            }
        }

        return null;
    }

    /**
     * [min, max] (cualquiera puede ser null) del tramo del texto que le toca al paciente.
     */
    private static function intervaloDeTexto($valorReferencia, $sexo, $edad): ?array
    {
        $texto = self::tramoAplicable($valorReferencia, $sexo, $edad);
        if ($texto === '') {
            return null;
        }

        if (preg_match(self::PATRON_MAXIMO, $texto, $m)) {
            return [null, (float) $m[1]];
        }

        if (preg_match(self::PATRON_MINIMO, $texto, $m)) {
            return [(float) $m[1], null];
        }

        if (preg_match(self::PATRON_RANGO, $texto, $m)) {
            $min = (float) $m[1];
            $max = (float) $m[2];

            return $min > $max ? [$max, $min] : [$min, $max];
        }

        return null;
    }

    /**
     * Recorta el texto de referencia al tramo del sexo / edad del paciente y, si hay
     * varios niveles, al marcado como normal. '' si el texto depende del sexo y no se conoce.
     */
    public static function tramoAplicable($valorReferencia, $sexo = null, $edad = null): string
    {
        $texto = self::normalizar($valorReferencia);
        if ($texto === '') {
            return '';
        }

        // 1. Sexo: "Mujeres > 55 Hombres > 45"
        $tramos = self::tramos($texto, self::PATRON_SEXO, fn (string $etiqueta) => str_starts_with($etiqueta, 'mujer') || $etiqueta === 'femenino' ? 'F' : 'M');
        if ($tramos !== null) {
            $sexoPaciente = self::sexo($sexo);
            if ($sexoPaciente === null || ! isset($tramos[$sexoPaciente])) {
                return '';
            }
            $texto = $tramos[$sexoPaciente];
        }

        // 2. Grupo de edad: "Niños 150 - 400 Adultos 250 - 450"
        $tramos = self::tramos($texto, self::PATRON_GRUPO_EDAD, fn (string $etiqueta) => str_starts_with($etiqueta, 'adult') ? 'adulto' : 'nino');
        if ($tramos !== null) {
            $grupo = is_numeric($edad) && (int) $edad < self::EDAD_ADULTO ? 'nino' : 'adulto';
            $texto = $tramos[$grupo] ?? reset($tramos);
        }

        // 3. Cortes de edad: "< 75 años 0 - 300 ≥ 75 años 0 - 450"
        if (preg_match_all(self::PATRON_CORTE_EDAD, $texto, $cortes, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $elegido = null;
            foreach ($cortes as $i => $corte) {
                $inicio = $corte[0][1] + strlen($corte[0][0]);
                $fin = $cortes[$i + 1][0][1] ?? strlen($texto);
                $tramo = trim(substr($texto, $inicio, $fin - $inicio));

                if ($elegido === null) {
                    $elegido = $tramo; // sin edad se usa el primero
                }
                if (is_numeric($edad) && self::cumpleCorte((int) $edad, $corte[1][0], (int) $corte[2][0])) {
                    $elegido = $tramo;
                    break;
                }
            }
            $texto = $elegido;
        }

        // 4. Varios niveles con uno "Normal": "Normal < 130 Límite 130 - 160 Elevado > 160"
        if (preg_match(self::PATRON_ETIQUETA_NORMAL, $texto, $m, PREG_OFFSET_CAPTURE)) {
            $texto = substr($texto, $m[0][1] + strlen($m[0][0]));
        }

        return trim($texto);
    }

    /**
     * Parte el texto en tramos por etiqueta: ['F' => '> 55', 'M' => '> 45'].
     * Se queda con el primer tramo de cada clave. Null si no hay etiquetas.
     */
    private static function tramos(string $texto, string $patron, callable $clave): ?array
    {
        if (! preg_match_all($patron, $texto, $etiquetas, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $tramos = [];
        foreach ($etiquetas as $i => $etiqueta) {
            $inicio = $etiqueta[0][1] + strlen($etiqueta[0][0]);
            $fin = $etiquetas[$i + 1][0][1] ?? strlen($texto);
            $tramos[$clave($etiqueta[1][0])] ??= trim(substr($texto, $inicio, $fin - $inicio));
        }

        return $tramos;
    }

    private static function cumpleCorte(int $edad, string $operador, int $limite): bool
    {
        return match ($operador) {
            '<' => $edad < $limite,
            '<=', '≤' => $edad <= $limite,
            '>' => $edad > $limite,
            default => $edad >= $limite, // >=, ≥
        };
    }

    private static function sexo($sexo): ?string
    {
        $sexo = self::normalizar($sexo);

        return match (true) {
            $sexo === '' => null,
            str_starts_with($sexo, 'f') || str_starts_with($sexo, 'muj') => 'F',
            str_starts_with($sexo, 'm') || str_starts_with($sexo, 'h') => 'M',
            default => null,
        };
    }

    /**
     * 'negativo' para "Negativo", "NEGATIVO/NEGATIVE"... Null si la referencia no es cualitativa.
     */
    private static function claveCualitativa($valorReferencia): ?string
    {
        $referencia = self::normalizar($valorReferencia);
        if ($referencia === '' || preg_match('/\d/', $referencia)) {
            return null;
        }

        foreach (array_keys(self::CUALITATIVOS) as $normal) {
            if ($referencia === $normal || preg_match('/^' . preg_quote($normal, '/') . '\b/u', $referencia)) {
                return $normal;
            }
        }

        return null;
    }

    private static function aNumero($valor): ?float
    {
        $texto = self::normalizar($valor);

        if ($texto === '' || ! preg_match('/-?\d+(?:\.\d+)?/', $texto, $m)) {
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

        return preg_replace('/\s+/u', ' ', $texto);
    }
}
