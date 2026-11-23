<?php

namespace App\Support;

use App\Models\Reporte;
use Illuminate\Support\Str;

/**
 * T10: TFGe (CKD-EPI 2021 por creatinina, 2012 por cistatina C, 2021 combinada)
 * y relacion albumina/creatinina en orina (RAC), para el PDF.
 *
 * Misma logica que resources/js/utils/funcionRenal.js (pantalla de captura).
 * Los valores no se guardan: se calculan de lo capturado y de la edad y el sexo
 * de la orden, asi que un resultado corregido siempre da la TFGe correcta.
 */
class FuncionRenal
{
    public const UNIDAD_TFGE = 'mL/min/1.73 m²';

    public const UNIDAD_RAC = 'mg/g';

    public const FORMULA_RAC = 'albúmina (mg/L) ÷ creatinina (mg/dL) × 100';

    public const CATEGORIAS_G = [
        ['codigo' => 'G1', 'rango' => '≥ 90', 'nombre' => 'Normal'],
        ['codigo' => 'G2', 'rango' => '60 – 89', 'nombre' => 'Levemente disminuida'],
        ['codigo' => 'G3a', 'rango' => '45 – 59', 'nombre' => 'Leve a moderadamente disminuida'],
        ['codigo' => 'G3b', 'rango' => '30 – 44', 'nombre' => 'Moderada a gravemente disminuida'],
        ['codigo' => 'G4', 'rango' => '15 – 29', 'nombre' => 'Gravemente disminuida'],
        ['codigo' => 'G5', 'rango' => '< 15', 'nombre' => 'Falla renal'],
    ];

    public const CATEGORIAS_A = [
        ['codigo' => 'A1', 'rango' => '< 30', 'nombre' => 'Normal o levemente aumentada'],
        ['codigo' => 'A2', 'rango' => '30 – 300', 'nombre' => 'Moderadamente aumentada'],
        ['codigo' => 'A3', 'rango' => '> 300', 'nombre' => 'Gravemente aumentada'],
    ];

    public const NOTA_TFGE = 'La TFG estimada no debe interpretarse sola para concluir daño renal; considere otros marcadores como la relación albúmina/creatinina. Se calcula con la ecuación CKD-EPI 2021, sin coeficiente racial. En pacientes con TFGe por creatinina de 45–59 mL/min/1.73 m² y relación albúmina/creatinina menor de 30 mg/g, las guías sugieren medir cistatina C para confirmar. No es válida en embarazo, insuficiencia renal aguda, amputaciones o masa muscular muy alta o muy baja.';

    public const OBSERVACIONES_PERFIL = [
        'Ecuaciones validadas para adultos (≥ 18 años). La TFGe no es válida con función renal inestable o lesión renal aguda.',
        'La creatinina varía con la masa muscular y la dieta; la cistatina C puede alterarse con disfunción tiroidea y corticoides.',
        'Un ACR elevado debe confirmarse en 2 de 3 muestras en 3 a 6 meses; el ejercicio intenso, la fiebre, la infección urinaria y la menstruación lo elevan de forma transitoria.',
    ];

    /**
     * Texto de "Observaciones" de la hoja de perfil renal cuando el estudio de
     * Cistatina C no tiene leyenda propia. El laboratorio lo puede reemplazar
     * escribiendo la leyenda de ese estudio en Gestion de estudios.
     */
    public static function observacionesPredeterminadas(): string
    {
        return self::NOTA_TFGE.' '.implode(' ', self::OBSERVACIONES_PERFIL);
    }

    public static function sexoCorto($sexo): ?string
    {
        $s = mb_strtolower(trim((string) $sexo));
        if (preg_match('/^(f|mujer)/u', $s)) {
            return 'F';
        }
        if (preg_match('/^(m|hombre|var)/u', $s)) {
            return 'M';
        }

        return null;
    }

    private static function adulto($edad): bool
    {
        return is_numeric($edad) && (float) $edad >= 18;
    }

    // ---------- Formulas (tal cual el ticket) ----------

