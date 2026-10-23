<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliclinicaCache extends Model
{
    protected $table = 'policlinica_cache';

    protected $fillable = [
        'evento_id',
        'paciente_nome',
        'paciente_cpf',
        'paciente_cns',
        'tipo',
        'procedimento',
        'telefone',
        'unidade',
        'profissional',
        'data_agendamento',
        'status',
        'sincronizado_em',
    ];

    protected function casts(): array
    {
        return [
            'data_agendamento' => 'date',
            'sincronizado_em' => 'datetime',
        ];
    }
}
