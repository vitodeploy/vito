<?php

namespace App\Actions\Network;

class GenerateWireGuardKeys
{
    /**
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
