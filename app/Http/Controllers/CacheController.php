<?php

namespace App\Http\Controllers;

use App\Models\PoliclinicaCacheSync;
use App\Services\PoliclinicaConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CacheController extends Controller
{
    public function __construct(private PoliclinicaConsultaService $poli) {}

    public function index(): View
    {
        $ultima = PoliclinicaCacheSync::query()
            ->orderByDesc('id')
            ->first();

        return view('cache.index', [
            'menu' => 'cache',
            'ultima' => $ultima,
            'opcoes' => config('consulta.cache.meses_opcoes', [3, 6, 12, 24, 36, 60, 120]),
            'padrao' => (int) config('consulta.cache.meses_padrao', 12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'meses' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        try {
            $r = $this->poli->sincronizarCache((int) $dados['meses']);

            return redirect()->route('cache.index')->with(
                'sucesso',
                'Cache atualizado: '.number_format($r['linhas'], 0, ',', '.').' linha(s) em '.$r['segundos'].'s (últimos '.$r['meses'].' mês(es)).'
            );
        } catch (Throwable $e) {
            return redirect()->route('cache.index')->with(
                'erro',
                'Falha ao atualizar o cache: '.$e->getMessage()
            );
        }
    }
}
