<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sistema extends Model
{
    protected $table = 'tblSistemas';
    protected $primaryKey = 'id_sistemas';
    public $timestamps = false;
    protected $fillable = ['nombre', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];
}