    public static function tfgeCreatinina(?float $creatinina, $edad, $sexo): ?int
    {
        $s = self::sexoCorto($sexo);
        if (! $creatinina || $creatinina <= 0 || ! self::adulto($edad) || ! $s) {
            return null;
        }
        $k = $s === 'F' ? 0.7 : 0.9;
        $a = $s === 'F' ? -0.241 : -0.302;
        $r = $creatinina / $k;
        $tfg = 142 * (min($r, 1) ** $a) * (max($r, 1) ** -1.2) * (0.9938 ** (float) $edad);
        if ($s === 'F') {
            $tfg *= 1.012;
        }

        return (int) round($tfg);
    }

    public static function tfgeCistatina(?float $cistatina, $edad, $sexo): ?int
    {
        $s = self::sexoCorto($sexo);
        if (! $cistatina || $cistatina <= 0 || ! self::adulto($edad) || ! $s) {
            return null;
        }
        $r = $cistatina / 0.8;
        $tfg = 133 * (min($r, 1) ** -0.499) * (max($r, 1) ** -1.328) * (0.996 ** (float) $edad);
        if ($s === 'F') {
            $tfg *= 0.932;
        }

        return (int) round($tfg);
    }

    public static function tfgeCombinada(?float $creatinina, ?float $cistatina, $edad, $sexo): ?int
    {
        $s = self::sexoCorto($sexo);
        if (! $creatinina || ! $cistatina || $creatinina <= 0 || $cistatina <= 0 || ! self::adulto($edad) || ! $s) {
            return null;
        }
        $k = $s === 'F' ? 0.7 : 0.9;
        $a = $s === 'F' ? -0.219 : -0.144;
        $rc = $creatinina / $k;
        $rs = $cistatina / 0.8;
        $tfg = 135 * (min($rc, 1) ** $a) * (max($rc, 1) ** -0.544)
            * (min($rs, 1) ** -0.323) * (max($rs, 1) ** -0.778)
            * (0.9961 ** (float) $edad);
        if ($s === 'F') {
            $tfg *= 0.963;
        }

        return (int) round($tfg);
    }

    /** RAC en mg/g con un decimal. Albumina en mg/L (x100) o mg/dL (x1000). */
    public static function relacionAlbuminaCreatinina(?float $albumina, ?string $unidadAlbumina, ?float $creatininaOrina): ?float
    {
        if ($albumina === null || $albumina < 0 || ! $creatininaOrina || $creatininaOrina <= 0) {
            return null;
        }
        $factor = preg_match('/mg\s*\/\s*dl/i', (string) $unidadAlbumina) ? 1000 : 100;

        return round($albumina / $creatininaOrina * $factor, 1);
    }

    public static function categoriaG(?int $tfge): ?array
    {
        if ($tfge === null) {
            return null;
        }

        return self::CATEGORIAS_G[match (true) {
            $tfge >= 90 => 0,
            $tfge >= 60 => 1,
            $tfge >= 45 => 2,
            $tfge >= 30 => 3,
            $tfge >= 15 => 4,
            default => 5,
        }];
    }

    public static function categoriaA(?float $rac): ?array
    {
        if ($rac === null) {
            return null;
        }

        return self::CATEGORIAS_A[$rac < 30 ? 0 : ($rac <= 300 ? 1 : 2)];
    }

    // ---------- Reconocer analitos ----------

    private static function normalizar($texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', Str::lower(Str::ascii((string) $texto))));
    }

    /**
     * El estudio es de orina si su muestra o su nombre lo dicen ("Relacion albumina
     * creatinina en orina al azar", "Microalbuminuria"...), aunque el analito se
     * llame solo "Creatinina" o "Albumina".
     */
    private static function esDeOrina($muestraEstudio, $nombreEstudio): bool
    {
        $m = self::normalizar($muestraEstudio);
        $e = self::normalizar($nombreEstudio);

        return (bool) preg_match('/orina|urin/', $m)
            || (bool) preg_match('/orina|urin|microalbumin/', $e)
            || (str_contains($e, 'albumin') && str_contains($e, 'creatinin'));
    }

