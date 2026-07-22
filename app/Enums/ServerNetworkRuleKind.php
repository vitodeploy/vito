<?php

namespace App\Enums;

enum ServerNetworkRuleKind: string
{
    case HANDSHAKE = 'handshake';
    case RULE = 'rule';
}
