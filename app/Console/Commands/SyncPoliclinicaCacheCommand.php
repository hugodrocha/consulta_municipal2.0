<?php

namespace App\Console\Commands;

use App\Services\PoliclinicaConsultaService;
use Illuminate\Console\Command;

class SyncPoliclinicaCacheCommand extends Command
{
    protected $signature = 'consulta:sync-cache {--meses=12 : Janela em meses}';

    protected $description = 'Sincroniza o histórico das Policlínicas para o cache local';

    public function handle(PoliclinicaConsultaService $service): int
    {
        $meses = (int) $this->option('meses');
        $this->info("Sincronizando últimos {$meses} mês(es)…");

        try {
            $r = $service->sincronizarCache($meses, fn (string $msg) => $this->line($msg));
            $this->info("Concluído: {$r['linhas']} linha(s) em {$r['segundos']}s.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
