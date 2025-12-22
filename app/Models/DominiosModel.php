<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class DominiosModel extends Model
{
    use HasFactory;
    protected $table = 'dominios';
    protected $primaryKey = 'id_dominio';

    protected $fillable = [
        'id_dominio',
        'nombre',
    	'url',
    	'estatus',
        'usuario',
        'password',
        'elementor_template_path'
    

    ];

    protected $dateFormat = 'Y-m-d H:i:s'; //funcion para formateo de la fecha
}
