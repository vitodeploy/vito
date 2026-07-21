<?php

namespace App\Actions\Network;

class GenerateWireGuardKeys
{
    /**
     * Generate a WireGuard (Curve25519) key pair, base64-encoded, matching
     * `wg genkey` / `wg pubkey`: the private key is clamped and the public key
     * is derived via X25519 base-point multiplication.
     *
     * @return array{private_key: string, public_key: string}
     */
    public function generate(): array
    {
        $private = random_bytes(32);
        $private[0] = chr(ord($private[0]) & 248);
        $private[31] = chr((ord($private[31]) & 127) | 64);

        $public = sodium_crypto_scalarmult_base($private);

        return [
            'private_key' => base64_encode($private),
            'public_key' => base64_encode($public),
        ];
    }
}
