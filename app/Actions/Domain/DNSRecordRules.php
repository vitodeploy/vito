<?php

namespace App\Actions\Domain;

use Illuminate\Validation\Rule;

class DNSRecordRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'SRV', 'NS', 'CAA', 'PTR', 'SOA']),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
            ],
            'ttl' => [
                'integer',
                'min:1',
                'max:86400',
            ],
            'proxied' => [
                'boolean',
            ],
            'priority' => [
                'nullable',
                'prohibited_unless:type,MX',
                'integer',
                'min:0',
                'max:65535',
            ],
        ];
    }
}
