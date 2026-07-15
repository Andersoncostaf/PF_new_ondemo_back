<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use App\Modules\Contratacao\Domain\VisitaTecnicaResolucao;
use App\Modules\Contratacao\Domain\VisitaTecnicaStatus;

final class VisitaTecnicaService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private ContratacaoFornecedorRepositoryPort $fornecedorRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function obter(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, false);

        return $this->serializar($fornecedor);
    }

    /**
     * Agenda (ou reagenda) a visita técnica pelo lado Compras.
     * Sem portal do fornecedor: status vai direto para agendamento_feito.
     *
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function agendar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, array $dados): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        if (! VisitaTecnicaStatus::permiteAgendarPorCompras(
            $fornecedor->visita_tecnica_status,
            $fornecedor->visita_tecnica_resolucao,
        )) {
            throw new ContratacaoTransicaoInvalidaException(
                'Visita técnica não pode ser agendada no status/resolução atual.',
            );
        }

        $atualizado = $this->fornecedorRepository->updateVisitaTecnica($fornecedor, [
            'visita_tecnica_status' => VisitaTecnicaStatus::AGENDAMENTO_FEITO,
            'visita_tecnica_necessaria' => true,
            'visita_agendada_data' => $dados['data'],
            'visita_agendada_hora' => $dados['hora'],
            'visita_agendada_local' => $dados['local'],
            'visita_tecnica_observacao' => $dados['observacao'] ?? $fornecedor->visita_tecnica_observacao,
            'visita_agendada_por_compras_em' => now(),
        ]);

        return $this->serializar($atualizado);
    }

    /**
     * Registra visita como concluída (Compras, sem aceite do fornecedor).
     *
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function concluir(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, array $dados = []): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        if (! VisitaTecnicaResolucao::permiteRegistrarPorCompras($fornecedor->visita_tecnica_resolucao)) {
            throw new ContratacaoTransicaoInvalidaException(
                'Visita técnica já está resolvida e não pode ser marcada como concluída.',
            );
        }

        $payload = [
            'visita_tecnica_status' => VisitaTecnicaStatus::AGENDAMENTO_FEITO,
            'visita_tecnica_resolucao' => VisitaTecnicaResolucao::CONCLUIDA,
            'visita_tecnica_necessaria' => true,
            'visita_tecnica_concluida_em' => now(),
            'visita_tecnica_dispensada_em' => null,
            'visita_dispensa_justificativa' => null,
        ];

        if (array_key_exists('observacao', $dados)) {
            $payload['visita_tecnica_observacao'] = $dados['observacao'];
        }

        if (! empty($dados['data']) || ! empty($dados['hora']) || ! empty($dados['local'])) {
            if (empty($dados['data']) || empty($dados['hora']) || empty($dados['local'])) {
                throw new ContratacaoTransicaoInvalidaException(
                    'Para concluir com agendamento, informe data, hora e local.',
                );
            }
            $payload['visita_agendada_data'] = $dados['data'];
            $payload['visita_agendada_hora'] = $dados['hora'];
            $payload['visita_agendada_local'] = $dados['local'];
            $payload['visita_agendada_por_compras_em'] = now();
        }

        $atualizado = $this->fornecedorRepository->updateVisitaTecnica($fornecedor, $payload);

        return $this->serializar($atualizado);
    }

    /**
     * Dispensa a visita técnica pelo lado Compras (sem fluxo de aprovação do fornecedor).
     *
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function dispensar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, array $dados = []): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        if (! VisitaTecnicaResolucao::permiteRegistrarPorCompras($fornecedor->visita_tecnica_resolucao)) {
            throw new ContratacaoTransicaoInvalidaException(
                'Visita técnica já está resolvida e não pode ser dispensada.',
            );
        }

        $atualizado = $this->fornecedorRepository->updateVisitaTecnica($fornecedor, [
            'visita_tecnica_necessaria' => false,
            'visita_tecnica_resolucao' => VisitaTecnicaResolucao::DISPENSADA,
            'visita_dispensa_justificativa' => $dados['justificativa'] ?? $dados['observacao'] ?? null,
            'visita_tecnica_observacao' => $dados['observacao'] ?? $fornecedor->visita_tecnica_observacao,
            'visita_tecnica_dispensada_em' => now(),
            'visita_tecnica_concluida_em' => null,
        ]);

        return $this->serializar($atualizado);
    }

    /**
     * @return array{0: Contratacao, 1: ContratacaoFornecedor}
     */
    private function loadContexto(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, bool $exigeEdicao): array
    {
        $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! in_array($contratacao->status, [
            ContratacaoStatus::EM_VENDOR_LIST,
            ContratacaoStatus::VENCEDOR_DEFINIDO,
        ], true)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está elegível para visita técnica.');
        }

        if ($exigeEdicao && ! UsuarioPodeEditarVendorList::check($usuario, $contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Sem permissão para editar a lista de fornecedores.');
        }

        $fornecedor = $this->fornecedorRepository->findByUuidForContratacao($contratacao, $fornecedorUuid);
        if ($fornecedor === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        return [$contratacao, $fornecedor];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(ContratacaoFornecedor $fornecedor): array
    {
        return [
            'fornecedor_uuid' => $fornecedor->uuid,
            'visita_tecnica_status' => VisitaTecnicaStatus::normalizar($fornecedor->visita_tecnica_status),
            'visita_tecnica_resolucao' => VisitaTecnicaResolucao::normalizar($fornecedor->visita_tecnica_resolucao),
            'visita_tecnica_necessaria' => $fornecedor->visita_tecnica_necessaria,
            'visita_agendada_data' => $fornecedor->visita_agendada_data?->format('Y-m-d'),
            'visita_agendada_hora' => $fornecedor->visita_agendada_hora,
            'visita_agendada_local' => $fornecedor->visita_agendada_local,
            'visita_agendada_por_compras_em' => $fornecedor->visita_agendada_por_compras_em?->toIso8601String(),
            'visita_tecnica_observacao' => $fornecedor->visita_tecnica_observacao,
            'visita_dispensa_justificativa' => $fornecedor->visita_dispensa_justificativa,
            'visita_tecnica_concluida_em' => $fornecedor->visita_tecnica_concluida_em?->toIso8601String(),
            'visita_tecnica_dispensada_em' => $fornecedor->visita_tecnica_dispensada_em?->toIso8601String(),
            'resolvida' => VisitaTecnicaResolucao::estaResolvida($fornecedor->visita_tecnica_resolucao),
        ];
    }
}
