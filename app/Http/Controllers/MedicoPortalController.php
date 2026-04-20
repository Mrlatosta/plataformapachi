<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class MedicoPortalController extends Controller
{
    public function showLogin()
    {
        if (session('medico_id')) {
            return redirect()->route('medico.reportes');
        }

        return Inertia::render('Medico/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'usuario'  => 'required|string',
            'password' => 'required|string',
        ], [
            'usuario.required'  => 'El usuario es obligatorio',
            'password.required' => 'La contraseña es obligatoria',
        ]);

        $medico = Medico::where('usuario', $request->usuario)
            ->where('activo', true)
            ->first();

        if (!$medico || !Hash::check($request->password, $medico->password)) {
            return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos']);
        }

        session(['medico_id' => $medico->id, 'medico_nombre' => $medico->nombre]);

        return redirect()->route('medico.reportes');
    }

    public function logout()
    {
        session()->forget(['medico_id', 'medico_nombre']);

        return redirect()->route('medico.login');
    }

    public function reportes()
    {
        $medicoId = session('medico_id');

        $reportes = Reporte::with(['estudios.estudio'])
            ->where('medico_id', $medicoId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                return [
                    'id'              => $r->id,
                    'folio'           => $r->folio,
                    'nombre_cliente'  => $r->nombre_cliente,
                    'fecha_reporte'   => $r->fecha_reporte,
                    'toma_muestra'    => $r->toma_muestra,
                    'estudios'        => $r->estudios->map(fn($e) => $e->estudio->nombre ?? ''),
                ];
            });

        return Inertia::render('Medico/Reportes', [
            'reportes'      => $reportes,
            'medicoNombre'  => session('medico_nombre'),
        ]);
    }
}
