<?php

namespace Tests\Feature;

use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Jornada;
use App\Models\JornadaParticipante;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaRapidaTest extends TestCase
{
    use RefreshDatabase;

    private Estudio $antidoping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->antidoping = Estudio::create([
            'nombre' => 'Antidoping de 5 elementos',
            'tipo_muestra' => 'Orina',
            'metodo' => 'INMUNOCROMATOGRAFIA',
            'precio' => 500,
        ]);
        foreach (['Marihuana', 'Cocaína', 'Anfetaminas'] as $orden => $droga) {
            Examen::create([
                'estudio_id' => $this->antidoping->id,
                'nombre_examen' => $droga,
                'valor_referencia' => 'NEGATIVO',
                'orden' => $orden,
            ]);
        }
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function crearJornada(array $cambios = []): Jornada
    {
        $respuesta = $this->postJson('/api/jornadas', [
            'nombre' => 'Antidoping Empresa X',
            'empresa' => 'Empresa X',
            'fecha_toma' => '2026-10-23T08:00',
            'aplica_iva' => false,
            'estudios' => [[
                'estudio_id' => $this->antidoping->id,
                'tipo_muestra' => 'Orina',
                'metodo' => 'INMUNOCROMATOGRAFIA',
                'elaboro' => 'QFB',
                'valido' => 'QFB',
                'precio' => 350,
                'predeterminados' => [],
            ]],
            ...$cambios,
        ])->assertCreated();

        return Jornada::findOrFail($respuesta->json('id'));
    }

    private function examen(string $nombre): Examen
    {
        return Examen::where('nombre_examen', $nombre)->firstOrFail();
    }

    public function test_invitado_no_accede(): void
    {
        $this->get('/captura-rapida')->assertRedirect('/login');
        $this->postJson('/api/jornadas', [])->assertUnauthorized();
    }

    public function test_la_jornada_arranca_con_el_valor_normal_de_cada_examen(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();

        $this->get("/captura-rapida/{$jornada->id}")
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('CapturaRapida/Jornada')
                ->where('jornada.fecha_toma', '2026-10-23T08:00')
                ->where('jornada.estudios.0.precio', 350)
                ->has('jornada.estudios.0.examenes', 3)
                ->where('jornada.estudios.0.examenes.0.nombre_examen', 'Marihuana')
                ->where('jornada.estudios.0.examenes.0.predeterminado', 'NEGATIVO'));
    }

    public function test_agrega_lista_y_calcula_la_edad_a_la_fecha_de_toma(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();

        $this->postJson("/api/jornadas/{$jornada->id}/participantes", [
            'participantes' => [
                ['nombre' => '  Juan   Pérez ', 'sexo' => 'Masculino', 'fecha_nacimiento' => '1990-10-24'],
                ['nombre' => 'Ana Ruiz', 'sexo' => 'Femenino', 'edad' => 41],
                ['nombre' => 'Sin Datos'],
            ],
        ])->assertCreated()
            ->assertJsonCount(3, 'participantes')
            // La tabla necesita el estado y la edad ya calculada en la respuesta
            ->assertJsonPath('participantes.0.estado', 'pendiente')
            ->assertJsonPath('participantes.0.edad', 35)
            ->assertJsonPath('participantes.0.reporte', null);

        $juan = JornadaParticipante::where('nombre', 'Juan Pérez')->firstOrFail();
        $this->assertSame(35, $juan->edad); // cumple 36 un día después de la toma
        $this->assertSame(41, JornadaParticipante::where('nombre', 'Ana Ruiz')->value('edad'));
    }

    public function test_no_se_marca_listo_sin_sexo_ni_edad(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $participante = $jornada->participantes()->create(['nombre' => 'Sin Datos']);

        $this->patchJson("/api/jornadas/{$jornada->id}/participantes/{$participante->id}", ['estado' => 'listo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado');

        $this->assertSame('pendiente', $participante->fresh()->estado);
    }

    public function test_genera_reportes_con_positivos_marcados_fuera_de_rango(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $cocaina = $this->examen('Cocaína');

        $positivo = $jornada->participantes()->create(['nombre' => 'Luis Gómez', 'sexo' => 'Masculino', 'edad' => 30]);
        $negativo = $jornada->participantes()->create(['nombre' => 'Eva Díaz', 'sexo' => 'Femenino', 'edad' => 28]);
        $pendiente = $jornada->participantes()->create(['nombre' => 'Aún No', 'sexo' => 'Femenino', 'edad' => 50]);

        $this->patchJson("/api/jornadas/{$jornada->id}/participantes/{$positivo->id}", [
            'resultados' => [$cocaina->id => 'POSITIVO'],
            'estado' => 'listo',
        ])->assertOk();
        $this->patchJson("/api/jornadas/{$jornada->id}/participantes/{$negativo->id}", ['estado' => 'listo'])->assertOk();

        $this->postJson("/api/jornadas/{$jornada->id}/reportes")
            ->assertOk()
            ->assertJsonPath('generados', 2);

        $this->assertNull($pendiente->fresh()->reporte_id);

        $reporte = Reporte::with('estudios.resultados')->findOrFail($positivo->fresh()->reporte_id);
        $this->assertSame('Luis Gómez', $reporte->nombre_cliente);
        $this->assertSame(30, $reporte->edad);
        $this->assertNull($reporte->medico_solicitante);
        $this->assertSame('2026-10-23 08:00:00', (string) $reporte->toma_muestra);
        $this->assertEquals(350, $reporte->estudios[0]->precio);

        $resultados = $reporte->estudios[0]->resultados->keyBy('examen_id');
        $this->assertCount(3, $resultados);
        $this->assertSame('POSITIVO', $resultados[$cocaina->id]->resultado);
        $this->assertTrue($resultados[$cocaina->id]->fuera_rango);
        $this->assertSame('NEGATIVO', $resultados[$this->examen('Marihuana')->id]->resultado);
        $this->assertFalse($resultados[$this->examen('Marihuana')->id]->fuera_rango);

        // El PDF sale aunque solo se haya capturado la edad
        $this->get("/api/reportes/{$reporte->id}/pdf-preview")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Folios consecutivos y una segunda corrida no duplica reportes
        $this->assertNotSame($reporte->folio, Reporte::find($negativo->fresh()->reporte_id)->folio);
        $this->postJson("/api/jornadas/{$jornada->id}/reportes")->assertJsonPath('generados', 0);
        $this->assertSame(2, Reporte::count());
    }

    public function test_con_reporte_emitido_ya_no_se_edita_ni_se_borra(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $participante = $jornada->participantes()->create(['nombre' => 'Luis Gómez', 'sexo' => 'Masculino', 'edad' => 30, 'estado' => 'listo']);
        $this->postJson("/api/jornadas/{$jornada->id}/reportes")->assertOk();

        $this->patchJson("/api/jornadas/{$jornada->id}/participantes/{$participante->id}", ['nombre' => 'Otro'])
            ->assertUnprocessable();
        $this->deleteJson("/api/jornadas/{$jornada->id}/participantes/{$participante->id}")
            ->assertUnprocessable();

        // Borrar la jornada conserva el reporte
        $this->deleteJson("/api/jornadas/{$jornada->id}")->assertOk();
        $this->assertSame(1, Reporte::count());
    }

    // ---------- Casos límite ----------

    private function glucosa(): Estudio
    {
        $glucosa = Estudio::create(['nombre' => 'Glucosa', 'precio' => 60]);
        Examen::create(['estudio_id' => $glucosa->id, 'nombre_examen' => 'Glucosa sérica', 'valor_referencia' => '70 - 110', 'unidad' => 'mg/dL']);

        return $glucosa;
    }

    private function configEstudio(Estudio $estudio, array $predeterminados = []): array
    {
        return [
            'estudio_id' => $estudio->id,
            'precio' => 100,
            'predeterminados' => $predeterminados,
        ];
    }

    public function test_todas_las_rutas_piden_sesion(): void
    {
        $this->getJson('/captura-rapida/1')->assertUnauthorized();
        $this->putJson('/api/jornadas/1', [])->assertUnauthorized();
        $this->deleteJson('/api/jornadas/1')->assertUnauthorized();
        $this->postJson('/api/jornadas/1/participantes', [])->assertUnauthorized();
        $this->patchJson('/api/jornadas/1/participantes/1', [])->assertUnauthorized();
        $this->deleteJson('/api/jornadas/1/participantes/1')->assertUnauthorized();
        $this->postJson('/api/jornadas/1/reportes')->assertUnauthorized();
        $this->getJson('/api/jornadas/1/csv')->assertUnauthorized();
    }

    public function test_jornada_inexistente_da_404(): void
    {
        $this->actingAs($this->admin());
        $this->get('/captura-rapida/999999')->assertNotFound();
        $this->postJson('/api/jornadas/999999/participantes', ['participantes' => [['nombre' => 'X']]])->assertNotFound();
    }

    public function test_no_se_toca_un_colaborador_de_otra_jornada(): void
    {
        $this->actingAs($this->admin());
        $a = $this->crearJornada();
        $b = $this->crearJornada(['nombre' => 'Otra']);
        $ajeno = $b->participantes()->create(['nombre' => 'De B', 'sexo' => 'Masculino', 'edad' => 20]);

        $this->patchJson("/api/jornadas/{$a->id}/participantes/{$ajeno->id}", ['nombre' => 'Hackeado'])->assertNotFound();
        $this->deleteJson("/api/jornadas/{$a->id}/participantes/{$ajeno->id}")->assertNotFound();
        $this->assertSame('De B', $ajeno->fresh()->nombre);
    }

    public function test_valida_datos_de_colaboradores_con_mensajes_en_espanol(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $url = "/api/jornadas/{$jornada->id}/participantes";

        $this->postJson($url, ['participantes' => [['nombre' => '   ']]])->assertJsonValidationErrors('participantes.0.nombre');
        $this->postJson($url, ['participantes' => [['nombre' => 'A', 'sexo' => 'Otro']]])->assertJsonValidationErrors('participantes.0.sexo');
        $this->postJson($url, ['participantes' => [['nombre' => 'A', 'edad' => 150]]])->assertJsonValidationErrors('participantes.0.edad');
        $this->postJson($url, ['participantes' => [['nombre' => 'A', 'fecha_nacimiento' => now()->addDay()->toDateString()]]])
            ->assertJsonValidationErrors('participantes.0.fecha_nacimiento');

        $demasiados = array_fill(0, 501, ['nombre' => 'X']);
        $respuesta = $this->postJson($url, ['participantes' => $demasiados])->assertUnprocessable();

        // Los mensajes que ve el muestreador deben estar en español
        foreach ([
            $respuesta->json('errors.participantes.0'),
            $this->postJson($url, ['participantes' => [['nombre' => 'A', 'sexo' => 'Otro']]])->json('errors')['participantes.0.sexo'][0],
            $this->postJson($url, ['participantes' => [['nombre' => 'A', 'edad' => 150]]])->json('errors')['participantes.0.edad'][0],
        ] as $mensaje) {
            $this->assertDoesNotMatchRegularExpression('/\b(The|field|must)\b/', $mensaje);
        }

        $this->assertSame(0, $jornada->participantes()->count());
    }

    public function test_valida_la_configuracion_de_la_jornada(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();

        $this->putJson("/api/jornadas/{$jornada->id}", ['nombre' => 'X', 'fecha_toma' => '2026-10-23T08:00', 'estudios' => []])
            ->assertJsonValidationErrors('estudios');
        $this->putJson("/api/jornadas/{$jornada->id}", [
            'nombre' => 'X',
            'fecha_toma' => '2026-10-23T08:00',
            'estudios' => [$this->configEstudio($this->antidoping), $this->configEstudio($this->antidoping)],
        ])->assertJsonValidationErrors('estudios.1.estudio_id');
    }

    public function test_corregir_datos_recalcula_la_edad(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $p = $jornada->participantes()->create(['nombre' => 'Ana', 'edad' => 30]);
        $url = "/api/jornadas/{$jornada->id}/participantes/{$p->id}";

        $this->patchJson($url, ['nombre' => 'Ana', 'sexo' => 'Femenino', 'fecha_nacimiento' => '2000-01-15', 'edad' => 99])
            ->assertOk()
            ->assertJsonPath('participante.edad', 26)
            ->assertJsonPath('participante.sexo', 'Femenino');

        // Quitar la fecha y escribir la edad a mano
        $this->patchJson($url, ['nombre' => 'Ana', 'sexo' => 'Femenino', 'fecha_nacimiento' => null, 'edad' => 41])
            ->assertOk()
            ->assertJsonPath('participante.edad', 41)
            ->assertJsonPath('participante.fecha_nacimiento', null);

        // Cambiar solo el sexo no borra la edad
        $this->patchJson($url, ['sexo' => 'Masculino'])->assertOk()->assertJsonPath('participante.edad', 41);
    }

    public function test_resultado_vaciado_a_proposito_no_vuelve_al_predeterminado(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $marihuana = $this->examen('Marihuana');
        $p = $jornada->participantes()->create(['nombre' => 'Ana', 'sexo' => 'Femenino', 'edad' => 30]);

        $this->patchJson("/api/jornadas/{$jornada->id}/participantes/{$p->id}", [
            'resultados' => [$marihuana->id => ''],
            'estado' => 'listo',
        ])->assertOk();
        $this->postJson("/api/jornadas/{$jornada->id}/reportes")->assertJsonPath('generados', 1);

        $resultado = Reporte::findOrFail($p->fresh()->reporte_id)->estudios[0]->resultados->firstWhere('examen_id', $marihuana->id);
        $this->assertSame('', $resultado->resultado);
        $this->assertFalse($resultado->fuera_rango);
    }

    public function test_examen_numerico_marca_alto_y_bajo(): void
    {
        $this->actingAs($this->admin());
        $glucosa = $this->glucosa();
        $jornada = $this->crearJornada(['estudios' => [$this->configEstudio($glucosa)]]);
        $examen = $this->examen('Glucosa sérica');

        $alto = $jornada->participantes()->create(['nombre' => 'Alto', 'sexo' => 'Masculino', 'edad' => 50, 'estado' => 'listo', 'resultados' => [$examen->id => '150']]);
        $bajo = $jornada->participantes()->create(['nombre' => 'Bajo', 'sexo' => 'Masculino', 'edad' => 50, 'estado' => 'listo', 'resultados' => [$examen->id => '65,5']]);
        $normal = $jornada->participantes()->create(['nombre' => 'Normal', 'sexo' => 'Masculino', 'edad' => 50, 'estado' => 'listo', 'resultados' => [$examen->id => '90']]);

        $this->postJson("/api/jornadas/{$jornada->id}/reportes")->assertJsonPath('generados', 3);

        $resultado = fn ($p) => Reporte::findOrFail($p->fresh()->reporte_id)->estudios[0]->resultados[0];
        $this->assertSame('alto', $resultado($alto)->direccion_rango);
        $this->assertSame('bajo', $resultado($bajo)->direccion_rango);
        $this->assertFalse($resultado($normal)->fuera_rango);
        $this->assertNull($resultado($normal)->direccion_rango);
    }

    public function test_predeterminados_personalizados_incluido_vacio(): void
    {
        $this->actingAs($this->admin());
        $glucosa = $this->glucosa();
        $marihuana = $this->examen('Marihuana');
        $cocaina = $this->examen('Cocaína');
        $glucosaSerica = $this->examen('Glucosa sérica');

        $jornada = $this->crearJornada(['estudios' => [
            // El formulario manda "" cuando se elige "Vacío"
            $this->configEstudio($this->antidoping, [$marihuana->id => '', $cocaina->id => 'NEGATIVO']),
            $this->configEstudio($glucosa, [$glucosaSerica->id => '90']),
        ]]);

        $this->get("/captura-rapida/{$jornada->id}")
            ->assertInertia(fn ($pagina) => $pagina
                ->where('jornada.estudios.0.examenes.0.predeterminado', '')          // Marihuana: vacío a propósito
                ->where('jornada.estudios.0.examenes.1.predeterminado', 'NEGATIVO')
                ->where('jornada.estudios.0.examenes.2.predeterminado', 'NEGATIVO')  // Anfetaminas: sin configurar
                ->where('jornada.estudios.1.examenes.0.predeterminado', '90'));
    }

    public function test_generar_solo_los_elegidos_y_lista_vacia_no_emite_todo(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $uno = $jornada->participantes()->create(['nombre' => 'Uno', 'sexo' => 'Masculino', 'edad' => 20, 'estado' => 'listo']);
        $dos = $jornada->participantes()->create(['nombre' => 'Dos', 'sexo' => 'Masculino', 'edad' => 20, 'estado' => 'listo']);

        $this->postJson("/api/jornadas/{$jornada->id}/reportes", ['participantes' => []])->assertJsonPath('generados', 0);
        $this->postJson("/api/jornadas/{$jornada->id}/reportes", ['participantes' => [$dos->id]])->assertJsonPath('generados', 1);

        $this->assertNull($uno->fresh()->reporte_id);
        $this->assertNotNull($dos->fresh()->reporte_id);
    }

    public function test_omite_a_quien_le_quitaron_datos_despues_de_listo(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $p = $jornada->participantes()->create(['nombre' => 'Ana', 'sexo' => 'Femenino', 'edad' => 30, 'estado' => 'listo']);
        $this->patchJson("/api/jornadas/{$jornada->id}/participantes/{$p->id}", ['sexo' => null])->assertOk();

        $this->postJson("/api/jornadas/{$jornada->id}/reportes")
            ->assertJsonPath('generados', 0)
            ->assertJsonPath('omitidos.0', 'Ana: falta sexo');
    }

    public function test_medico_registrado_iva_y_estudio_borrado_del_catalogo(): void
    {
        $this->actingAs($this->admin());
        $medico = \App\Models\Medico::create(['nombre' => 'Dra. Ruiz', 'usuario' => 'ruiz', 'password' => 'secreto123', 'activo' => true]);
        $glucosa = $this->glucosa();
        $jornada = $this->crearJornada([
            'medico_id' => $medico->id,
            'medico_solicitante' => 'Texto que se ignora',
            'aplica_iva' => true,
            'estudios' => [$this->configEstudio($this->antidoping), $this->configEstudio($glucosa)],
        ]);
        $this->assertSame('Dra. Ruiz', $jornada->medico_solicitante);

        // Si un estudio se borra del catálogo, la jornada sigue funcionando con el resto
        $glucosa->examenes()->delete();
        $glucosa->delete();
        $this->get("/captura-rapida/{$jornada->id}")->assertOk()->assertInertia(fn ($pagina) => $pagina->has('jornada.estudios', 1));

        $p = $jornada->participantes()->create(['nombre' => 'Ana', 'sexo' => 'Femenino', 'edad' => 30, 'estado' => 'listo']);
        $this->postJson("/api/jornadas/{$jornada->id}/reportes")->assertJsonPath('generados', 1);

        $reporte = Reporte::findOrFail($p->fresh()->reporte_id);
        $this->assertSame($medico->id, $reporte->medico_id);
        $this->assertSame('Dra. Ruiz', $reporte->medico_solicitante);
        $this->assertTrue($reporte->aplica_iva);
        $this->assertCount(1, $reporte->estudios);
    }

    public function test_lista_de_jornadas_cuenta_por_etapa(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $jornada->participantes()->create(['nombre' => 'Pendiente']);
        $jornada->participantes()->create(['nombre' => 'Listo', 'sexo' => 'Masculino', 'edad' => 20, 'estado' => 'listo']);
        $emitido = $jornada->participantes()->create(['nombre' => 'Emitido', 'sexo' => 'Masculino', 'edad' => 20, 'estado' => 'listo']);
        $this->postJson("/api/jornadas/{$jornada->id}/reportes", ['participantes' => [$emitido->id]]);

        $this->get('/captura-rapida')->assertInertia(fn ($pagina) => $pagina
            ->component('CapturaRapida/Index')
            ->where('jornadas.0.participantes', 3)
            ->where('jornadas.0.listos', 1)
            ->where('jornadas.0.reportados', 1)
            ->where('jornadas.0.estudios.0', 'Antidoping de 5 elementos'));
    }

    public function test_csv_escapa_comas_y_comillas(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada(['nombre' => 'Jornada: "Empresa/X"']);
        $jornada->participantes()->create(['nombre' => 'Pérez, Juan "Toño"', 'sexo' => 'Masculino', 'edad' => 30]);

        $respuesta = $this->get("/api/jornadas/{$jornada->id}/csv")->assertOk();
        $this->assertStringContainsString('"Pérez, Juan ""Toño"""', $respuesta->streamedContent());
        $this->assertStringNotContainsString('/', (string) $respuesta->headers->get('content-disposition'));
    }

    public function test_exporta_csv_con_resultados(): void
    {
        $this->actingAs($this->admin());
        $jornada = $this->crearJornada();
        $jornada->participantes()->create([
            'nombre' => 'Luis Gómez', 'sexo' => 'Masculino', 'edad' => 30, 'estado' => 'listo',
            'resultados' => [$this->examen('Cocaína')->id => 'POSITIVO'],
        ]);

        $csv = $this->get("/api/jornadas/{$jornada->id}/csv")->assertOk()->streamedContent();

        $this->assertStringContainsString('Marihuana,Cocaína,Anfetaminas', $csv);
        $this->assertStringContainsString('"Luis Gómez",Masculino,30,,NEGATIVO,POSITIVO,NEGATIVO,Cocaína,Capturado', $csv);
    }
}
