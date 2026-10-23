<?php

namespace App\Services;

use App\Support\QueryClassifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class PecConsultaService
{
    public function conectado(): bool
    {
        return Cache::remember('status-conexao-pec', now()->addSeconds(20), function (): bool {
            try {
                DB::connection('pec_dw')->select('SELECT 1');

                return true;
            } catch (Throwable $e) {
                return false;
            }
        });
    }

    /**
     * @return array{
     *   paciente: ?array<string, mixed>,
     *   atendimentos: array<int, array<string, mixed>>,
     *   resumo: array<string, int>,
     *   erro: ?string
     * }
     */
    public function buscar(string $q): array
    {
        $vazio = [
            'paciente' => null,
            'atendimentos' => [],
            'resumo' => $this->resumoVazio(),
            'erro' => null,
        ];

        $q = trim($q);
        if ($q === '') {
            return $vazio;
        }

        try {
            $pdo = DB::connection('pec_dw')->getPdo();
        } catch (Throwable $e) {
            Log::warning('PEC DW indisponível: '.$e->getMessage());

            return array_merge($vazio, ['erro' => 'Não foi possível conectar à Base PEC.']);
        }

        $class = QueryClassifier::classify($q);
        $limite = (int) config('consulta.pec.historico_limite', 50);

        try {
            $rows = match ($class['type']) {
                'cpf' => $this->consultar($pdo, 'cid.nu_cpf_cidadao = :doc', [':doc' => $class['value']], $limite),
                'cns' => $this->consultar($pdo, 'cid.nu_cns = :doc', [':doc' => $class['value']], $limite),
                default => $this->consultar(
                    $pdo,
                    'upper(cid.no_cidadao) LIKE :nome',
                    [':nome' => '%'.$class['value'].'%'],
                    $limite
                ),
            };
        } catch (Throwable $e) {
            Log::error('Erro na consulta PEC: '.$e->getMessage());

            return array_merge($vazio, ['erro' => 'Erro ao consultar a Base PEC: '.$e->getMessage()]);
        }

        if ($rows === []) {
            return $vazio;
        }

        $rows = $this->enriquecerDescricoes($pdo, $rows);

        $primeiro = $rows[0];
        $paciente = [
            'nome' => trim((string) ($primeiro['nome_cidadao'] ?? '')),
            'nome_mae' => trim((string) ($primeiro['nome_mae'] ?? '')),
            'telefone' => trim((string) ($primeiro['telefone_cidadao'] ?? '')),
            'nascimento' => QueryClassifier::formatDateBr(isset($primeiro['dt_nascimento_cidadao']) ? (string) $primeiro['dt_nascimento_cidadao'] : null),
            'cpf' => QueryClassifier::formatCpf(isset($primeiro['nu_cpf_cidadao']) ? (string) $primeiro['nu_cpf_cidadao'] : null),
            'cns' => trim((string) ($primeiro['nu_cns'] ?? '')),
            'endereco' => trim((string) ($primeiro['endereco_cidadao'] ?? '')),
        ];

        $atendimentos = [];
        foreach ($rows as $row) {
            $atendimentos[] = [
                'data' => QueryClassifier::formatDateTimeBr(isset($row['dt_inicial_atendimento']) ? (string) $row['dt_inicial_atendimento'] : null),
                'especialidade' => trim((string) ($row['tipo_atendimento'] ?? '')) ?: 'Atenção básica',
                'profissional' => trim((string) ($row['profissional_nome'] ?? '')),
                'unidade' => trim((string) ($row['unidade_nome'] ?? '')),
                'cid_ciap' => $this->juntar([
                    (string) ($row['ds_cids_descricao'] ?? $row['ds_filtro_cids'] ?? ''),
                    (string) ($row['ds_ciaps_descricao'] ?? $row['ds_filtro_ciaps'] ?? ''),
                ], ' / '),
                'exames' => $this->juntar([
                    (string) ($row['ds_proced_avaliados_descricao'] ?? $row['ds_filtro_proced_avaliados'] ?? ''),
                    (string) ($row['ds_proced_solicitados_descricao'] ?? $row['ds_filtro_proced_solicitados'] ?? ''),
                ], '; '),
                'qtd_exames' => $this->contarItens((string) ($row['ds_filtro_proced_avaliados'] ?? ''))
                    + $this->contarItens((string) ($row['ds_filtro_proced_solicitados'] ?? '')),
                'tipo_raw' => trim((string) ($row['tipo_atendimento'] ?? '')),
            ];
        }

        return [
            'paciente' => $paciente,
            'atendimentos' => $atendimentos,
            'resumo' => $this->montarResumo($atendimentos),
            'erro' => null,
        ];
    }

    /**
     * @param  array<string, string>  $params
     * @return array<int, array<string, mixed>>
     */
    private function consultar(PDO $pdo, string $where, array $params, int $limite): array
    {
        $sql = <<<SQL
            SELECT
                cid.nu_cpf_cidadao,
                cid.nu_cns,
                cid.no_cidadao AS nome_cidadao,
                nasc.dt_registro AS dt_nascimento_cidadao,
                COALESCE(c.no_mae, '') AS nome_mae,
                COALESCE(cid.nu_telefone_celular, '') AS telefone_cidadao,
                trim(both ' ' from concat_ws(', ',
                    nullif(c.ds_logradouro, ''),
                    nullif(c.nu_numero, ''),
                    nullif(c.no_bairro, ''),
                    nullif(c.ds_cep, '')
                )) AS endereco_cidadao,
                ai.dt_inicial_atendimento,
                ai.ds_filtro_cids,
                ai.ds_filtro_ciaps,
                ai.ds_filtro_proced_avaliados,
                ai.ds_filtro_proced_solicitados,
                uns.no_unidade_saude AS unidade_nome,
                prof.no_profissional AS profissional_nome,
                COALESCE(ta.ds_filtro, ta.ds_tipo_atendimento, '') AS tipo_atendimento
            FROM tb_fat_atendimento_individual ai
            INNER JOIN tb_fat_cidadao_pec cid
                ON cid.co_seq_fat_cidadao_pec = ai.co_fat_cidadao_pec
            LEFT JOIN tb_dim_tempo nasc
                ON nasc.co_seq_dim_tempo = cid.co_dim_tempo_nascimento
            LEFT JOIN tb_cidadao c
                ON c.co_seq_cidadao = cid.co_cidadao
            LEFT JOIN tb_dim_unidade_saude uns
                ON uns.co_seq_dim_unidade_saude = ai.co_dim_unidade_saude_1
            LEFT JOIN tb_dim_profissional prof
                ON prof.co_seq_dim_profissional = ai.co_dim_profissional_1
            LEFT JOIN tb_dim_tipo_atendimento ta
                ON ta.co_seq_dim_tipo_atendimento = ai.co_dim_tipo_atendimento
            WHERE {$where}
            ORDER BY ai.dt_inicial_atendimento DESC NULLS LAST
            LIMIT {$limite}
        SQL;

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function juntar(array $parts, string $sep): string
    {
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return $parts === [] ? '' : implode($sep, $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function enriquecerDescricoes(PDO $pdo, array $rows): array
    {
        $dimensoes = [
            'ds_filtro_cids' => ['tb_dim_cid', 'nu_cid', 'no_cid', 'ds_cids_descricao'],
            'ds_filtro_ciaps' => ['tb_dim_ciap', 'nu_ciap', 'no_ciap', 'ds_ciaps_descricao'],
            'ds_filtro_proced_avaliados' => ['tb_dim_procedimento', 'co_proced', 'ds_proced', 'ds_proced_avaliados_descricao'],
            'ds_filtro_proced_solicitados' => ['tb_dim_procedimento', 'co_proced', 'ds_proced', 'ds_proced_solicitados_descricao'],
        ];
        $codigos = [];

        foreach ($dimensoes as $campo => [$tabela, $colunaCodigo, $colunaDescricao, $campoDescricao]) {
            foreach ($rows as $row) {
                foreach ($this->extrairCodigos((string) ($row[$campo] ?? '')) as $codigo) {
                    $codigos[$tabela][$colunaCodigo][$colunaDescricao][$codigo] = true;
                }
            }
        }

        $mapas = [];
        try {
            foreach ($codigos as $tabela => $colunas) {
                foreach ($colunas as $colunaCodigo => $descricoes) {
                    foreach ($descricoes as $colunaDescricao => $lista) {
                        $valores = array_keys($lista);
                        if ($valores === []) {
                            continue;
                        }

                        $in = implode(',', array_map(fn (string $codigo): string => $pdo->quote($codigo), $valores));
                        $sql = "SELECT UPPER({$colunaCodigo}) AS codigo, {$colunaDescricao} AS descricao, ds_filtro FROM {$tabela} WHERE UPPER({$colunaCodigo}) IN ({$in})";
                        foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $registro) {
                            $descricao = trim((string) ($registro['descricao'] ?? ''));
                            if ($descricao === '') {
                                $descricao = trim((string) ($registro['ds_filtro'] ?? ''));
                            }
                            if ($descricao !== '') {
                                $mapas[$tabela][$colunaCodigo][$colunaDescricao][strtoupper((string) $registro['codigo'])] = $descricao;
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Não foi possível traduzir códigos clínicos do PEC: '.$e->getMessage());

            return $rows;
        }

        foreach ($rows as &$row) {
            foreach ($dimensoes as $campo => [$tabela, $colunaCodigo, $colunaDescricao, $campoDescricao]) {
                $valor = trim((string) ($row[$campo] ?? ''));
                $codigosDoCampo = $this->extrairCodigos($valor);
                if ($codigosDoCampo === []) {
                    $row[$campoDescricao] = $valor;
                    continue;
                }

                $labels = [];
                foreach ($codigosDoCampo as $codigo) {
                    $label = $mapas[$tabela][$colunaCodigo][$colunaDescricao][$codigo] ?? $codigo;
                    $labels[$label] = $label;
                }
                $row[$campoDescricao] = implode('; ', array_values($labels));
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function extrairCodigos(string $valor): array
    {
        if (trim($valor) === '') {
            return [];
        }

        $partes = preg_split('/[|,;]+/u', $valor, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $codigos = [];
        foreach ($partes as $parte) {
            $codigo = strtoupper(trim($parte));
            if ($codigo !== '' && preg_match('/^[A-Z0-9.\/-]{3,}$/', $codigo) === 1) {
                $codigos[$codigo] = $codigo;
            }
        }

        return array_values($codigos);
    }

    private function contarItens(string $s): int
    {
        if (trim($s) === '') {
            return 0;
        }
        $parts = preg_split('/[|,;]+/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count(array_filter(array_map('trim', $parts)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $atendimentos
     * @return array<string, int>
     */
    private function montarResumo(array $atendimentos): array
    {
        $comCid = 0;
        $exames = 0;
        $noDia = 0;
        $agendada = 0;

        foreach ($atendimentos as $a) {
            if (trim((string) ($a['cid_ciap'] ?? '')) !== '') {
                $comCid++;
            }
            $exames += (int) ($a['qtd_exames'] ?? 0);
            $tipo = mb_strtolower((string) ($a['tipo_raw'] ?? ''), 'UTF-8');
            if (str_contains($tipo, 'espont') || str_contains($tipo, 'no dia') || str_contains($tipo, 'demanda')) {
                $noDia++;
            }
            if (str_contains($tipo, 'agend') || str_contains($tipo, 'marcada') || str_contains($tipo, 'program')) {
                $agendada++;
            }
        }

        return [
            'atendimentos' => count($atendimentos),
            'com_cid_ciap' => $comCid,
            'itens_exame' => $exames,
            'consulta_no_dia' => $noDia,
            'consulta_agendada' => $agendada,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function resumoVazio(): array
    {
        return [
            'atendimentos' => 0,
            'com_cid_ciap' => 0,
            'itens_exame' => 0,
            'consulta_no_dia' => 0,
            'consulta_agendada' => 0,
        ];
    }
}