    public static function claveRenal($nombre, $unidad, $muestraEstudio = '', $nombreEstudio = ''): ?string
    {
        $n = self::normalizar($nombre);
        $u = self::normalizar($unidad);
        // "Creatinina serica" dentro de una depuracion de 24 h sigue siendo de sangre
        $sangre = (bool) preg_match('/seric|suero|sangre|plasma/', $n);
        $orina = ! $sangre && ((bool) preg_match('/orina|urin/', $n) || self::esDeOrina($muestraEstudio, $nombreEstudio));
        // Orina de 24 h (depuracion, microalbumina en mg/24 h) no entra en la relacion al azar
        $de24h = (bool) preg_match('/24\s*h/', $n.' '.$u.' '.self::normalizar($nombreEstudio).' '.self::normalizar($muestraEstudio));

        // El propio renglon de la relacion albumina/creatinina: se calcula solo
        if ((str_contains($n, 'albumin') && str_contains($n, 'creatinin')) || preg_match('/\b(acr|rac)\b/', $n)) {
            return ($orina || str_contains($n, 'albumin')) && ! $de24h ? 'rac' : null;
        }
        if (preg_match('/relacion|indice|depuracion|aclaramiento|\/|24 ?h/', $n)) {
            return null;
        }
        if (str_contains($n, 'cistatina')) {
            return 'cistatina';
        }
        if (str_contains($n, 'microalbumin') || (str_starts_with($n, 'albumina') && $orina)) {
            return $de24h ? null : 'albumina_orina';
        }
        if (preg_match('/^creatinina\b/', $n) && (preg_match('/mg\s*\/\s*dl/', $u) || ($orina && $u === ''))) {
            if (! $orina) {
                return 'creatinina';
            }

            return $de24h ? null : 'creatinina_orina';
        }

        return null;
    }

    private static function aNumero($valor): ?float
    {
        $texto = str_replace(',', '.', trim((string) $valor));

        return preg_match('/^\d+(\.\d+)?$/', $texto) ? (float) $texto : null;
    }

