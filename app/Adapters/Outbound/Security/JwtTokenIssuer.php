<?php

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\TokenIssuerInterface;
use App\Domain\Model\User;

final class JwtTokenIssuer implements TokenIssuerInterface
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function issueToken(User $user, int $ttlSeconds = 28800): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload = [
            'sub' => $user->getId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ];

        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $expectedSig = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->secret, true);
        if (!hash_equals($expectedSig, $this->base64UrlDecode($base64Signature))) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($base64Payload), true);
        if (!$payload || !isset($payload['exp']) || time() >= $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
