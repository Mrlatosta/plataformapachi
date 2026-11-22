<?php

namespace Tests\Unit;

use App\Support\FuncionRenal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * T10: casos de prueba del ticket. Los mismos que tests/js/funcionRenal.test.mjs,
 * para que pantalla (JS) y PDF (PHP) den siempre el mismo numero.
 */
class FuncionRenalTest extends TestCase
{
    public static function casosCreatinina(): array
    {
        return [
            [0.71, 48, 'F', 105],
            [1.0, 50, 'M', 92],
            [1.0, 50, 'F', 69],
            [1.3, 70, 'M', 59],
            [2.5, 65, 'M', 28],
            [1.1, 20, 'M', 99],
        ];
    }

    #[DataProvider('casosCreatinina')]
    public function test_tfge_por_creatinina(float $cr, int $edad, string $sexo, int $esperada): void
    {
        $this->assertSame($esperada, FuncionRenal::tfgeCreatinina($cr, $edad, $sexo));
    }

    public static function casosCistatina(): array
    {
        return [
            [1.0, 1.0, 50, 'M', 81, 88],
            [1.2, 1.2, 60, 'F', 57, 56],
            [0.8, 0.7, 40, 'F', 113, 108],
            [2.0, 1.8, 70, 'M', 34, 35],
        ];
    }

    #[DataProvider('casosCistatina')]
    public function test_tfge_por_cistatina_y_combinada(float $cr, float $cis, int $edad, string $sexo, int $porCistatina, int $combinada): void
    {
        $this->assertSame($porCistatina, FuncionRenal::tfgeCistatina($cis, $edad, $sexo));
        $this->assertSame($combinada, FuncionRenal::tfgeCombinada($cr, $cis, $edad, $sexo));
    }

    public function test_sexo_como_lo_guarda_el_sistema_y_menores(): void
    {
        $this->assertSame(69, FuncionRenal::tfgeCreatinina(1.0, 50, 'Femenino'));
        $this->assertSame(92, FuncionRenal::tfgeCreatinina(1.0, 50, 'masculino'));
        $this->assertNull(FuncionRenal::tfgeCreatinina(1.0, 17, 'Masculino'));
        $this->assertNull(FuncionRenal::tfgeCreatinina(1.0, null, 'Masculino'));
        $this->assertNull(FuncionRenal::tfgeCreatinina(1.0, 40, ''));
    }

    public function test_rac_y_categorias(): void
    {
        $this->assertSame(30.0, FuncionRenal::relacionAlbuminaCreatinina(45, 'mg/L', 150));
        $this->assertSame(30.0, FuncionRenal::relacionAlbuminaCreatinina(4.5, 'mg/dL', 150));
        $this->assertSame('A2', FuncionRenal::categoriaA(30.0)['codigo']);
        $this->assertSame('A1', FuncionRenal::categoriaA(12.0)['codigo']);
        $this->assertSame('A3', FuncionRenal::categoriaA(450.0)['codigo']);

        // Clasificacion KDIGO del formato (TFGe → G)
        $this->assertSame(['G1', 'G2', 'G3b', 'G3a'], array_map(
            fn ($v) => FuncionRenal::categoriaG($v)['codigo'],
            [92, 69, 35, 56],
        ));
    }

    public function test_reconoce_los_analitos(): void
    {
        $this->assertSame('creatinina', FuncionRenal::claveRenal('Creatinina', 'mg/dL', 'Suero'));
        $this->assertSame('creatinina_orina', FuncionRenal::claveRenal('Creatinina', 'mg/dl', 'ORINA'));
        $this->assertSame('albumina_orina', FuncionRenal::claveRenal('Albúmina en orina', 'mg/L', ''));
        $this->assertSame('cistatina', FuncionRenal::claveRenal('Cistatina C', 'mg/L', 'SUERO'));
        $this->assertNull(FuncionRenal::claveRenal('Albumina', 'g/dL', 'Suero'));
        $this->assertNull(FuncionRenal::claveRenal('Creatine Kinase (CK)', 'U/L', 'suero'));
    }
}
