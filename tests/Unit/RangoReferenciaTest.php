<?php

namespace Tests\Unit;

use App\Support\RangoReferencia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Los mismos casos se prueban en JS (tests/js/rangoReferencia.test.mjs) para que
 * la marca que se ve al capturar sea la misma que se guarda e imprime.
 */
class RangoReferenciaTest extends TestCase
{
    public static function casos(): array
    {
        $casos = json_decode(file_get_contents(__DIR__ . '/../fixtures/rangos.json'), true);

        $porNombre = [];
        foreach ($casos as $caso) {
            $porNombre[$caso['caso']] = [$caso];
        }

        return $porNombre;
    }

    #[DataProvider('casos')]
    public function test_evalua_el_resultado_contra_la_referencia(array $caso): void
    {
        $evaluacion = RangoReferencia::evaluar(
            $caso['resultado'],
            $caso['referencia'],
            $caso['sexo'] ?? null,
            $caso['edad'] ?? null,
            $caso['niveles'] ?? null,
        );

        $this->assertSame($caso['fuera'], $evaluacion['fuera_rango'], 'fuera_rango');
        $this->assertSame($caso['direccion'], $evaluacion['direccion'], 'direccion');
    }

    public function test_opciones_cualitativas_respetan_mayusculas(): void
    {
        $this->assertSame(['NEGATIVO', 'POSITIVO'], RangoReferencia::opcionesCualitativas('NEGATIVO'));
        $this->assertSame(['Negativo', 'Positivo'], RangoReferencia::opcionesCualitativas('Negativo'));
        $this->assertSame(['NEGATIVO', 'POSITIVO'], RangoReferencia::opcionesCualitativas('NEGATIVO/NEGATIVE'));
        $this->assertNull(RangoReferencia::opcionesCualitativas('70 - 110'));
    }
}
