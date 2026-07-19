<?php

declare(strict_types=1);

use PHPUnit\TextUI\DefaultResultPrinter;

/**
 * Custom PHPUnit Result Printer that suppresses test output.
 *
 * Prevents HTML from component-rendering tests cluttering the console.
 */
class CleanOutputPrinter extends DefaultResultPrinter
{
    /**
     * Override to suppress output from tests.
     *
     * @param string $output
     */
    protected function write_progress(string $progress): void
    {
        parent::write_progress($progress);
    }
}
