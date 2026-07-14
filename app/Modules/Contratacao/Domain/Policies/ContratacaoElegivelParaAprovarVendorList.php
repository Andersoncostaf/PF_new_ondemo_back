<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Models\ContratacaoAvaliacaoTecnica;
use App\Models\ContratacaoFornecedor;
use App\Modules\Contratacao\Domain\AvaliacaoTecnicaStatus;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use Illuminate\Support\Collection;

final class ContratacaoElegivelParaAprovarVendorList
{
    /**
     * @param  Collection<int, ContratacaoFornecedor>  $fornecedores
     */
    public static function check(
        Contratacao $contratacao,
        Collection $fornecedores,
        ?ContratacaoAvaliacaoTecnica $avaliacao = null,
    ): bool {
        if ($contratacao->status !== ContratacaoStatus::EM_VENDOR_LIST) {
            return false;
        }

        $vencedores = $fornecedores->filter(fn (ContratacaoFornecedor $f) => (bool) $f->vencedor);

        if ($vencedores->count() !== 1) {
            return false;
        }

        /** @var ContratacaoFornecedor $vencedor */
        $vencedor = $vencedores->first();
        $propostaFinal = $vencedor->proposta_final;

        if ($propostaFinal === null || (float) $propostaFinal <= 0) {
            return false;
        }

        if ($avaliacao !== null && ! AvaliacaoTecnicaStatus::permiteAprovarVendorList($avaliacao->status)) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, ContratacaoFornecedor>  $fornecedores
     */
    public static function motivoFalha(
        Contratacao $contratacao,
        Collection $fornecedores,
        ?ContratacaoAvaliacaoTecnica $avaliacao = null,
    ): string {
        if ($contratacao->status !== ContratacaoStatus::EM_VENDOR_LIST) {
            return 'Contratação não está em análise de fornecedores.';
        }

        $vencedores = $fornecedores->filter(fn (ContratacaoFornecedor $f) => (bool) $f->vencedor);

        if ($vencedores->count() !== 1) {
            return 'Para aprovar, deve haver exatamente 1 fornecedor vencedor selecionado. Atualmente há: '.$vencedores->count();
        }

        /** @var ContratacaoFornecedor $vencedor */
        $vencedor = $vencedores->first();
        if ($vencedor->proposta_final === null || (float) $vencedor->proposta_final <= 0) {
            return 'O fornecedor vencedor deve possuir proposta final preenchida com valor maior que zero.';
        }

        if ($avaliacao !== null && ! AvaliacaoTecnicaStatus::permiteAprovarVendorList($avaliacao->status)) {
            return 'A avaliação técnica deve estar concluída antes de aprovar a Vendor List.';
        }

        return 'Contratação não elegível para aprovação da Vendor List.';
    }
}
