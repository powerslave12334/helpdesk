<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'tblPeriodoMantenimiento';
    protected $primaryKey = 'id_PeriodoMantenimiento';
    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'meses',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
