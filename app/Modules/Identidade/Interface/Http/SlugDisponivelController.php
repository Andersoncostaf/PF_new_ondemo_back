<?php

namespace App\Modules\Identidade\Interface\Http;

use App\Http\Controllers\Controller;
use App\Modules\Identidade\Application\UseCase\VerificarSlugDisponivelUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlugDisponivelController extends Controller
{
    public function __construct(
        private VerificarSlugDisponivelUseCase $useCase,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $slug = (string) $request->query('slug', '');

        return response()->json($this->useCase->executar($slug));
    }
}
