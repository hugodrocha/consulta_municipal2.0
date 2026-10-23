<?php

namespace App\Services;

use App\Models\PoliclinicaCache;
use App\Support\QueryClassifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class PoliclinicaConsultaService
{
    /**
     * @return array{rows: array<int, array<string, string>>, erro: ?string, fonte: string}
     */
    public function buscar(string $q, ?array $pacientePec = null): array
    {
        $q = trim($q);
        if ($q === '') {
            return ['rows' => [], 'erro' => null, 'fonte' => 'cache'];
        }

        $class = QueryClassifier::classify($q);
        $cpf = $class['type'] === 'cpf' ? $class['value'] : QueryClassifier::digits((string) ($pacientePec['cpf'] ?? ''));
        $cns = $class['type'] === 'cns' ? $class['value'] : QueryClassifier::digits((string) ($pacientePec['cns'] ?? ''));
        $nome = $class['type'] === 'nome' ? $class['value'] : mb_strtoupper((string) ($pacientePec['nome'] ?? ''), 'UTF-8');

        if (strlen($cpf) !== 11) {
            $cpf = '';
        }
        if (strlen($cns) !== 15) {
            $cns = '';
        }

        $cacheRows = $this->buscarNoCache($cpf, $cns, $nome);
        if ($cacheRows !== []) {
            return ['rows' => $cacheRows, 'erro' => null, 'fonte' => 'cache'];
        }

        try {
            if ($cpf === '' && $cns === '' && $nome === '') {
                return ['rows' => [], 'erro' => null, 'fonte' => 'vazio'];
            }
            $carregadas = $this->carregarPacienteNoCache($cpf, $cns, $nome);
            if ($carregadas > 0) {
                return [
                    'rows' => $this->buscarNoCache($cpf, $cns, $nome),
                    'erro' => null,
                    'fonte' => 'mysql',
                ];
            }
        } catch (Throwable $e) {
            Log::warning('Policlínica MySQL: '.$e->getMessage());

            return ['rows' => [], 'erro' => 'Não foi possível consultar as Policlínicas agora. Atualize o cache.', 'fonte' => 'erro'];
        }

        return ['rows' => [], 'erro' => null, 'fonte' => 'vazio'];
    }

    public function conectado(): bool
    {
        return Cache::remember('status-conexao-esus', now()->addSeconds(20), function (): bool {
            return $this->mysqlPdo() !== null;
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buscarNoCache(string $cpf, string $cns, string $nome): array
    {
        $query = PoliclinicaCache::query()->orderByDesc('data_agendamento');

        if ($cpf !== '') {
            $query->where('paciente_cpf', $cpf);
        } elseif ($cns !== '') {
            $query->where('paciente_cns', $cns);
        } elseif ($nome !== '') {
            $query->where('paciente_nome', 'like', '%'.$nome.'%');
        } else {
            return [];
        }

        return $query->limit(200)->get()->map(function (PoliclinicaCache $r) {
            return [
                'tipo' => strtoupper((string) $r->tipo),
                'procedimento' => (string) ($r->procedimento ?: '—'),
                'paciente' => (string) ($r->paciente_nome ?: '—'),
                'telefone' => (string) ($r->telefone ?: '—'),
                'unidade' => (string) ($r->unidade ?: '—'),
                'profissional' => (string) ($r->profissional ?: '—'),
                'data_inclusao' => '—',
                'data_agenda' => $r->data_agendamento
                    ? $r->data_agendamento->format('d/m/Y')
                    : '—',
                'situacao' => '—',
                'status' => $this->statusLabel((string) $r->status),
            ];
        })->all();
    }

    public function carregarPacienteNoCache(string $cpf, string $cns, string $nome = ''): int
    {
        if (strlen($cpf) !== 11 && strlen($cns) !== 15 && trim($nome) === '') {
            return 0;
        }

        $pdo = $this->mysqlPdo();
        if ($pdo === null) {
            return 0;
        }

        $view = config('consulta.policlinica.view', 'vw_paciente_historico_agendamentos');
        $where = [];
        $params = [];
        if (strlen($cpf) === 11) {
            $where[] = 'paciente_cpf = :cpf';
            $params[':cpf'] = $cpf;
        }
        if (strlen($cns) === 15) {
            $where[] = 'paciente_cns = :cns';
            $params[':cns'] = $cns;
        }
        if ($where === [] && trim($nome) !== '') {
            $where[] = 'paciente_nome LIKE :nome';
            $params[':nome'] = '%'.trim($nome).'%';
        }

        $sql = 'SELECT evento_id, paciente_nome, paciente_cpf, paciente_cns, tipo,
                       procedimento_descricao, unidade_saude_nome, profissional_nome,
                       data_agendamento, status
                FROM `'.$view.'`
                WHERE ('.implode(' OR ', $where).')
                ORDER BY data_agendamento DESC
                LIMIT '.($where[0] === 'paciente_nome LIKE :nome' ? '100' : '500');

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $this->gravarLinhas($rows);
    }

    /**
     * Sincroniza o histórico MySQL para o cache local (janela em meses).
     *
     * @param  callable(string):void|null  $onProgress
     * @return array{linhas: int, meses: int, segundos: float}
     */
    public function sincronizarCache(int $meses, ?callable $onProgress = null): array
    {
        $meses = max(1, min(120, $meses));
        $inicio = microtime(true);
        $sync = \App\Models\PoliclinicaCacheSync::query()->create([
            'janela_meses' => $meses,
            'iniciado_em' => now(),
        ]);

        $emit = static function (string $msg) use ($onProgress): void {
            if ($onProgress) {
                $onProgress($msg);
            }
        };

        try {
            $pdo = $this->mysqlPdo();
            if ($pdo === null) {
                throw new \RuntimeException('MySQL Policlínicas indisponível.');
            }

            $view = config('consulta.policlinica.view', 'vw_paciente_historico_agendamentos');
            $chunk = 2000;
            $offset = 0;
            $total = 0;
            $marcador = now();

            $sqlBase = 'SELECT evento_id, paciente_nome, paciente_cpf, paciente_cns, tipo,
                               procedimento_descricao, unidade_saude_nome, profissional_nome,
                               data_agendamento, status
                        FROM `'.$view.'`
                        WHERE data_agendamento >= DATE_SUB(CURDATE(), INTERVAL '.(int) $meses.' MONTH)
                        ORDER BY evento_id ASC';

            while (true) {
                $emit("Buscando bloco offset={$offset}…");
                $sql = $sqlBase.' LIMIT '.(int) $chunk.' OFFSET '.(int) $offset;
                $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $emit('  → '.count($rows).' linha(s)');
                if ($rows === []) {
                    break;
                }
                $total += $this->gravarLinhas($rows, $marcador);
                if (count($rows) < $chunk) {
                    break;
                }
                $offset += $chunk;
            }

            PoliclinicaCache::query()
                ->where(function ($q) use ($marcador) {
                    $q->whereNull('sincronizado_em')
                        ->orWhere('sincronizado_em', '<', $marcador);
                })
                ->delete();

            $sync->update([
                'linhas' => $total,
                'sucesso' => true,
                'terminado_em' => now(),
                'mensagem' => 'OK',
            ]);

            return [
                'linhas' => $total,
                'meses' => $meses,
                'segundos' => round(microtime(true) - $inicio, 1),
            ];
        } catch (Throwable $e) {
            $sync->update([
                'sucesso' => false,
                'terminado_em' => now(),
                'mensagem' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function gravarLinhas(array $rows, mixed $marcador = null): int
    {
        $marcador = $marcador ?? now();
        $payloads = [];

        foreach ($rows as $r) {
            $cpf = QueryClassifier::digits((string) ($r['paciente_cpf'] ?? ''));
            $cns = QueryClassifier::digits((string) ($r['paciente_cns'] ?? ''));
            $eventoId = isset($r['evento_id']) && $r['evento_id'] !== '' && $r['evento_id'] !== null
                ? (int) $r['evento_id']
                : null;

            $payloads[] = array_merge(['evento_id' => $eventoId], [
                'paciente_nome' => trim((string) ($r['paciente_nome'] ?? '')) ?: null,
                'paciente_cpf' => strlen($cpf) === 11 ? $cpf : null,
                'paciente_cns' => strlen($cns) === 15 ? $cns : null,
                'tipo' => trim((string) ($r['tipo'] ?? '')) ?: null,
                'procedimento' => trim((string) ($r['procedimento_descricao'] ?? '')) ?: null,
                'unidade' => trim((string) ($r['unidade_saude_nome'] ?? '')) ?: null,
                'profissional' => trim((string) ($r['profissional_nome'] ?? '')) ?: null,
                'data_agendamento' => ! empty($r['data_agendamento']) ? (string) $r['data_agendamento'] : null,
                'status' => trim((string) ($r['status'] ?? '')) ?: null,
                'sincronizado_em' => $marcador,
            ]);
        }

        foreach (array_chunk($payloads, 60) as $lote) {
            PoliclinicaCache::query()->upsert($lote, ['evento_id'], [
                'paciente_nome',
                'paciente_cpf',
                'paciente_cns',
                'tipo',
                'procedimento',
                'unidade',
                'profissional',
                'data_agendamento',
                'status',
                'sincronizado_em',
            ]);
        }

        return count($payloads);
    }

    private function mysqlPdo(): ?PDO
    {
        $cfg = config('consulta.policlinica');
        $timeout = (int) ($cfg['connect_timeout'] ?? 5);
        $candidates = [
            [
                'host' => $cfg['host'],
                'port' => (int) $cfg['port'],
                'db' => $cfg['database'],
                'user' => $cfg['username'],
                'pass' => $cfg['password'],
            ],
        ];

        // Fallback externo só se o host interno falhar — timeout curto.
        if (! empty($cfg['fallback_host'])) {
            $candidates[] = [
                'host' => $cfg['fallback_host'],
                'port' => (int) $cfg['port'],
                'db' => $cfg['database'],
                'user' => $cfg['fallback_username'] ?: $cfg['username'],
                'pass' => $cfg['fallback_password'] ?: $cfg['password'],
            ];
        }

        foreach ($candidates as $c) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $c['host'],
                    $c['port'],
                    $c['db']
                );
                $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => $timeout,
                ]);
                $pdo->query('SELECT 1');

                return $pdo;
            } catch (Throwable $e) {
                Log::info('MySQL poli tentativa falhou ('.$c['host'].'): '.$e->getMessage());
                continue;
            }
        }

        return null;
    }

    private function statusLabel(string $status): string
    {
        $s = mb_strtoupper(trim($status), 'UTF-8');
        $s = strtr($s, ['Á' => 'A', 'Ã' => 'A', 'Â' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C']);

        return match (true) {
            $s === '' => '—',
            str_contains($s, 'NAO EFETIV') || str_contains($s, 'NÃO EFETIV') => 'Não efetivado',
            str_contains($s, 'EFETIV') => 'Efetivado',
            str_contains($s, 'AGEND') => 'Agendado',
            str_contains($s, 'CANCEL') => 'Cancelado',
            default => ucfirst(mb_strtolower($status, 'UTF-8')),
        };
    }
}
