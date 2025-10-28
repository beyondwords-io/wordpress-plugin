<?php

declare(strict_types=1);

use PHPUnit\TextUI\DefaultResultPrinter;

/**
 * Custom PHPUnit Result Printer that suppresses test output.
 *
 * This prevents HTML output from tests that render components
 * from cluttering the console output.
 */
class CleanOutputPrinter extends DefaultResultPrinter
{
    /**
     * Override to suppress output from tests.
     *
     * @param string $output
     */
    protected function writeProgress(string $progress): void
    {
        // Suppress any captured output, only show progress
        parent::writeProgress($progress);
    }
}