    /**
     * Valores calculados de la orden (con los estudios que ya trae cargados).
     *
     * @return array{filas: array<int, array>, valores: array, ubicados: array}
     *               filas: por id de resultado, los renglones AUTO que van debajo
     */
    public static function analizar(Reporte $reporte): array
    {
        $ubicados = [];
        foreach ($reporte->estudios as $i => $estudio) {
            $muestra = $estudio->tipo_muestra ?: ($estudio->estudio->tipo_muestra ?? '');
            $nombreEstudio = $estudio->estudio->nombre ?? '';
            foreach ($estudio->resultados as $j => $resultado) {
                if (str_contains((string) $resultado->resultado, '*')) {
                    continue; // no se imprime
                }
                $clave = self::claveRenal($resultado->examen->nombre_examen ?? '', $resultado->examen->unidad ?? '', $muestra, $nombreEstudio);
                if ($clave) {
                    $ubicados[] = [
                        'clave' => $clave, 'i' => $i, 'j' => $j,
                        'resultado' => $resultado, 'estudio' => $estudio,
                        'valor' => self::aNumero($resultado->resultado),
                    ];
                }
            }
        }

        $primero = fn (string $clave) => collect($ubicados)->first(fn ($u) => $u['clave'] === $clave && $u['valor'] !== null);
        $cr = $primero('creatinina');
        $cis = $primero('cistatina');
        $alb = $primero('albumina_orina');
        $crOrina = $primero('creatinina_orina');

        $edad = $reporte->edad;
        $sexo = $reporte->sexo;

        $valores = [
            'creatinina' => $cr['valor'] ?? null,
            'cistatina' => $cis['valor'] ?? null,
            'albumina_orina' => $alb['valor'] ?? null,
            'creatinina_orina' => $crOrina['valor'] ?? null,
            'tfge_cr' => $cr ? self::tfgeCreatinina($cr['valor'], $edad, $sexo) : null,
            'tfge_cis' => $cis ? self::tfgeCistatina($cis['valor'], $edad, $sexo) : null,
            'tfge_comb' => $cr && $cis ? self::tfgeCombinada($cr['valor'], $cis['valor'], $edad, $sexo) : null,
            'rac' => $alb && $crOrina
                ? self::relacionAlbuminaCreatinina($alb['valor'], $alb['resultado']->examen->unidad ?? '', $crOrina['valor'])
                : null,
        ];
        $valores['tfge_kdigo'] = $valores['tfge_comb'] ?? $valores['tfge_cr'] ?? $valores['tfge_cis'];
        $valores['G'] = self::categoriaG($valores['tfge_kdigo']);
        $valores['A'] = self::categoriaA($valores['rac']);

        $filaTfge = fn (string $clave, string $nombre, ?int $valor, string $formula) => [
            'clave' => $clave,
            'nombre' => $nombre,
            'valor' => $valor,
            'texto' => (string) $valor,
            'unidad' => self::UNIDAD_TFGE,
            'referencia' => '≥ 90',
            'bajo' => $valor !== null && $valor < 60,
            'alto' => false,
            'formula' => $formula,
            'categoria' => self::categoriaG($valor),
            'tabla' => 'G',
        ];

        // filas: por resultado (debajo de su analito, en pantalla)
        // alFinal: por estudio, en el orden en que aparecen (en el PDF van al final
        // del estudio para no cortar la secuencia de una quimica extensa)
        $filas = [];
        $alFinal = [];
        $agregar = function (?array $ubicado, array $fila) use (&$filas, &$alFinal) {
            if ($ubicado && $fila['valor'] !== null) {
                $filas[self::claveResultado($ubicado['resultado'])][] = $fila;
                $alFinal[spl_object_id($ubicado['estudio'])][] = $fila;
            }
        };

        foreach ($ubicados as $u) {
            if ($u['clave'] === 'creatinina' && $u['valor'] !== null) {
                $agregar($u, $filaTfge('tfge_cr', 'Tasa de filtración glomerular estimada (TFGe)', self::tfgeCreatinina($u['valor'], $edad, $sexo), 'CKD-EPI 2021'));
            }
        }
        if ($cis) {
            $agregar($cis, $filaTfge('tfge_cis', 'TFGe por cistatina C', $valores['tfge_cis'], 'CKD-EPI 2012'));
            $agregar($cis, $filaTfge('tfge_comb', 'TFGe combinada creatinina + cistatina C', $valores['tfge_comb'], 'CKD-EPI 2021'));
        }
        // Relacion albumina/creatinina (ACR). Si el estudio trae su propio renglon, ese
        // se imprime con el valor calculado (reemplazos); si no, se agrega uno AUTO.
        $reemplazos = [];
        if ($valores['rac'] !== null) {
            $filaRac = [
                'clave' => 'rac',
                'nombre' => 'Relación albúmina/creatinina (ACR)',
                'valor' => $valores['rac'],
                'texto' => number_format($valores['rac'], 1, '.', ''),
                'unidad' => self::UNIDAD_RAC,
                'referencia' => '< 30',
                'bajo' => false,
                'alto' => $valores['rac'] >= 30,
                'formula' => self::FORMULA_RAC,
                'categoria' => $valores['A'],
                'tabla' => 'A',
            ];
            $propios = collect($ubicados)->where('clave', 'rac');
            if ($propios->isNotEmpty()) {
                foreach ($propios as $u) {
                    $reemplazos[self::claveResultado($u['resultado'])] = $filaRac;
                }
            } else {
                $ultimo = collect([$alb, $crOrina])->sortBy(fn ($u) => [$u['i'], $u['j']])->last();
                $agregar($ultimo, $filaRac);
            }
        }

        return ['filas' => $filas, 'alFinal' => $alFinal, 'reemplazos' => $reemplazos, 'valores' => $valores, 'ubicados' => $ubicados];
    }

    /**
     * Clave de resultado para el arreglo de filas (id, o el objeto en un borrador sin guardar).
     */
    public static function claveResultado($resultado): int
    {
        return $resultado->id ?? spl_object_id($resultado);
    }
}
