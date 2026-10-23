<?php

namespace App\Services;

use App\Support\QueryClassifier;

class ConsultaMunicipalService
{
    public function __construct(
        private PecConsultaService $pec,
        private PoliclinicaConsultaService $poli,
    ) {}

    /**
     * @return array{pec: bool, esus: bool}
     */
    public function statusConexoes(): array
    {
        return [
            'pec' => $this->pec->conectado(),
            'esus' => $this->poli->conectado(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function consultar(string $q): array
    {
        $q = trim($q);
        $pec = $this->pec->buscar($q);

        $pacienteParaPoli = null;
        if (is_array($pec['paciente'])) {
            $pacienteParaPoli = [
                'nome' => $pec['paciente']['nome'] ?? '',
                'cpf' => preg_replace('/\D+/', '', (string) ($pec['paciente']['cpf'] ?? '')) ?? '',
                'cns' => preg_replace('/\D+/', '', (string) ($pec['paciente']['cns'] ?? '')) ?? '',
            ];
        } elseif (! in_array(QueryClassifier::classify($q)['type'], ['cpf', 'cns'], true)) {
            $pacienteParaPoli = ['nome' => $q, 'cpf' => '', 'cns' => ''];
        }

        $poli = $this->poli->buscar($q, $pacienteParaPoli);

        // Se PEC não trouxe paciente mas Policlínica tem, monta identificação mínima.
        $paciente = $pec['paciente'];
        if ($paciente === null && ! empty($poli['rows'][0])) {
            $r = $poli['rows'][0];
            $paciente = [
                'nome' => $r['paciente'] !== '—' ? $r['paciente'] : '',
                'nome_mae' => '',
                'telefone' => $r['telefone'] !== '—' ? $r['telefone'] : '',
                'nascimento' => '',
                'cpf' => '',
                'cns' => '',
                'endereco' => '',
            ];
        }

        return [
            'q' => $q,
            'paciente' => $paciente,
            'pec_atendimentos' => $pec['atendimentos'],
            'pec_resumo' => $pec['resumo'],
            'pec_erro' => $pec['erro'],
            'poli_rows' => $poli['rows'],
            'poli_erro' => $poli['erro'],
            'poli_fonte' => $poli['fonte'],
        ];
    }
}
