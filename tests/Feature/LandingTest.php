<?php

namespace Tests\Feature;

use App\Models\Estudio;
use App\Models\LandingImagen;
use App\Models\LandingPromocion;
use App\Models\LandingPublicacion;
use App\Models\User;
use App\Support\TextoPublico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function promocion(array $datos = []): LandingPromocion
    {
        return LandingPromocion::create([
            'titulo' => 'Check-up esencial',
            'precio_actual' => 449,
            'precio_anterior' => 610,
            'etiqueta_tono' => 'verde',
            'activa' => true,
            ...$datos,
        ]);
    }

    public function test_inicio_solo_muestra_estudios_visibles_con_precio_publico(): void
    {
        Estudio::create(['nombre' => 'BIOMETRIA HEMATICA COMPLETA (BHC)', 'precio' => 150, 'costo' => 55, 'visible_publico' => true]);
        Estudio::create(['nombre' => 'ESTUDIO INTERNO OCULTO', 'precio' => 999, 'costo' => 10, 'visible_publico' => false]);

        $respuesta = $this->get('/inicio');

        $respuesta->assertOk()
            ->assertSee('Biometria hematica completa (BHC)')
            ->assertSee('$150')
            ->assertDontSee('ESTUDIO INTERNO OCULTO')
            ->assertDontSee('Estudio interno oculto')
            ->assertDontSee('$55');
    }

    public function test_inicio_funciona_sin_datos(): void
    {
        $this->get('/inicio')
            ->assertOk()
            ->assertSee('Por ahora no hay promociones activas')
            ->assertSee('Estamos actualizando nuestra lista de precios')
            ->assertDontSee('id="novedades"', false);
    }

    public function test_admin_abre_gestion_landing(): void
    {
        Estudio::create(['nombre' => 'Glucosa', 'precio' => 35, 'visible_publico' => true]);

        $this->actingAs($this->admin())
            ->get('/gestion-landing')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('GestionLanding')
                ->where('estudiosVisibles', 1)
                ->where('estudiosTotal', 1));
    }

    public function test_invitado_no_puede_usar_la_api_del_panel(): void
    {
        $this->getJson('/api/landing/promociones')->assertUnauthorized();
        $this->postJson('/api/landing/promociones', ['titulo' => 'X'])->assertUnauthorized();
        $this->get('/gestion-landing')->assertRedirect('/login');
    }

    public function test_admin_crea_promocion_con_imagen_y_se_publica(): void
    {
        $this->actingAs($this->admin())
            ->post('/api/landing/promociones', [
                'titulo' => 'Marcadores tumorales para mujer',
                'descripcion' => 'Panel preventivo',
                'incluye' => "- CA 15-3 (mama)\n• CA 125 (ovario)\n\n",
                'precio_actual' => '1190',
                'precio_anterior' => '1680',
                'etiqueta' => 'Octubre Rosa',
                'etiqueta_tono' => 'rosa',
                'destacada' => '1',
                'activa' => '1',
                'vigente_hasta' => now('America/Cancun')->addDays(10)->toDateString(),
                'imagen' => UploadedFile::fake()->image('liston.png', 2400, 1500),
                'imagen_alt' => 'Listón rosa',
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $promocion = LandingPromocion::with('imagen')->sole();
        $this->assertSame(['CA 15-3 (mama)', 'CA 125 (ovario)'], $promocion->incluye);
        $this->assertSame(1600, $promocion->imagen->ancho, 'La imagen se reduce a 1600 px');
        $this->assertSame('Listón rosa', $promocion->imagen->alt);

        $this->get('/inicio')
            ->assertOk()
            ->assertSee('id="datos-promociones"', false)
            ->assertSee('Marcadores tumorales para mujer');

        $oferta = $this->getJson('/api/publico/promociones')->assertOk()->json('ofertas.0');
        $this->assertSame(1190, (int) $oferta['precioActual']);
        $this->assertSame(['texto' => 'Octubre Rosa', 'tono' => 'rosa'], $oferta['etiqueta']);
        $this->assertStringEndsWith('T23:59:59-05:00', $oferta['vigenteHasta']);

        $this->get($oferta['imagen']['src'])
            ->assertOk()
            ->assertHeader('Content-Type', $promocion->imagen->mime)
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }

    public function test_reemplazar_imagen_borra_la_anterior(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/api/landing/promociones', [
            'titulo' => 'Promo', 'precio_actual' => 100, 'etiqueta_tono' => 'verde', 'activa' => 1,
            'imagen' => UploadedFile::fake()->image('a.jpg', 800, 500),
        ], ['Accept' => 'application/json'])->assertCreated();
        $promocion = LandingPromocion::sole();
        $primera = $promocion->imagen_id;

        $this->actingAs($admin)->post("/api/landing/promociones/{$promocion->id}", [
            'titulo' => 'Promo', 'precio_actual' => 100, 'etiqueta_tono' => 'verde', 'activa' => 1,
            'imagen' => UploadedFile::fake()->image('b.jpg', 800, 500),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNotSame($primera, $promocion->fresh()->imagen_id);
        $this->assertDatabaseMissing('landing_imagenes', ['id' => $primera]);
        $this->assertSame(1, LandingImagen::count());

        $this->actingAs($admin)->deleteJson("/api/landing/promociones/{$promocion->id}")->assertOk();
        $this->assertSame(0, LandingImagen::count());
    }

    public function test_validacion_explica_como_corregir(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/landing/promociones', [
                'titulo' => '',
                'precio_actual' => 500,
                'precio_anterior' => 400,
                'etiqueta_tono' => 'verde',
                'vigente_desde' => '2026-10-10',
                'vigente_hasta' => '2026-10-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titulo', 'precio_anterior', 'vigente_hasta'])
            ->assertJsonPath('errors.precio_anterior.0', 'El precio normal debe ser mayor al de promoción; si no hay descuento, déjalo vacío.');
    }

    public function test_promociones_vencidas_pausadas_o_sin_lugares_no_se_muestran(): void
    {
        $hoy = now('America/Cancun');
        $this->promocion(['titulo' => 'Vigente']);
        $this->promocion(['titulo' => 'Vencida', 'vigente_hasta' => $hoy->copy()->subDay()->toDateString()]);
        $this->promocion(['titulo' => 'Futura', 'vigente_desde' => $hoy->copy()->addDay()->toDateString()]);
        $this->promocion(['titulo' => 'Pausada', 'activa' => false]);
        $this->promocion(['titulo' => 'Agotada', 'cupos_restantes' => 0]);

        $titulos = collect($this->getJson('/api/publico/promociones')->json('ofertas'))->pluck('titulo')->all();

        $this->assertSame(['Vigente'], $titulos);
    }

    public function test_solo_una_promocion_puede_ser_destacada(): void
    {
        $vieja = $this->promocion(['titulo' => 'Vieja', 'destacada' => true]);

        $this->actingAs($this->admin())->postJson('/api/landing/promociones', [
            'titulo' => 'Nueva', 'precio_actual' => 200, 'etiqueta_tono' => 'marino', 'destacada' => true, 'activa' => true,
        ])->assertCreated();

        $this->assertFalse($vieja->fresh()->destacada);
        $this->assertTrue(LandingPromocion::where('titulo', 'Nueva')->sole()->destacada);
    }

    public function test_articulo_se_publica_escapado_y_borradores_no_son_publicos(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson('/api/landing/publicaciones', [
            'tipo' => 'articulo',
            'titulo' => 'Cómo prepararte para tu química sanguínea',
            'resumen' => 'Ayuno y consejos',
            'contenido' => "Primer párrafo <script>alert(1)</script>\n\nSegundo párrafo",
            'publicada' => true,
        ])->assertCreated();

        $articulo = LandingPublicacion::sole();
        $this->assertSame('como-prepararte-para-tu-quimica-sanguinea', $articulo->slug);

        $this->get('/novedades/' . $articulo->slug)
            ->assertOk()
            ->assertSee('Segundo párrafo')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);

        $this->get('/inicio')->assertSee('Cómo prepararte para tu química sanguínea');
        $this->get('/novedades')->assertOk()->assertSee('Cómo prepararte para tu química sanguínea');

        $this->actingAs($admin)->patchJson("/api/landing/publicaciones/{$articulo->id}/estado", ['publicada' => false])->assertOk();
        $this->get('/novedades/' . $articulo->slug)->assertNotFound();
    }

    public function test_foto_de_galeria_requiere_imagen(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/landing/publicaciones', [
            'tipo' => 'foto', 'titulo' => 'Nuestra recepción', 'publicada' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['imagen']);

        $this->actingAs($admin)->post('/api/landing/publicaciones', [
            'tipo' => 'foto', 'titulo' => 'Nuestra recepción', 'publicada' => 1,
            'imagen' => UploadedFile::fake()->image('recepcion.jpg', 1200, 750),
        ], ['Accept' => 'application/json'])->assertCreated();

        $foto = LandingPublicacion::sole();
        $this->assertNull($foto->contenido);
        $this->get('/inicio')->assertSee('data-lightbox', false)->assertSee('Nuestra recepción');
        $this->get('/novedades/' . $foto->slug)->assertNotFound();
    }

    public function test_formato_de_nombres_publicos(): void
    {
        $this->assertSame('Biometria hematica completa (BHC)', TextoPublico::nombre('BIOMETRIA HEMATICA COMPLETA (BHC)'));
        $this->assertSame('Perfil hepatico II', TextoPublico::nombre('PERFIL HEPATICO II'));
        $this->assertSame('Perfil TORCH IgG e IgM', TextoPublico::nombre('PERFIL TORCH IGG E IGM'));
        $this->assertSame('Antígeno SARS COV2 COVID-19', TextoPublico::nombre('ANTÍGENO SARS COV2 COVID-19'));
        $this->assertSame('Antidoping de 5 elementos', TextoPublico::nombre('Antidoping de 5 elementos'));
        $this->assertSame('$150', TextoPublico::precio(150.0));
        $this->assertSame('$99.50', TextoPublico::precio(99.5));
    }
}
