<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dominios_Contenido_DetallesModel extends Model
{
    use HasFactory;

    protected $table = 'dominios_contenido_detalles';

    protected $primaryKey = 'id_dominio_contenido_detalle';

    public $incrementing = true;     // ✅ autoincrement
    protected $keyType = 'int';      // ✅
protected $casts = ['scheduled_at' => 'datetime'];

    protected $fillable = [
        'id_dominio_contenido',
        'id_dominio',

        'tipo',
        'keyword',
        'enfoque',

        'title',
        'slug',
        'contenido_html',

        'meta_title',
        'meta_description',

        'wp_post_id',
        'wp_url',

        'estatus',
        'error',

        'draft_html',
        'modelo',
        'wp_id',
        'wp_link',
        'job_uuid'
      
        
    ];
}