<?php

namespace App\Modules\Contratacao\Domain;

final class ContratacaoStatus
{
    public const RASCUNHO = 'rascunho';

    public const AGUARDANDO_ANALISE_COMPRAS = 'aguardando_analise_compras';

    public const EM_ANALISE = 'em_analise';

    public const AGUARDANDO_AJUSTE_AREA = 'aguardando_ajuste_area';

    public const APROVADO_COMPRAS = 'aprovado_compras';

    public const EM_VENDOR_LIST = 'em_vendor_list';

    /** @var list<string> */
    public const FILA_COMPRAS = [
        self::AGUARDANDO_ANALISE_COMPRAS,
        self::EM_ANALISE,
        self::EM_VENDOR_LIST,
    ];

    /** @var list<string> */
    public const ETAPAS_APONTAMENTO = [
        'filial',
        'tr',
        'qqp',
        'anexos',
        'solicitacao_servico',
    ];
}
