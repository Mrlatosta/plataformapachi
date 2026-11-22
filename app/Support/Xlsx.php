<?php

namespace App\Support;

/**
 * Hoja de Excel (.xlsx) sencilla, sin librerias ni extensiones de PHP (ni zip):
 * texto, numeros, moneda, negritas y anchos de columna. Para exportar reportes.
 *
 * Cada fila es un arreglo de celdas; una celda es un valor (string|int|float|null)
 * o ['v' => valor, 's' => estilo]. Estilos: negrita, dinero, dinero_negrita,
 * encabezado, titulo, numero.
 */
class Xlsx
{
    private const ESTILOS = [
        'normal' => 0, 'negrita' => 1, 'dinero' => 2, 'dinero_negrita' => 3,
        'encabezado' => 4, 'titulo' => 5, 'numero' => 6,
    ];

    /**
     * @param  array<int, array<int, mixed>>  $filas
     * @param  array<int, float>  $anchos  ancho de cada columna (en caracteres)
     */
    public static function generar(string $nombreHoja, array $filas, array $anchos = []): string
    {
        return self::zip([
            '[Content_Types].xml' => self::tiposContenido(),
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="'.self::xml(mb_substr($nombreHoja, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => self::estilos(),
            'xl/worksheets/sheet1.xml' => self::hoja($filas, $anchos),
        ]);
    }

    private static function tiposContenido(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;$&quot;#,##0.00"/></numFmts>'
            .'<fonts count="3">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="164" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private static function hoja(array $filas, array $anchos): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($anchos) {
            $xml .= '<cols>';
            foreach (array_values($anchos) as $i => $ancho) {
                $n = $i + 1;
                $xml .= '<col min="'.$n.'" max="'.$n.'" width="'.(float) $ancho.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach (array_values($filas) as $f => $fila) {
            $r = $f + 1;
            $xml .= '<row r="'.$r.'">';
            foreach (array_values($fila) as $c => $celda) {
                [$valor, $estilo] = is_array($celda) ? [$celda['v'] ?? null, $celda['s'] ?? 'normal'] : [$celda, 'normal'];
                if ($valor === null || $valor === '') {
                    continue;
                }
                $ref = self::columna($c).$r;
                $s = self::ESTILOS[$estilo] ?? 0;
                if (is_int($valor) || is_float($valor)) {
                    $xml .= '<c r="'.$ref.'" s="'.$s.'"><v>'.$valor.'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" s="'.$s.'" t="inlineStr"><is><t xml:space="preserve">'.self::xml((string) $valor).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    /** 0 → A, 25 → Z, 26 → AA */
    private static function columna(int $indice): string
    {
        $letras = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26).$letras;
        }

        return $letras;
    }

    private static function xml(string $texto): string
    {
        // Quita caracteres de control que Excel no acepta
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto);

        return htmlspecialchars((string) $texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * ZIP sin compresion (metodo "store"), suficiente para un .xlsx.
     *
     * @param  array<string, string>  $archivos  ruta => contenido
     */
    private static function zip(array $archivos): string
    {
        $datos = '';
        $central = '';
        $hora = (int) ((date('H') << 11) | (date('i') << 5) | intdiv((int) date('s'), 2));
        $fecha = (int) (((date('Y') - 1980) << 9) | (date('n') << 5) | date('j'));

        foreach ($archivos as $nombre => $contenido) {
            $crc = crc32($contenido);
            $tam = strlen($contenido);
            $desplazamiento = strlen($datos);
            // version, banderas (0x0800 = nombres UTF-8), metodo 0, hora, fecha, crc, tamaños, largo del nombre, extra
            $encabezado = pack('vvvvvVVVvv', 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam, strlen($nombre), 0);

            $datos .= pack('V', 0x04034b50).$encabezado.$nombre.$contenido;
            $central .= pack('V', 0x02014b50).pack('v', 20).$encabezado
                .pack('vvvVV', 0, 0, 0, 0, $desplazamiento).$nombre;
        }

        return $datos.$central.pack('VvvvvVVv', 0x06054b50, 0, 0, count($archivos), count($archivos), strlen($central), strlen($datos), 0);
    }
}
