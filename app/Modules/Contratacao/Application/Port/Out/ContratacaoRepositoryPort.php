<?php



namespace App\Modules\Contratacao\Application\Port\Out;



use App\Models\Contratacao;

use App\Models\ContratacaoApontamento;

use App\Modules\Contratacao\Application\DTO\ContratacaoInput;

use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Collection;



interface ContratacaoRepositoryPort

{

    public function createRascunho(string $tenantId, string $usuarioId, ContratacaoInput $input): Contratacao;



    public function findByUuidForTenant(string $uuid, string $tenantId): ?Contratacao;



    public function updateRascunho(Contratacao $contratacao, ContratacaoInput $input): Contratacao;



    public function deleteRascunho(Contratacao $contratacao): void;



    public function submeter(Contratacao $contratacao): Contratacao;



    public function listByTenant(string $tenantId, ContratacaoListFilter $filter): LengthAwarePaginator;



    public function listPendentesAprovacao(string $tenantId, ContratacaoListFilter $filter): LengthAwarePaginator;



    public function listFilaCompras(string $tenantId, ContratacaoListFilter $filter): LengthAwarePaginator;



    public function assumirAnalise(Contratacao $contratacao, string $analistaUsuarioId): Contratacao;



    public function countApontamentosPendentes(Contratacao $contratacao): int;



    public function retornarParaAjustes(Contratacao $contratacao): Contratacao;



    public function aprovarAnalise(Contratacao $contratacao): Contratacao;



    public function assumirVendorList(Contratacao $contratacao, string $compradorUsuarioId): Contratacao;



    public function reenviarAposAjustes(Contratacao $contratacao): Contratacao;



    /**

     * @return Collection<int, ContratacaoApontamento>

     */

    public function listApontamentos(Contratacao $contratacao, ?string $etapa = null): Collection;



    public function findApontamentoForContratacao(Contratacao $contratacao, string $apontamentoId): ?ContratacaoApontamento;



    public function createApontamento(

        Contratacao $contratacao,

        string $tenantId,

        string $autorUsuarioId,

        string $etapa,

        string $descricao,

        ?UploadedFile $arquivo = null,

    ): ContratacaoApontamento;



    public function updateApontamento(

        ContratacaoApontamento $apontamento,

        string $descricao,

        ?UploadedFile $arquivo = null,

    ): ContratacaoApontamento;



    public function deleteApontamento(ContratacaoApontamento $apontamento): void;



    public function responderApontamento(

        ContratacaoApontamento $apontamento,

        string $respondedorUsuarioId,

        string $resposta,

    ): ContratacaoApontamento;

}


