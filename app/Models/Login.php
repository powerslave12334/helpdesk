<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    protected $table = 'tblLogin';
    protected $primaryKey = 'id_Login';
    public $timestamps = false;
    protected $fillable = ['id_Usuario', 'usuario', 'pass', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_Usuario');
    }
}
