<?php

namespace Tests\Feature;

use App\Models\Estudio;
use App\Models\Examen;
use App\Models\ResultadoExamen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteResultadoVacioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_un_reporte_con_examenes_sin_resultado(): void
    {
        $estudio = Estudio::create(['nombre' => 'Biometría hemática', 'precio' => 150]);
        $hemoglobina = Examen::create(['estudio_id' => $estudio->id, 'nombre_examen' => 'Hemoglobina', 'valor_referencia' => '12 - 16']);
        $hematocrito = Examen::create(['estudio_id' => $estudio->id, 'nombre_examen' => 'Hematocrito', 'valor_referencia' => '36 - 46']);

        // El formulario manda "" en los exámenes que no se capturaron
        $this->actingAs(User::factory()->create())->postJson('/api/reportes', [
            'cliente' => ['nombre' => 'Ana López', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Femenino'],
            'toma_muestra' => '2026-10-21T08:00',
            'fecha_reporte' => '2026-10-21T09:00',
            'fecha_validacion' => '2026-10-21T09:00',
            'estudios' => [[
                'id' => $estudio->id,
                'precio' => 150,
                'elaboro' => 'QFB',
                'valido' => 'QFB',
                'tipo_muestra' => 'Sangre',
                'metodo' => 'Automatizado',
                'examenes' => [
                    ['id' => $hemoglobina->id, 'resultado' => '14'],
                    ['id' => $hematocrito->id, 'resultado' => ''],
                ],
            ]],
        ])->assertOk();

        $this->assertSame('', ResultadoExamen::where('examen_id', $hematocrito->id)->value('resultado'));
    }
}
