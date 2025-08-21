<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EstudioController;
use App\Http\Controllers\CapturaResultadosController;
use App\Http\Controllers\ReporteController;


Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/gestion-estudios', function () {
    return Inertia::render('GestionEstudios');
})->middleware(['auth', 'verified'])->name('gestion.estudios');

Route::get('/captura-resultados', function () {
    return Inertia::render('CapturaResultados');
})->middleware(['auth', 'verified'])->name('captura.resultados');

Route::get('/api/estudios', [EstudioController::class, 'index']);
Route::post('/api/estudios', [EstudioController::class, 'store']);
Route::get('/api/estudios/{id}', [EstudioController::class, 'show']);
Route::put('/api/estudios/{id}', [EstudioController::class, 'update']);


Route::get('/api/estudios-con-examenes', [CapturaResultadosController::class, 'index']);
Route::post('/api/resultados', [CapturaResultadosController::class, 'store']);
Route::get('/api/resultados/pdf', [CapturaResultadosController::class, 'generarPDF']);


Route::post('/api/reportes', [ReporteController::class, 'store']);

Route::post('/guardar-reporte', [ReporteController::class, 'guardar'])->name('reporte.guardar');

Route::get('/api/reportes/{id}/pdf', [ReporteController::class, 'generarPDF']);

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Reporte;


Route::get('/prueba-pdf', function () {
    // Simular reporte para pruebas sin consultar BD
    $reporte = (object) [
        'folio' => 'PRUEBA-001',
        'toma_muestra' => now(),
        'fecha_reporte' => now(),
        'fecha_validacion' => now(),
        'nombre_cliente' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'fecha_nacimiento' => '1990-05-10',
        'edad' => 34,
        'sexo' => 'masculino',
        'medico_solicitante' => 'Dr. Ruiz Martínez',
        'estudios' => [
            (object) [
                'elaboro' => 'QFB Angel Augusto Perez Arias',
                'valido' => 'QFB Angel Augusto Perez Arias',
                'tipo_muestra' => 'Sangre',
                'metodo' => 'Colorimetría',
                'estudio' => (object) ['nombre' => 'QUÍMICA SANGUÍNEA'],
                'resultados' => [
                    (object) [
                        'resultado' => '85',
                        'fuera_rango' => false,
                        'examen' => (object) [
                            'nombre_examen' => 'Glucosa',
                            'unidad' => 'mg/dL',
                            'valor_referencia' => '70 - 110',
                        ],
                    ],
                    (object) [
                        'resultado' => '250',
                        'fuera_rango' => true,
                        'examen' => (object) [
                            'nombre_examen' => 'Triglicéridos',
                            'unidad' => 'mg/dL',
                            'valor_referencia' => '< 150',
                        ],
                    ],
                ],
            ]
        ]
    ];

    $pdf = Pdf::loadView('pdf.reporte_biolab', compact('reporte'));
    return $pdf->stream('reporte.pdf');
});


require __DIR__.'/auth.php';
