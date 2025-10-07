<?php

declare(strict_types=1);

return \Rector\Config\RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRules([
        \N1ebieski\KSEFClient\Overrides\Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
    ])
    ->withSkip([
        \Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class,
        \Rector\Carbon\Rector\MethodCall\DateTimeMethodCallToCarbonRector::class,
        \Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
        \Rector\Php81\Rector\Array_\FirstClassCallableRector::class,
        \Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
        \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
        \Rector\Carbon\Rector\New_\DateTimeInstanceToCarbonRector::class,
        \Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class => [
            __DIR__ . '/src/Actions/ConvertEcdsaDerToRaw/ConvertEcdsaDerToRawHandler.php'
        ],
        \Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector::class => [
            __DIR__ . '/src/Actions/ConvertEcdsaDerToRaw/ConvertEcdsaDerToRawHandler.php'
        ]
    ])
    ->withComposerBased(phpunit: true)
    ->withImportNames(removeUnusedImports: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
        carbon: true,
        phpunitCodeQuality: true
    )
    ->withDowngradeSets(php81: true)
    ->withPhpSets(php81: true);
