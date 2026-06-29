<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\UsuarioCliente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'clientex'],
            [
                'razao_social' => 'Cliente Exemplo Ltda',
                'nome_fantasia' => 'Cliente Ex',
                'cnpj' => '12345678000199',
                'status' => 'ativo',
                'trial_starts_at' => Carbon::now(),
                'trial_ends_at' => Carbon::now()->addDays(15),
                'subscription_status' => 'trial',
            ]
        );

        UsuarioCliente::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@clientex.local'],
            [
                'nome' => 'Admin Demo',
                'password' => 'password',
                'cargo' => 'Administrador',
                'perfil' => 'admin_tenant',
                'status' => 'ativo',
                'email_verified_at' => Carbon::now(),
            ]
        );

        UsuarioCliente::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'area@clientex.local'],
            [
                'nome' => 'Area Demo',
                'password' => 'password',
                'cargo' => 'Analista de Compras',
                'perfil' => 'area',
                'status' => 'ativo',
                'email_verified_at' => Carbon::now(),
            ]
        );
    }
}
