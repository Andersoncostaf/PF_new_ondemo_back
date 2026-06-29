<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'unknown';

        try {
            DB::connection()->getPdo();
            $database = 'connected';
        } catch (\Throwable) {
            $database = 'disconnected';
        }

        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'database' => $database,
        ]);
    }
}
