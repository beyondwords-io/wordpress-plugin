<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/index.php',
        __DIR__ . '/speechkit.php',
        __DIR__ . '/uninstall.php',
    ])
    ->withSkip([
        __DIR__ . '/tests',
        __DIR__ . '/vendor',
    ])
    ->withRules([
        // Add any additional rules here.
    ])
    ->withPreparedSets(
        deadCode: true,  // Remove dead code
        codeQuality: false, // Don't auto-refactor code quality (too aggressive for WordPress)
    )
    ->withPhpSets(
        php80: true // Use PHP 8.0 features
    )
    ->withTypeCoverageLevel(0); // Don't enforce type coverage (just clean up)
