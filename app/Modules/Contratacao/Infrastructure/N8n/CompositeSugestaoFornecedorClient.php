<?php

namespace App\Modules\Contratacao\Infrastructure\N8n;

use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;

/**
 * Orquestra n8n → LLM → busca web para complementar sugestões externas.
 */
final class CompositeSugestaoFornecedorClient implements N8nSugestaoFornecedorPort
{
    public function __construct(
        private N8nSugestaoFornecedorClient $n8n,
        private LlmSugestaoFornecedorClient $llm,
        private WebSearchSugestaoFornecedorClient $webSearch,
    ) {}

    public function solicitarSugestoes(
        string $tenantId,
        string $contratacaoUuid,
        array $brief,
    ): array {
        $n8nResult = $this->n8n->solicitarSugestoes($tenantId, $contratacaoUuid, $brief);
        if ($n8nResult !== []) {
            return $n8nResult;
        }

        $llmResult = $this->llm->solicitarSugestoes($tenantId, $contratacaoUuid, $brief);
        if ($llmResult !== []) {
            return $llmResult;
        }

        return $this->webSearch->solicitarSugestoes($tenantId, $contratacaoUuid, $brief);
    }
}
