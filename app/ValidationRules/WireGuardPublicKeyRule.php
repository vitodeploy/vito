<?php

namespace App\ValidationRules;

use App\Models\Network;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WireGuardPublicKeyRule implements ValidationRule
{
    public function __construct(private Network $network, private ?int $ignorePeerId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || preg_match('/^[A-Za-z0-9+\/]{43}=$/', $value) !== 1
            || strlen((string) base64_decode($value, true)) !== 32) {
            $fail('The :attribute must be a valid WireGuard public key.')->translate();

            return;
        }

        $collidesWithServer = $this->network->servers()
            ->where('public_key', $value)
            ->exists();

        $collidesWithPeer = $this->network->peers()
            ->where('public_key', $value)
            ->when($this->ignorePeerId !== null, fn ($query) => $query->whereKeyNot($this->ignorePeerId))
            ->exists();

        if ($collidesWithServer || $collidesWithPeer) {
            $fail('The :attribute is already in use on this network.')->translate();
        }
    }
}
