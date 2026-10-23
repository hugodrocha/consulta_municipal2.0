<?php

namespace App\Http\Controllers;

use App\Services\ConsultaMunicipalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultaController extends Controller
{
    public function __construct(private ConsultaMunicipalService $consulta) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $dados = $q !== ''
            ? $this->consulta->consultar($q)
            : [
                'q' => '',
                'paciente' => null,
                'pec_atendimentos' => [],
                'pec_resumo' => [
                    'atendimentos' => 0,
                    'com_cid_ciap' => 0,
                    'itens_exame' => 0,
                    'consulta_no_dia' => 0,
                    'consulta_agendada' => 0,
                ],
                'pec_erro' => null,
                'poli_rows' => [],
                'poli_erro' => null,
                'poli_fonte' => '',
            ];

        return view('consulta.index', array_merge($dados, [
            'menu' => 'consulta',
        ]));
    }
}
