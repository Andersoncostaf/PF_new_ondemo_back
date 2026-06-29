<?php

namespace App\Modules\Identidade\Infrastructure\Auth;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\JwtTokenPort;
use App\Modules\Identidade\Domain\Exceptions\TokenInvalidoException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class HmacJwtTokenService implements JwtTokenPort
{
    public function issueForUsuarioCliente(UsuarioCliente $usuario): array
    {
        $ttl = (int) config('identidade.jwt.ttl', 3600);
        $now = time();
        $jti = (string) Str::uuid();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $usuario->id,
            'tenant_id' => $usuario->tenant_id,
            'perfil' => $usuario->perfil,
            'tipo_conta' => 'cliente',
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        return [
            'token' => $this->encode($payload),
            'expires_in' => $ttl,
        ];
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new TokenInvalidoException;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSignature = $this->sign("{$headerB64}.{$payloadB64}");

        if (! hash_equals($expectedSignature, $this->base64UrlDecode($signatureB64))) {
            throw new TokenInvalidoException;
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            throw new TokenInvalidoException;
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new TokenInvalidoException('Token expirado.');
        }

        if ($this->isInvalidated($payload['jti'] ?? '')) {
            throw new TokenInvalidoException('Token revogado.');
        }

        if (($payload['tipo_conta'] ?? '') !== 'cliente') {
            throw new TokenInvalidoException;
        }

        return $payload;
    }

    public function invalidate(string $token): void
    {
        $payload = $this->decode($token);
        $jti = $payload['jti'] ?? null;
        $exp = $payload['exp'] ?? null;

        if (! is_string($jti) || ! is_int($exp)) {
            return;
        }

        $ttl = max(1, $exp - time());
        Cache::put($this->blacklistKey($jti), true, $ttl);
    }

    public function isInvalidated(string $jti): bool
    {
        if ($jti === '') {
            return true;
        }

        return Cache::has($this->blacklistKey($jti));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode($this->sign("{$header}.{$body}"));

        return "{$header}.{$body}.{$signature}";
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret(), true);
    }

    private function secret(): string
    {
        $secret = config('identidade.jwt.secret');

        if (! is_string($secret) || $secret === '') {
            throw new TokenInvalidoException('JWT secret não configurado.');
        }

        return $secret;
    }

    private function blacklistKey(string $jti): string
    {
        return 'identidade:jwt:blacklist:'.$jti;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}
