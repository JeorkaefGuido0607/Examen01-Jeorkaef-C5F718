<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitaSalonModel extends Model {
    protected $table = 'citas_salon';
    public $timestamps = false;

    protected $fillable = [
        'nombre_cliente',
        'telefono_cliente',
        'servicio_solicitado',
        'estilista_asignado',
        'fecha_hora_cita',
        'precio_servicio',
        'estado'
    ];

    protected $casts = [
        'id' => 'integer',
        'precio_servicio' => 'float'
    ];
}
