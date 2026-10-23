<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliclinicaCacheSync extends Model
{
    public $timestamps = false;

    protected $table = 'policlinica_cache_syncs';

    protected $fillable = [
        'janela_meses',
        'linhas',
        'sucesso',
        'mensagem',
        'iniciado_em',
        'terminado_em',
    ];

    protected function casts(): array
    {
        return [
            'sucesso' => 'boolean',
            'iniciado_em' => 'datetime',
            'terminado_em' => 'datetime',
        ];
    }
}
