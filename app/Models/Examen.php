<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Estudio;
use App\Models\Examen;
use App\Models\ResultadoExamen;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = ['estudio_id', 'seccion', 'orden', 'nombre_examen', 'unidad', 'valor_referencia'];

    public function estudio()
    {
        return $this->belongsTo(Estudio::class);
    }

public function resultados()
{
    return $this->hasMany(ResultadoExamen::class);
}


}

