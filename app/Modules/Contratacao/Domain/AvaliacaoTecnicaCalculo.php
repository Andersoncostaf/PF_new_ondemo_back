<?php

namespace App\Modules\Contratacao\Domain;

/**
 * Índice ponderado 0–100% a partir de notas 0–10; qualificação mínima 70%.
 */
final class AvaliacaoTecnicaCalculo
{
    /**
     * @param array<string, float|null> $notasPorCriterio nota 0–10 por código de critério
     */
    public static function indicePercentual(array $notasPorCriterio): ?float
    {
        $total = 0.0;
        $pesoUsado = 0.0;

        foreach (AvaliacaoTecnicaCriterios::todos() as $criterio) {
            $codigo = $criterio['codigo'];
            if (! array_key_exists($codigo, $notasPorCriterio) || $notasPorCriterio[$codigo] === null) {
                continue;
            }

            $nota = (float) $notasPorCriterio[$codigo];
            if ($nota < 0 || $nota > 10) {
                throw new \InvalidArgumentException("Nota inválida para critério {$codigo}. Use valor entre 0 e 10.");
            }

            $peso = (float) $criterio['peso_percentual'];
            $total += ($nota / 10.0) * $peso;
            $pesoUsado += $peso;
        }

        if ($pesoUsado <= 0) {
            return null;
        }

        return round($total, 2);
    }

    public static function qualificada(?float $indicePercentual): bool
    {
        if ($indicePercentual === null) {
            return false;
        }

        return $indicePercentual >= AvaliacaoTecnicaCriterios::INDICE_MINIMO_PERCENTUAL;
    }
}
