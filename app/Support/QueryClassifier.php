<?php

namespace App\Support;

class QueryClassifier
{
    public static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * @return array{type: string, value: string}
     */
    public static function classify(string $q): array
    {
        $q = trim($q);
        $digits = self::digits($q);

        if (strlen($digits) === 11) {
            return ['type' => 'cpf', 'value' => $digits];
        }

        if (strlen($digits) === 15) {
            return ['type' => 'cns', 'value' => $digits];
        }

        return ['type' => 'nome', 'value' => mb_strtoupper($q, 'UTF-8')];
    }

    public static function formatCpf(?string $cpf): string
    {
        $d = self::digits((string) $cpf);
        if (strlen($d) !== 11) {
            return $cpf ? trim($cpf) : '';
        }

        return substr($d, 0, 3).'.'.substr($d, 3, 3).'.'.substr($d, 6, 3).'-'.substr($d, 9, 2);
    }

    public static function formatDateBr(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        return date('d/m/Y', $ts);
    }

    public static function formatDateTimeBr(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        return date('d/m/Y H:i', $ts);
    }
}
