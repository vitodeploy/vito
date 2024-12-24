<?php

declare(strict_types=1);

use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;

SimpleParameterProvider::setParameter(Option::INDENT_CHAR, " ");

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
    ])
    ->withRules([
        AddGenericReturnTypeToRelationsRector::class,
    ])
    // ->withTypeCoverageLevel(0)
;
