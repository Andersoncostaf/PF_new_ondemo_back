<?php

namespace App\Modules\Identidade\Interface\Http;

use App\Http\Controllers\Controller;
use App\Modules\Identidade\Application\DTO\CadastroInput;
use App\Modules\Identidade\Application\UseCase\CadastrarTenantComPrimeiroUsuarioUseCase;
use App\Modules\Identidade\Interface\Http\Requests\CadastroRequest;
use Illuminate\Http\JsonResponse;

class CadastroController extends Controller
{
    public function __construct(
        private CadastrarTenantComPrimeiroUsuarioUseCase $useCase,
    ) {}

    public function store(CadastroRequest $request): JsonResponse
    {
        $result = $this->useCase->executar(new CadastroInput(
            razaoSocial: $request->string('razao_social')->toString(),
            cnpj: $request->string('cnpj')->toString(),
            slug: $request->input('slug'),
            nome: $request->string('nome')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            cargo: $request->input('cargo'),
        ));

        return response()->json($result->toArray(), 201);
    }
}
