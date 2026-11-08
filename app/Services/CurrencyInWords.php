<?php

namespace App\Services;

class CurrencyInWords
{
    private static array $unidades = [
        1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro', 5 => 'cinco',
        6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove', 10 => 'dez',
        11 => 'onze', 12 => 'doze', 13 => 'treze', 14 => 'quatorze', 15 => 'quinze',
        16 => 'dezesseis', 17 => 'dezessete', 18 => 'dezoito', 19 => 'dezenove',
    ];

    private static array $dezenas = [
        2 => 'vinte', 3 => 'trinta', 4 => 'quarenta', 5 => 'cinquenta',
        6 => 'sessenta', 7 => 'setenta', 8 => 'oitenta', 9 => 'noventa',
    ];

    private static array $centenas = [
        1 => 'cento', 2 => 'duzentos', 3 => 'trezentos', 4 => 'quatrocentos',
        5 => 'quinhentos', 6 => 'seiscentos', 7 => 'setecentos', 8 => 'oitocentos',
        9 => 'novecentos',
    ];

    public static function toWords(float $amount): string
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return 'zero reais';
        }

        $inteiro = (int) floor($amount);
        $centavos = (int) round(($amount - $inteiro) * 100);

        $partes = [];

        if ($inteiro > 0) {
            $partes[] = self::inteiroPorExtenso($inteiro).($inteiro === 1 ? ' real' : ' reais');
        }

        if ($centavos > 0) {
            $partes[] = self::converteGrupo($centavos).($centavos === 1 ? ' centavo' : ' centavos');
        }

        return implode(' e ', $partes);
    }

    private static function inteiroPorExtenso(int $valor): string
    {
        if ($valor === 100) {
            return 'cem';
        }

        if ($valor >= 1_000_000) {
            $milhoes = (int) floor($valor / 1_000_000);
            $resto = $valor % 1_000_000;
            $sufixo = $milhoes === 1 ? 'um milhão' : self::converteGrupo($milhoes).' milhões';

            if ($resto === 0) {
                return $sufixo;
            }

            return $sufixo.($resto < 100 ? ' e ' : ' ').self::inteiroPorExtenso($resto);
        }

        if ($valor >= 1000) {
            $milhar = (int) floor($valor / 1000);
            $resto = $valor % 1000;
            $sufixo = $milhar === 1 ? 'mil' : self::converteGrupo($milhar).' mil';

            if ($resto === 0) {
                return $sufixo;
            }

            return $sufixo.($resto < 100 || $resto % 100 === 0 ? ' e ' : ' ').self::inteiroPorExtenso($resto);
        }

        return self::converteGrupo($valor);
    }

    private static function converteGrupo(int $n): string
    {
        if ($n === 0) {
            return '';
        }

        if ($n === 100) {
            return 'cem';
        }

        $resultado = [];

        $c = (int) floor($n / 100);
        $restoCentena = $n % 100;

        if ($c > 0) {
            $resultado[] = self::$centenas[$c];
        }

        if ($restoCentena > 0) {
            if ($restoCentena < 20) {
                $resultado[] = self::$unidades[$restoCentena];
            } else {
                $d = (int) floor($restoCentena / 10);
                $u = $restoCentena % 10;
                $resDezena = self::$dezenas[$d];
                if ($u > 0) {
                    $resDezena .= ' e '.self::$unidades[$u];
                }
                $resultado[] = $resDezena;
            }
        }

        return implode(' e ', $resultado);
    }
}
