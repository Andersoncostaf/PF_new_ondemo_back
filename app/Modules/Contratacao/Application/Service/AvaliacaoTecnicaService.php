<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoAvaliacaoTecnica;
use App\Models\ContratacaoAvaliacaoTecnicaItem;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\AvaliacaoTecnicaCalculo;
use App\Modules\Contratacao\Domain\AvaliacaoTecnicaCriterios;
use App\Modules\Contratacao\Domain\AvaliacaoTecnicaStatus;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AvaliacaoTecnicaService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function obter(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadVendorList($usuario, $uuid);
        $avaliacao = $this->obterOuCriarRascunho($contratacao, $usuario->tenant_id);

        return $this->serializar($avaliacao);
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function salvarNotas(UsuarioCliente $usuario, string $uuid, array $dados): array
    {
        $contratacao = $this->loadVendorListEditavel($usuario, $uuid);
        $avaliacao = $this->obterOuCriarRascunho($contratacao, $usuario->tenant_id);

        if (! AvaliacaoTecnicaStatus::comprasPodeEditar($avaliacao->status)) {
            throw new ContratacaoTransicaoInvalidaException('Avaliação técnica não pode ser editada no status atual.');
        }

        /** @var array<int, array<string, mixed>> $itensInput */
        $itensInput = $dados['itens'] ?? [];
        $notasPorCodigo = [];

        DB::transaction(function () use ($avaliacao, $itensInput, $dados, $contratacao, &$notasPorCodigo): void {
            foreach ($itensInput as $itemInput) {
                $codigo = (string) ($itemInput['codigo'] ?? '');
                if (! in_array($codigo, AvaliacaoTecnicaCriterios::codigosValidos(), true)) {
                    continue;
                }

                $item = $avaliacao->itens()->where('codigo', $codigo)->first();
                if ($item === null) {
                    continue;
                }

                if (array_key_exists('nota', $itemInput)) {
                    $nota = $itemInput['nota'];
                    if ($nota !== null && ((float) $nota < 0 || (float) $nota > 10)) {
                        throw new ContratacaoTransicaoInvalidaException("Nota inválida para o critério {$codigo}. Use valor entre 0 e 10.");
                    }
                    $item->nota = $nota;
                    $notasPorCodigo[$codigo] = $nota !== null ? (float) $nota : null;
                } else {
                    $notasPorCodigo[$codigo] = $item->nota !== null ? (float) $item->nota : null;
                }

                if (array_key_exists('observacao', $itemInput)) {
                    $item->observacao = $itemInput['observacao'];
                }

                $item->save();
            }

            foreach ($avaliacao->itens as $item) {
                if (! array_key_exists($item->codigo, $notasPorCodigo)) {
                    $notasPorCodigo[$item->codigo] = $item->nota !== null ? (float) $item->nota : null;
                }
            }

            if (array_key_exists('observacao', $dados)) {
                $avaliacao->observacao = $dados['observacao'];
            }

            if (array_key_exists('fornecedor_vencedor_uuid', $dados) && $dados['fornecedor_vencedor_uuid']) {
                $fornecedor = $contratacao->fornecedores()
                    ->where(function ($q) use ($dados) {
                        $q->where('uuid', $dados['fornecedor_vencedor_uuid'])
                            ->orWhere('id', $dados['fornecedor_vencedor_uuid']);
                    })
                    ->first();

                if ($fornecedor === null) {
                    throw new ContratacaoNaoEncontradaException;
                }

                $avaliacao->fornecedor_vencedor_id = $fornecedor->id;
            }

            $avaliacao->indice_percentual = AvaliacaoTecnicaCalculo::indicePercentual($notasPorCodigo);
            $avaliacao->save();
        });

        return $this->serializar($avaliacao->fresh(['itens', 'fornecedorVencedor']) ?? $avaliacao);
    }

    /**
     * @return array<string, mixed>
     */
    public function concluir(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadVendorListEditavel($usuario, $uuid);
        $avaliacao = $this->obterOuCriarRascunho($contratacao, $usuario->tenant_id);

        if (AvaliacaoTecnicaStatus::normalizar($avaliacao->status) === AvaliacaoTecnicaStatus::CONCLUIDA) {
            return $this->serializar($avaliacao);
        }

        $notasPorCodigo = [];
        foreach ($avaliacao->itens as $item) {
            if ($item->nota === null) {
                throw new ContratacaoTransicaoInvalidaException('Preencha todas as notas dos critérios antes de concluir a avaliação técnica.');
            }
            $notasPorCodigo[$item->codigo] = (float) $item->nota;
        }

        $indice = AvaliacaoTecnicaCalculo::indicePercentual($notasPorCodigo);
        if ($indice === null || $indice < AvaliacaoTecnicaCriterios::INDICE_MINIMO_PERCENTUAL) {
            throw new ContratacaoTransicaoInvalidaException(
                'Índice ponderado mínimo de '.AvaliacaoTecnicaCriterios::INDICE_MINIMO_PERCENTUAL.'% não atingido para concluir a avaliação técnica.'
            );
        }

        $avaliacao->indice_percentual = $indice;
        $avaliacao->status = AvaliacaoTecnicaStatus::CONCLUIDA;
        $avaliacao->save();

        return $this->serializar($avaliacao->fresh(['itens', 'fornecedorVencedor']) ?? $avaliacao);
    }

    private function obterOuCriarRascunho(Contratacao $contratacao, string $tenantId): ContratacaoAvaliacaoTecnica
    {
        $avaliacao = ContratacaoAvaliacaoTecnica::query()
            ->where('contratacao_id', $contratacao->id)
            ->with(['itens', 'fornecedorVencedor'])
            ->first();

        if ($avaliacao !== null) {
            return $avaliacao;
        }

        return DB::transaction(function () use ($contratacao, $tenantId) {
            $avaliacao = ContratacaoAvaliacaoTecnica::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'contratacao_id' => $contratacao->id,
                'status' => AvaliacaoTecnicaStatus::RASCUNHO,
            ]);

            foreach (AvaliacaoTecnicaCriterios::todos() as $criterio) {
                ContratacaoAvaliacaoTecnicaItem::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'avaliacao_id' => $avaliacao->id,
                    'codigo' => $criterio['codigo'],
                    'label' => $criterio['label'],
                    'peso_percentual' => $criterio['peso_percentual'],
                    'nota' => null,
                    'observacao' => null,
                ]);
            }

            return $avaliacao->fresh(['itens', 'fornecedorVencedor']) ?? $avaliacao;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(ContratacaoAvaliacaoTecnica $avaliacao): array
    {
        $avaliacao->loadMissing(['itens', 'fornecedorVencedor']);

        return [
            'uuid' => $avaliacao->uuid,
            'status' => $avaliacao->status,
            'indice_percentual' => $avaliacao->indice_percentual !== null ? (float) $avaliacao->indice_percentual : null,
            'qualificada' => AvaliacaoTecnicaCalculo::qualificada(
                $avaliacao->indice_percentual !== null ? (float) $avaliacao->indice_percentual : null,
            ),
            'indice_minimo_percentual' => AvaliacaoTecnicaCriterios::INDICE_MINIMO_PERCENTUAL,
            'observacao' => $avaliacao->observacao,
            'fornecedor_vencedor_uuid' => $avaliacao->fornecedorVencedor?->uuid,
            'itens' => $avaliacao->itens->map(fn (ContratacaoAvaliacaoTecnicaItem $item) => [
                'uuid' => $item->uuid,
                'codigo' => $item->codigo,
                'label' => $item->label,
                'peso_percentual' => (float) $item->peso_percentual,
                'nota' => $item->nota !== null ? (float) $item->nota : null,
                'observacao' => $item->observacao,
            ])->values()->all(),
        ];
    }

    private function loadVendorList(UsuarioCliente $usuario, string $uuid): Contratacao
    {
        $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoElegivelParaVendorList::checkConsulta($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        return $contratacao;
    }

    private function loadVendorListEditavel(UsuarioCliente $usuario, string $uuid): Contratacao
    {
        $contratacao = $this->loadVendorList($usuario, $uuid);

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está mais editável (vencedor já definido).');
        }

        if (! UsuarioPodeEditarVendorList::check($usuario, $contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Sem permissão para editar a lista de fornecedores.');
        }

        return $contratacao;
    }
}
